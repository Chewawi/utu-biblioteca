<?php
// ============================================================
//  Modelo Prestamo
//  Conecta un Socio con un Item. No guarda el nombre del socio
//  ni el título del item directamente, guarda sus ids — por
//  eso listar() hace un INNER JOIN contra socios e items para
//  traer el nombre y el título ya resueltos.
// ============================================================

class Prestamo {
    // Tienen que coincidir con el ENUM de la columna "estado" en database.sql.
    const ESTADOS = ['activo', 'devuelto'];

    // Opciones del filtro de estado: las de la columna más 'vencido', que no se
    // guarda: es un préstamo activo con la fecha límite pasada.
    const FILTROS_ESTADO = ['activo', 'vencido', 'devuelto'];

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
        // Completamos las claves que no vinieron, así abajo no hay que preguntar si existen.
        $filtros = array_merge(
            ['item_id' => '', 'socio_id' => '', 'estado' => '', 'desde' => '', 'hasta' => '', 'q' => '',
             'pagina' => 1, 'por_pagina' => 0],
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
            $condiciones[] = "p.estado = 'activo' AND p.fecha_limite < ?";
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

        // El FROM y el WHERE son los mismos para contar y para traer las filas.
        $desdeDonde = '
            FROM prestamos p
            INNER JOIN socios s ON p.socio_id = s.id
            INNER JOIN items i ON p.item_id = i.id
        ';
        if (!empty($condiciones)) {
            $desdeDonde .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $stmt = $pdo->prepare('SELECT COUNT(*)' . $desdeDonde);
        $stmt->execute($parametros);
        $total = (int) $stmt->fetchColumn();

        $sql = '
            SELECT p.id, p.fecha_prestamo, p.fecha_limite, p.fecha_devolucion, p.estado,
                   s.nombre AS socio_nombre, s.telefono AS socio_telefono,
                   i.titulo AS item_titulo
        ' . $desdeDonde . ' ORDER BY p.estado, p.fecha_prestamo DESC';

        $pagina = 1;
        $paginas = 1;
        if ($filtros['por_pagina'] > 0) {
            $paginacion = calcularPaginacion($total, $filtros['pagina'], $filtros['por_pagina']);
            $pagina = $paginacion['pagina'];
            $paginas = $paginacion['paginas'];
            // LIMIT y OFFSET son enteros que calculamos nosotros (no texto del usuario),
            // por eso se pueden poner directo en el SQL.
            $sql .= ' LIMIT ' . (int) $filtros['por_pagina'] . ' OFFSET ' . $paginacion['desde'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);
        $filas = $stmt->fetchAll();

        $prestamos = [];
        foreach ($filas as $f) {
            $prestamos[] = new Prestamo($f['id'], $f['fecha_prestamo'], $f['fecha_limite'], $f['fecha_devolucion'], $f['estado'], $f['socio_nombre'], $f['item_titulo'], $f['socio_telefono']);
        }
        return ['filas' => $prestamos, 'total' => $total, 'pagina' => $pagina, 'paginas' => $paginas];
    }

    /** INSERT simple, sin JOIN (solo guarda los ids). */
    public static function crear($pdo, $socioId, $itemId, $fecha, $fechaLimite) {
        $stmt = $pdo->prepare(
            "INSERT INTO prestamos (socio_id, item_id, fecha_prestamo, fecha_limite) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$socioId, $itemId, $fecha, $fechaLimite]);
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
