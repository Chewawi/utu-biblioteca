<?php
require_once __DIR__ . '/Usuario.php';

// ============================================================
//  Modelo Socio
//  Un socio es una persona que puede pedir items prestados.
//  Está enganchado a una cuenta de Usuario (para poder loguearse).
// ============================================================

class Socio {
    private $id;
    private $usuario; // objeto Usuario asociado
    private $nombre;
    private $direccion;
    private $telefono;

    public function __construct($id, $usuario, $nombre, $direccion, $telefono) {
        $this->id = $id;
        $this->usuario = $usuario;
        $this->nombre = $nombre;
        $this->direccion = $direccion;
        $this->telefono = $telefono;
    }

    // --- getters ---
    public function getId() { return $this->id; }
    public function getUsuario() { return $this->usuario; }
    public function getNombre() { return $this->nombre; }
    public function getDireccion() { return $this->direccion; }
    public function getTelefono() { return $this->telefono; }

    // --- acceso a datos ---

    /**
     * Todos los socios, cada uno con su Usuario. Se resuelve en una sola
     * consulta: el JOIN trae los datos de la cuenta junto con los del socio.
     * $filtros es un array con estas claves (opcionales; vacío = sin filtrar):
     *   q -> texto que se busca en el nombre, el teléfono o el email.
     */
    public static function listar($pdo, $filtros = []) {
        $filtros = array_merge(['q' => ''], $filtros);

        $condiciones = [];
        $parametros = [];
        if ($filtros['q'] !== '') {
            $condiciones[] = '(s.nombre LIKE ? OR s.telefono LIKE ? OR u.email LIKE ?)';
            $parametros[] = '%' . $filtros['q'] . '%';
            $parametros[] = '%' . $filtros['q'] . '%';
            $parametros[] = '%' . $filtros['q'] . '%';
        }

        $sql = "
            SELECT s.*, u.rol, u.email, u.pass
            FROM socios s
            INNER JOIN usuarios u ON s.usuario_id = u.id
        ";
        if (!empty($condiciones)) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }
        $sql .= ' ORDER BY s.nombre';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);
        $filas = $stmt->fetchAll();

        $socios = [];
        foreach ($filas as $f) {
            $usuario = new Usuario($f['usuario_id'], $f['rol'], $f['email'], $f['pass']);
            $socios[] = new Socio($f['id'], $usuario, $f['nombre'], $f['direccion'], $f['telefono']);
        }
        return $socios;
    }

    public static function buscarPorId($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM socios WHERE id = ?");
        $stmt->execute([$id]);
        $f = $stmt->fetch();
        if (!$f) {
            return null;
        }
        $usuario = Usuario::buscarPorId($pdo, $f['usuario_id']);
        return new Socio($f['id'], $usuario, $f['nombre'], $f['direccion'], $f['telefono']);
    }

    /** Busca el socio asociado a una cuenta de usuario (para "Mis préstamos"). */
    public static function buscarPorUsuarioId($pdo, $usuarioId) {
        $stmt = $pdo->prepare("SELECT * FROM socios WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);
        $f = $stmt->fetch();
        if (!$f) {
            return null;
        }
        $usuario = Usuario::buscarPorId($pdo, $f['usuario_id']);
        return new Socio($f['id'], $usuario, $f['nombre'], $f['direccion'], $f['telefono']);
    }

    public static function crear($pdo, $usuarioId, $nombre, $direccion, $telefono) {
        $stmt = $pdo->prepare(
            "INSERT INTO socios (usuario_id, nombre, direccion, telefono) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$usuarioId, $nombre, $direccion, $telefono]);
    }

    public static function actualizar($pdo, $id, $usuarioId, $nombre, $direccion, $telefono) {
        $stmt = $pdo->prepare(
            "UPDATE socios SET usuario_id = ?, nombre = ?, direccion = ?, telefono = ? WHERE id = ?"
        );
        return $stmt->execute([$usuarioId, $nombre, $direccion, $telefono, $id]);
    }

    public static function eliminar($pdo, $id) {
        $stmt = $pdo->prepare("DELETE FROM socios WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** true si el socio tiene algún préstamo activo. */
    public static function tienePrestamos($pdo, $id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM prestamos WHERE socio_id = ? AND estado = 'activo'");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() > 0;
    }
}
