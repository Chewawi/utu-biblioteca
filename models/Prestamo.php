<?php
// ============================================================
//  Modelo Prestamo
//  Conecta un Socio con un Item. No guarda el nombre del socio
//  ni el título del item directamente, guarda sus ids — por
//  eso listar() hace un INNER JOIN contra socios e items para
//  traer el nombre y el título ya resueltos.
// ============================================================

class Prestamo {
    // Opciones del filtro de estado: las de la columna más 'vencido', que no se
    // guarda: es un préstamo activo con la fecha límite pasada.
    const FILTROS_ESTADO = ['activo', 'vencido', 'devuelto'];

    // La regla de "vencido", escrita una sola vez para todas las consultas (el
    // "?" es la fecha de hoy). estaVencido() es la misma regla para un préstamo ya leído.
    const VENCIDO = "(p.estado = 'activo' AND p.fecha_limite < ?)";

    private $id;
    private $fechaPrestamo;
    private $fechaLimite;
    private $fechaDevolucion;
    private $estado;
    private $socioNombre;
    private $itemTitulo;
    private $socioTelefono;

    public function __construct($id, $fechaPrestamo, $fechaLimite, $fechaDevolucion, $estado, $socioNombre, $itemTitulo, $socioTelefono) {
        $this->id = $id;
        $this->fechaPrestamo = $fechaPrestamo;
        $this->fechaLimite = $fechaLimite;
        $this->fechaDevolucion = $fechaDevolucion;
        $this->estado = $estado;
        $this->socioNombre = $socioNombre;
        $this->itemTitulo = $itemTitulo;
        $this->socioTelefono = $socioTelefono;
    }

    // --- getters ---
    public function getId() { return $this->id; }
    public function getFechaPrestamo() { return $this->fechaPrestamo; }
    public function getFechaLimite() { return $this->fechaLimite; }
    public function getFechaDevolucion() { return $this->fechaDevolucion; }
    public function estaActivo() { return $this->estado === 'activo'; }
    // Hoy sale de PHP (date), el mismo reloj que se usa para registrar la devolución.
    public function estaVencido() { return $this->estaActivo() && $this->fechaLimite < date('Y-m-d'); }
    public function getSocioNombre() { return $this->socioNombre; }
    public function getItemTitulo() { return $this->itemTitulo; }
    public function getSocioTelefono() { return $this->socioTelefono; }

    /** 'Vencido', 'Activo' o 'Devuelto', para mostrar. */
    public function getEstadoTexto() {
        if ($this->estaVencido()) {
            return 'Vencido';
        }
        return $this->estaActivo() ? 'Activo' : 'Devuelto';
    }

    /** Días que pasaron desde la fecha límite. 0 si no está vencido. */
    public function getDiasDeAtraso() {
        if (!$this->estaVencido()) {
            return 0;
        }
        return (new DateTime($this->fechaLimite))->diff(new DateTime(date('Y-m-d')))->days;
    }

    // --- acceso a datos ---

