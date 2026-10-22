<?php
// ============================================================
//  Modelo Item
//  Sabe hablar con la tabla "items". Un item es cualquier cosa
//  que se puede pedir prestada: un libro, un juego o un proyecto.
// ============================================================

class Item {
    // Tienen que coincidir con el ENUM de la columna "tipo" en database.sql.
    const TIPOS = ['libro', 'juego', 'proyecto'];

    private $id;
    private $tipo;
    private $titulo;
    private $autor;
    private $categoria;
    private $stock;
    private $imagen;

    public function __construct($id, $tipo, $titulo, $autor, $categoria, $stock, $imagen) {
        $this->id = $id;
        $this->tipo = $tipo;
        $this->titulo = $titulo;
        $this->autor = $autor;
        $this->categoria = $categoria;
        $this->stock = $stock;
        $this->imagen = $imagen;
    }

    // --- getters ---
    public function getId() { return $this->id; }
    public function getTipo() { return $this->tipo; }
    public function getTitulo() { return $this->titulo; }
    public function getAutor() { return $this->autor; }
    public function getCategoria() { return $this->categoria; }
    public function getStock() { return $this->stock; }
    public function getImagen() { return $this->imagen; }

    // --- acceso a datos ---

    /** Trae todos los items. Si $q no está vacío, filtra por título o autor. */
    public static function listar($pdo, $q = '') {
        if ($q !== '') {
            $stmt = $pdo->prepare("SELECT * FROM items WHERE titulo LIKE ? OR autor LIKE ? ORDER BY titulo");
            $stmt->execute(["%$q%", "%$q%"]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM items ORDER BY titulo");
            $stmt->execute();
        }
        $filas = $stmt->fetchAll();

        $items = [];
        foreach ($filas as $f) {
            $items[] = new Item($f['id'], $f['tipo'], $f['titulo'], $f['autor'], $f['categoria'], $f['stock'], $f['imagen']);
        }
        return $items;
    }

    public static function buscarPorId($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$id]);
        $f = $stmt->fetch();
        return $f ? new Item($f['id'], $f['tipo'], $f['titulo'], $f['autor'], $f['categoria'], $f['stock'], $f['imagen']) : null;
    }

    public static function crear($pdo, $tipo, $titulo, $autor, $categoria, $stock, $imagen) {
        $stmt = $pdo->prepare(
            "INSERT INTO items (tipo, titulo, autor, categoria, stock, imagen) VALUES (?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([$tipo, $titulo, $autor, $categoria, $stock, $imagen]);
    }

    public static function actualizar($pdo, $id, $tipo, $titulo, $autor, $categoria, $stock, $imagen) {
        $stmt = $pdo->prepare(
            "UPDATE items SET tipo = ?, titulo = ?, autor = ?, categoria = ?, stock = ?, imagen = ? WHERE id = ?"
        );
        return $stmt->execute([$tipo, $titulo, $autor, $categoria, $stock, $imagen, $id]);
    }

    public static function eliminar($pdo, $id) {
        $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** Cuántos préstamos tiene este item ahora mismo (sin devolver). */
    public static function tienePrestamos($pdo, $id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM prestamos WHERE item_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() > 0;
    }
}
