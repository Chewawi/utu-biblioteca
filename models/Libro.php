<?php

class Libro {
    private $id;
    private $titulo;
    private $autor;
    private $imagen;

    public function __construct($id, $titulo, $autor, $imagen) {
        $this->id = $id;
        $this->titulo = $titulo;
        $this->autor = $autor;
        $this->imagen = $imagen;
    }

    public function getId() {
        return $this->id;
    }

    public function getTitulo() {
        return $this->titulo;
    }

    public function getAutor() {
        return $this->autor;
    }

    public function getimagen() {
        return $this->imagen;
    }

    public static function listar($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM libros ORDER BY titulo");
        $stmt->execute();
        $filas = $stmt->fetchAll();

        $libros = [];
        foreach ($filas as $f) {
            $libros[] = new Libro($f['id'], $f['titulo'], $f['autor'], $f['imagen']);
        }
        return $libros;
    }

    public static function crear($pdo, $titulo, $autor, $imagen) {
        $stmt = $pdo->prepare(
            "INSERT INTO libros (titulo, autor, imagen) VALUES (?, ?, ?)"
        );
        return $stmt->execute([$titulo, $autor, $imagen]);
    }

    public static function actualizar($pdo, $id, $titulo, $autor, $imagen) {
        $stmt = $pdo->prepare(
            "UPDATE libros SET titulo=?, autor=?, imagen=? WHERE id=?"
        );
        return $stmt->execute([$titulo, $autor, $imagen, $id]);
    }

    public static function eliminar($pdo, $id) {
        $stmt = $pdo->prepare("DELETE FROM libros WHERE id=?");
        return $stmt->execute([$id]);
    }

    public static function buscarPorId($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM libros WHERE id=?");
        $stmt->execute([$id]);
        $f = $stmt->fetch();
        return $f ? new Libro($f['id'], $f['titulo'], $f['autor'], $f['imagen']) : null;
    }
}