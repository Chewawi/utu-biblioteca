<?php
require_once "Usuario.php";

class Socio {
    private int $id;
    private Usuario $usuario;
    private string $nombre;
    private string $telefono;
    private string $direccion;

    public function __construct(int $id, Usuario $usuario, string $nombre, string $telefono, string $direccion) {
        $this->id = $id;
        $this->usuario = $usuario;
        $this->nombre = $nombre;
        $this->telefono = $telefono;
        $this->direccion = $direccion;
    } 

    // --- getters ---
    public function getId() { return $this->id; }
    public function getUsuario() { return $this->usuario; }
    public function getNombre() { return $this->nombre; }
    public function getTelefono() { return $this->telefono; }
    public function getDireccion() { return $this->direccion; }

     // --- acceso a datos ---

    public static function buscarPorId($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM socios WHERE id = ?");
        $stmt->execute([$id]);
        $f = $stmt->fetch();
        return $f ? new Socio($f['id'], $f['usuario_id'], $f['nombre'], $f['telefono'], $f['direccion']) : null;
    }

    public static function crear($pdo, $usuario_id, $nombre, $telefono, $direccion) {
        $stmt = $pdo->prepare(
            "INSERT INTO socios (usuario_id, nombre, telefono, direccion) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$usuario_id, $nombre, $telefono, $direccion]);
    }

    public static function eliminar($pdo, $id) {
        $stmt = $pdo->prepare("DELETE FROM socios WHERE id = ?");
        return $stmt->execute([$id]);
    }
}