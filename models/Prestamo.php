<?php
// ============================================================
//  Modelo Prestamo
//  Conecta un Socio con un Item. No guarda el nombre del socio
//  ni el título del item directamente, guarda sus ids — por
//  eso listar() hace un INNER JOIN contra socios e items para
//  traer el nombre y el título ya resueltos.
// ============================================================

class Prestamo {
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

    /** Todos los préstamos, con el nombre del socio y el título del item. Primero los activos (en el ENUM "activo" va antes que "devuelto"). */
    public static function listar($pdo) {
        $stmt = $pdo->prepare("
            SELECT p.id, p.socio_id, p.item_id, p.fecha_prestamo, p.fecha_devolucion, p.estado,
                   s.nombre AS socio_nombre,
                   i.titulo AS item_titulo
            FROM prestamos p
            INNER JOIN socios s ON p.socio_id = s.id
            INNER JOIN items i ON p.item_id = i.id
            ORDER BY p.estado, p.fecha_prestamo DESC
        ");
        $stmt->execute();
        $filas = $stmt->fetchAll();

        $prestamos = [];
        foreach ($filas as $f) {
            $prestamos[] = new Prestamo($f['id'], $f['socio_id'], $f['item_id'], $f['fecha_prestamo'], $f['fecha_devolucion'], $f['estado'], $f['socio_nombre'], $f['item_titulo']);
        }
        return $prestamos;
    }

    /** Los préstamos de un solo socio (para "Mis préstamos"). Primero los activos (en el ENUM "activo" va antes que "devuelto"). */
    public static function listarPorSocio($pdo, $socioId) {
        $stmt = $pdo->prepare("
            SELECT p.id, p.socio_id, p.item_id, p.fecha_prestamo, p.fecha_devolucion, p.estado,
                   s.nombre AS socio_nombre,
                   i.titulo AS item_titulo
            FROM prestamos p
            INNER JOIN socios s ON p.socio_id = s.id
            INNER JOIN items i ON p.item_id = i.id
            WHERE p.socio_id = ?
            ORDER BY p.estado, p.fecha_prestamo DESC
        ");
        $stmt->execute([$socioId]);
        $filas = $stmt->fetchAll();

        $prestamos = [];
        foreach ($filas as $f) {
            $prestamos[] = new Prestamo($f['id'], $f['socio_id'], $f['item_id'], $f['fecha_prestamo'], $f['fecha_devolucion'], $f['estado'], $f['socio_nombre'], $f['item_titulo']);
        }
        return $prestamos;
    }

    /** INSERT simple, sin JOIN (solo guarda los ids). */
    public static function crear($pdo, $socioId, $itemId, $fecha) {
        $stmt = $pdo->prepare(
            "INSERT INTO prestamos (socio_id, item_id, fecha_prestamo) VALUES (?, ?, ?)"
        );
        return $stmt->execute([$socioId, $itemId, $fecha]);
    }

    /**
     * Pasa el préstamo a 'devuelto' con la fecha de hoy. No se borra: queda en
     * el historial. Si ya estaba devuelto no cambia nada y devuelve false.
     */
    public static function devolver($pdo, $id) {
        $stmt = $pdo->prepare(
            "UPDATE prestamos SET estado = 'devuelto', fecha_devolucion = CURDATE() WHERE id = ? AND estado = 'activo'"
        );
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