    /**
     * El FROM y el WHERE para los $filtros (las mismas claves que listar()),
     * con los valores de cada "?". Lo usan listar() y contarPorEstado().
     * Devuelve [el FROM ... WHERE armado, sus parámetros].
     */
    private static function armarFiltros($filtros) {
        // Completamos las claves que no vinieron, así abajo no hay que preguntar si existen.
        $filtros = array_merge(
            ['item_id' => '', 'socio_id' => '', 'estado' => '', 'desde' => '', 'hasta' => '', 'q' => ''],
            $filtros
        );

        // Cada filtro usado suma una condición al WHERE y su valor a $parametros.
        $condiciones = [];
        $parametros = [];
        if ($filtros['item_id'] !== '') {
            $condiciones[] = 'p.item_id = ?';
            $parametros[] = $filtros['item_id'];
        }
        if ($filtros['socio_id'] !== '') {
            $condiciones[] = 'p.socio_id = ?';
            $parametros[] = $filtros['socio_id'];
        }
        if ($filtros['estado'] === 'vencido') {
            $condiciones[] = self::VENCIDO;
            $parametros[] = date('Y-m-d');
        } elseif ($filtros['estado'] !== '') {
            $condiciones[] = 'p.estado = ?';
            $parametros[] = $filtros['estado'];
        }
        if ($filtros['desde'] !== '') {
            $condiciones[] = 'p.fecha_prestamo >= ?';
            $parametros[] = $filtros['desde'];
        }
        if ($filtros['hasta'] !== '') {
            $condiciones[] = 'p.fecha_prestamo <= ?';
            $parametros[] = $filtros['hasta'];
        }
        if ($filtros['q'] !== '') {
            $condiciones[] = '(s.nombre LIKE ? OR i.titulo LIKE ?)';
            $parametros[] = '%' . $filtros['q'] . '%';
            $parametros[] = '%' . $filtros['q'] . '%';
        }

        $desdeDonde = '
            FROM prestamos p
            INNER JOIN socios s ON p.socio_id = s.id
            INNER JOIN items i ON p.item_id = i.id
        ';
        if (!empty($condiciones)) {
            $desdeDonde .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        return [$desdeDonde, $parametros];
    }

    /**
     * Cuántos préstamos hay en cada estado con los demás $filtros (el estado
     * que venga se ignora), para los contadores de las pestañas. Los vencidos
     * también cuentan como activos, igual que al filtrar.
     * Devuelve ['' => total, 'activo' => ..., 'vencido' => ..., 'devuelto' => ...].
     */
    public static function contarPorEstado($pdo, $filtros) {
        $filtros['estado'] = '';
        [$desdeDonde, $parametros] = self::armarFiltros($filtros);
        $stmt = $pdo->prepare("
            SELECT COUNT(*),
                   COALESCE(SUM(p.estado = 'activo'), 0),
                   COALESCE(SUM(" . self::VENCIDO . "), 0),
                   COALESCE(SUM(p.estado = 'devuelto'), 0)
        " . $desdeDonde);
        // El "?" de la fecha está en el SELECT, antes que los del WHERE.
        $stmt->execute(array_merge([date('Y-m-d')], $parametros));
        $fila = array_map('intval', $stmt->fetch(PDO::FETCH_NUM));
        return array_combine(['', 'activo', 'vencido', 'devuelto'], $fila);
    }

    /**
     * Préstamos con el nombre del socio y el título del item, filtrados.
     * $filtros es un array con estas claves (todas opcionales; vacío = sin filtrar):
     *   item_id, socio_id,
     *   estado        -> uno de FILTROS_ESTADO,
     *   desde, hasta  -> rango sobre fecha_prestamo (formato AAAA-MM-DD),
     *   q             -> texto que se busca en el nombre del socio o el título del item,
     *   pagina        -> número de página (si está fuera de rango se corrige),
     *   por_pagina    -> cuántos por página; 0 o sin poner = todos, sin paginar.
     * Primero los activos (en el ENUM "activo" va antes que "devuelto").
     * Devuelve ['filas' => préstamos de esa página, 'total' => cantidad de resultados,
     *           'pagina' => página que se muestra, 'paginas' => cantidad de páginas].
     */
    public static function listar($pdo, $filtros = []) {
        $filtros = array_merge(['pagina' => 1, 'por_pagina' => 0], $filtros);

        // El FROM y el WHERE son los mismos para contar y para traer las filas.
        [$desdeDonde, $parametros] = self::armarFiltros($filtros);

        $stmt = $pdo->prepare('SELECT COUNT(*)' . $desdeDonde);
        $stmt->execute($parametros);
        $total = (int) $stmt->fetchColumn();

        $sql = '
            SELECT p.id, p.fecha_prestamo, p.fecha_limite, p.fecha_devolucion, p.estado,
                   s.nombre AS socio_nombre, s.telefono AS socio_telefono,
                   i.titulo AS item_titulo
        ' . $desdeDonde . ' ORDER BY p.estado, p.fecha_prestamo DESC';

        $paginacion = calcularPaginacion($total, $filtros['pagina'], $filtros['por_pagina']);
        $sql .= $paginacion['limite'];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);
        $filas = $stmt->fetchAll();

        $prestamos = [];
        foreach ($filas as $f) {
            $prestamos[] = new Prestamo($f['id'], $f['fecha_prestamo'], $f['fecha_limite'], $f['fecha_devolucion'], $f['estado'], $f['socio_nombre'], $f['item_titulo'], $f['socio_telefono']);
        }
        return ['filas' => $prestamos, 'total' => $total, 'pagina' => $paginacion['pagina'], 'paginas' => $paginacion['paginas']];
    }

    /** Los items que pidió alguna vez el socio $socioId, para su filtro por ítem: [id => título]. */
    public static function itemsDelSocio($pdo, $socioId) {
        $stmt = $pdo->prepare('
            SELECT DISTINCT i.id, i.titulo
            FROM prestamos p
            INNER JOIN items i ON p.item_id = i.id
            WHERE p.socio_id = ?
            ORDER BY i.titulo
        ');
        $stmt->execute([$socioId]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /** Los ids de los items que el socio $socioId tiene prestados ahora (préstamos activos). */
    public static function itemsPrestadosA($pdo, $socioId) {
        $stmt = $pdo->prepare("SELECT item_id FROM prestamos WHERE socio_id = ? AND estado = 'activo'");
        $stmt->execute([$socioId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Préstamos vencidos con el nombre y el email del socio, para mandar los
     * recordatorios. Ordenados por socio, así quedan juntos los de cada uno.
     * Devuelve filas con socio_id, socio_nombre, email, item_titulo y fecha_limite.
     */
    public static function vencidosConEmail($pdo) {
        $stmt = $pdo->prepare("
            SELECT s.id AS socio_id, s.nombre AS socio_nombre, u.email,
                   i.titulo AS item_titulo, p.fecha_limite
            FROM prestamos p
            INNER JOIN socios s ON p.socio_id = s.id
            INNER JOIN usuarios u ON s.usuario_id = u.id
            INNER JOIN items i ON p.item_id = i.id
            WHERE " . self::VENCIDO . "
            ORDER BY s.id, p.fecha_limite
        ");
        $stmt->execute([date('Y-m-d')]);
        return $stmt->fetchAll();
    }

    /** INSERT simple, sin JOIN (solo guarda los ids). */
    public static function crear($pdo, $socioId, $itemId, $fecha, $fechaLimite) {
        $stmt = $pdo->prepare(
            "INSERT INTO prestamos (socio_id, item_id, fecha_prestamo, fecha_limite) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$socioId, $itemId, $fecha, $fechaLimite]);
    }

    /** El id del item del préstamo $id, o null si no existe. */
    public static function itemDelPrestamo($pdo, $id) {
        $stmt = $pdo->prepare('SELECT item_id FROM prestamos WHERE id = ?');
        $stmt->execute([$id]);
        $itemId = $stmt->fetchColumn();
        return $itemId === false ? null : $itemId;
    }

    /**
     * Pasa el préstamo a 'devuelto' con la fecha $fecha. No se borra: queda en
     * el historial. Si ya estaba devuelto no cambia nada y devuelve false.
     */
    public static function devolver($pdo, $id, $fecha) {
        $stmt = $pdo->prepare(
            "UPDATE prestamos SET estado = 'devuelto', fecha_devolucion = ? WHERE id = ? AND estado = 'activo'"
        );
        $stmt->execute([$fecha, $id]);
        return $stmt->rowCount() > 0;
    }
}
