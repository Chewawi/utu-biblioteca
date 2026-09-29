<?php
// ============================================================
//  Modelo Prestamo
//  Conecta un Socio con un Libro. No guarda el nombre del socio
//  ni el título del libro directamente, guarda sus ids — por
//  eso listar() hace un INNER JOIN contra socios y libros para
//  traer el nombre y el título ya resueltos.
// ============================================================

class Prestamo {
    private $id;
    private $socioId;
    private $libroId;
    private $fechaPrestamo;
    private $socioNombre;
    private $libroTitulo;

    public function __construct($id, $socioId, $libroId, $fechaPrestamo, $socioNombre, $libroTitulo) {
        $this->id = $id;
        $this->socioId = $socioId;
        $this->libroId = $libroId;
        $this->fechaPrestamo = $fechaPrestamo;
        $this->socioNombre = $socioNombre;
        $this->libroTitulo = $libroTitulo;
    }

    // --- getters ---
    public function getId() { return $this->id; }
    public function getSocioId() { return $this->socioId; }
    public function getLibroId() { return $this->libroId; }
    public function getFechaPrestamo() { return $this->fechaPrestamo; }
    public function getSocioNombre() { return $this->socioNombre; }
    public function getLibroTitulo() { return $this->libroTitulo; }

    // --- acceso a datos ---

    /** Todos los préstamos, con el nombre del socio y el título del libro. */
    public static function listar($pdo) {
        $stmt = $pdo->prepare("
            SELECT p.id, p.socio_id, p.libro_id, p.fecha_prestamo,
                   s.nombre AS socio_nombre,
                   l.titulo AS libro_titulo
            FROM prestamos p
            INNER JOIN socios s ON p.socio_id = s.id
            INNER JOIN libros l ON p.libro_id = l.id
            ORDER BY p.fecha_prestamo DESC
        ");
        $stmt->execute();
        $filas = $stmt->fetchAll();

        $prestamos = [];
        foreach ($filas as $f) {
            $prestamos[] = new Prestamo($f['id'], $f['socio_id'], $f['libro_id'], $f['fecha_prestamo'], $f['socio_nombre'], $f['libro_titulo']);
        }
        return $prestamos;
    }

    /** Los préstamos de un solo socio (para "Mis préstamos"). */
    public static function listarPorSocio($pdo, $socioId) {
        $stmt = $pdo->prepare("
            SELECT p.id, p.socio_id, p.libro_id, p.fecha_prestamo,
                   s.nombre AS socio_nombre,
                   l.titulo AS libro_titulo
            FROM prestamos p
            INNER JOIN socios s ON p.socio_id = s.id
            INNER JOIN libros l ON p.libro_id = l.id
            WHERE p.socio_id = ?
            ORDER BY p.fecha_prestamo DESC
        ");
        $stmt->execute([$socioId]);
        $filas = $stmt->fetchAll();

        $prestamos = [];
        foreach ($filas as $f) {
            $prestamos[] = new Prestamo($f['id'], $f['socio_id'], $f['libro_id'], $f['fecha_prestamo'], $f['socio_nombre'], $f['libro_titulo']);
        }
        return $prestamos;
    }

    /** INSERT simple, sin JOIN (solo guarda los ids). */
    public static function crear($pdo, $socioId, $libroId, $fecha) {
        $stmt = $pdo->prepare(
            "INSERT INTO prestamos (socio_id, libro_id, fecha_prestamo) VALUES (?, ?, ?)"
        );
        return $stmt->execute([$socioId, $libroId, $fecha]);
    }

    /** Borrar el préstamo = registrar que el libro ya se devolvió. */
    public static function eliminar($pdo, $id) {
        $stmt = $pdo->prepare("DELETE FROM prestamos WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
