<?php
// ============================================================
//  Modelo Libro
//  Sabe hablar con la tabla "libros".
// ============================================================

class Libro {
    private $id;
    private $titulo;
    private $autor;
    private $categoria;
    private $imagen;

    public function __construct($id, $titulo, $autor, $categoria, $imagen) {
        $this->id = $id;
        $this->titulo = $titulo;
        $this->autor = $autor;
        $this->categoria = $categoria;
        $this->imagen = $imagen;
    }

    // --- getters ---
    public function getId() { return $this->id; }
    public function getTitulo() { return $this->titulo; }
    public function getAutor() { return $this->autor; }
    public function getCategoria() { return $this->categoria; }
    public function getImagen() { return $this->imagen; }

    // --- acceso a datos ---

    /** Trae todos los libros. Si $q no está vacío, filtra por título o autor. */
    public static function listar($pdo, $q = '') {
        if ($q !== '') {
            $stmt = $pdo->prepare("SELECT * FROM libros WHERE titulo LIKE ? OR autor LIKE ? ORDER BY titulo");
            $stmt->execute(["%$q%", "%$q%"]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM libros ORDER BY titulo");
            $stmt->execute();
        }
        $filas = $stmt->fetchAll();

        $libros = [];
        foreach ($filas as $f) {
            $libros[] = new Libro($f['id'], $f['titulo'], $f['autor'], $f['categoria'], $f['imagen']);
        }
        return $libros;
    }

    public static function buscarPorId($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM libros WHERE id = ?");
        $stmt->execute([$id]);
        $f = $stmt->fetch();
        return $f ? new Libro($f['id'], $f['titulo'], $f['autor'], $f['categoria'], $f['imagen']) : null;
    }

    public static function crear($pdo, $titulo, $autor, $categoria, $imagen) {
        $stmt = $pdo->prepare(
            "INSERT INTO libros (titulo, autor, categoria, imagen) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$titulo, $autor, $categoria, $imagen]);
    }

    public static function actualizar($pdo, $id, $titulo, $autor, $categoria, $imagen) {
        $stmt = $pdo->prepare(
            "UPDATE libros SET titulo = ?, autor = ?, categoria = ?, imagen = ? WHERE id = ?"
        );
        return $stmt->execute([$titulo, $autor, $categoria, $imagen, $id]);
    }

    public static function eliminar($pdo, $id) {
        $stmt = $pdo->prepare("DELETE FROM libros WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** Cuántos préstamos tiene este libro ahora mismo (sin devolver). */
    public static function tienePrestamos($pdo, $id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM prestamos WHERE libro_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() > 0;
    }
}
