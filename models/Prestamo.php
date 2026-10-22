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

    private $id;
    private $socioId;
    private $itemId;
    private $fechaPrestamo;
    private $fechaDevolucion;
    private $estado;
    private $socioNombre;
    private $itemTitulo;

    public function __construct($id, $socioId, $itemId, $fechaPrestamo, $fechaDevolucion, $estado, $socioNombre, $itemTitulo) {
        $this->id = $id;
        $this->socioId = $socioId;
        $this->itemId = $itemId;
        $this->fechaPrestamo = $fechaPrestamo;
        $this->fechaDevolucion = $fechaDevolucion;
        $this->estado = $estado;
        $this->socioNombre = $socioNombre;
        $this->itemTitulo = $itemTitulo;
    }

    // --- getters ---
    public function getId() { return $this->id; }
    public function getSocioId() { return $this->socioId; }
    public function getItemId() { return $this->itemId; }
    public function getFechaPrestamo() { return $this->fechaPrestamo; }
    public function getFechaDevolucion() { return $this->fechaDevolucion; }
    public function getEstado() { return $this->estado; }
    public function estaActivo() { return $this->estado === 'activo'; }
    public function getSocioNombre() { return $this->socioNombre; }
    public function getItemTitulo() { return $this->itemTitulo; }

    // --- acceso a datos ---

    /**
     * Préstamos con el nombre del socio y el título del item, filtrados.
     * $filtros es un array con estas claves (todas opcionales; vacío = sin filtrar):
     *   item_id, socio_id, estado,
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
        if ($filtros['estado'] !== '') {
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
            SELECT p.id, p.socio_id, p.item_id, p.fecha_prestamo, p.fecha_devolucion, p.estado,
                   s.nombre AS socio_nombre,
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
            $prestamos[] = new Prestamo($f['id'], $f['socio_id'], $f['item_id'], $f['fecha_prestamo'], $f['fecha_devolucion'], $f['estado'], $f['socio_nombre'], $f['item_titulo']);
        }
        return ['filas' => $prestamos, 'total' => $total, 'pagina' => $pagina, 'paginas' => $paginas];
    }

    /** INSERT simple, sin JOIN (solo guarda los ids). */
    public static function crear($pdo, $socioId, $itemId, $fecha) {
        $stmt = $pdo->prepare(
            "INSERT INTO prestamos (socio_id, item_id, fecha_prestamo) VALUES (?, ?, ?)"
        );
        return $stmt->execute([$socioId, $itemId, $fecha]);
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
