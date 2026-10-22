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
    private $prestados; // ejemplares prestados ahora mismo (no viene de una columna, se cuenta)

    public function __construct($id, $tipo, $titulo, $autor, $categoria, $stock, $imagen, $prestados) {
        $this->id = $id;
        $this->tipo = $tipo;
        $this->titulo = $titulo;
        $this->autor = $autor;
        $this->categoria = $categoria;
        $this->stock = $stock;
        $this->imagen = $imagen;
        $this->prestados = $prestados;
    }

    // --- getters ---
    public function getId() { return $this->id; }
    public function getTipo() { return $this->tipo; }
    public function getTitulo() { return $this->titulo; }
    public function getAutor() { return $this->autor; }
    public function getCategoria() { return $this->categoria; }
    public function getStock() { return $this->stock; }
    public function getImagen() { return $this->imagen; }
    public function getPrestados() { return $this->prestados; }
    public function getDisponibles() { return $this->stock - $this->prestados; }

    // --- acceso a datos ---

    /*
     * Las consultas de lectura traen además la columna "prestados": una
     * subconsulta que cuenta los préstamos activos de cada item.
     */

    /** Trae todos los items. Si $q no está vacío, filtra por título o autor. */
    public static function listar($pdo, $q = '') {
        if ($q !== '') {
            $stmt = $pdo->prepare("
                SELECT i.*,
                       (SELECT COUNT(*) FROM prestamos p WHERE p.item_id = i.id AND p.estado = 'activo') AS prestados
                FROM items i
                WHERE i.titulo LIKE ? OR i.autor LIKE ?
                ORDER BY i.titulo
            ");
            $stmt->execute(["%$q%", "%$q%"]);
        } else {
            $stmt = $pdo->prepare("
                SELECT i.*,
                       (SELECT COUNT(*) FROM prestamos p WHERE p.item_id = i.id AND p.estado = 'activo') AS prestados
                FROM items i
                ORDER BY i.titulo
            ");
            $stmt->execute();
        }
        $filas = $stmt->fetchAll();

        $items = [];
        foreach ($filas as $f) {
            $items[] = new Item($f['id'], $f['tipo'], $f['titulo'], $f['autor'], $f['categoria'], $f['stock'], $f['imagen'], $f['prestados']);
        }
        return $items;
    }

    public static function buscarPorId($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT i.*,
                   (SELECT COUNT(*) FROM prestamos p WHERE p.item_id = i.id AND p.estado = 'activo') AS prestados
            FROM items i
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        $f = $stmt->fetch();
        return $f ? new Item($f['id'], $f['tipo'], $f['titulo'], $f['autor'], $f['categoria'], $f['stock'], $f['imagen'], $f['prestados']) : null;
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
}
