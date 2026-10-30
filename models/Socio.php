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
    private $foto;

    public function __construct($id, $usuario, $nombre, $direccion, $telefono, $foto) {
        $this->id = $id;
        $this->usuario = $usuario;
        $this->nombre = $nombre;
        $this->direccion = $direccion;
        $this->telefono = $telefono;
        $this->foto = $foto;
    }

    // --- getters ---
    public function getId() { return $this->id; }
    public function getUsuario() { return $this->usuario; }
    public function getNombre() { return $this->nombre; }
    public function getDireccion() { return $this->direccion; }
    public function getTelefono() { return $this->telefono; }
    public function getFoto() { return $this->foto; }

    // --- acceso a datos ---

    /**
     * Todos los socios, cada uno con su Usuario. Se resuelve en una sola
     * consulta: el JOIN trae los datos de la cuenta junto con los del socio.
     * $filtros es un array con estas claves (todas opcionales):
     *   q          -> texto que se busca en el nombre, el teléfono o el email,
     *   pagina     -> número de página (si está fuera de rango se corrige),
     *   por_pagina -> cuántos por página; 0 o sin poner = todos, sin paginar.
     * Devuelve ['filas' => socios de esa página, 'total' => cantidad de resultados,
     *           'pagina' => página que se muestra, 'paginas' => cantidad de páginas].
     */
    public static function listar($pdo, $filtros = []) {
        $filtros = array_merge(['q' => '', 'pagina' => 1, 'por_pagina' => 0], $filtros);

        $condiciones = [];
        $parametros = [];
        if ($filtros['q'] !== '') {
            $condiciones[] = '(s.nombre LIKE ? OR s.telefono LIKE ? OR u.email LIKE ?)';
            $parametros[] = '%' . $filtros['q'] . '%';
            $parametros[] = '%' . $filtros['q'] . '%';
            $parametros[] = '%' . $filtros['q'] . '%';
        }

        // El FROM y el WHERE son los mismos para contar y para traer las filas.
        $desdeDonde = ' FROM socios s INNER JOIN usuarios u ON s.usuario_id = u.id';
        if (!empty($condiciones)) {
            $desdeDonde .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $stmt = $pdo->prepare('SELECT COUNT(*)' . $desdeDonde);
        $stmt->execute($parametros);
        $total = (int) $stmt->fetchColumn();

        $sql = 'SELECT s.*, u.rol, u.email, u.pass' . $desdeDonde . ' ORDER BY s.nombre';

        $paginacion = calcularPaginacion($total, $filtros['pagina'], $filtros['por_pagina']);
        $sql .= $paginacion['limite'];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);
        $filas = $stmt->fetchAll();

        $socios = [];
        foreach ($filas as $f) {
            $usuario = new Usuario($f['usuario_id'], $f['rol'], $f['email'], $f['pass']);
            $socios[] = new Socio($f['id'], $usuario, $f['nombre'], $f['direccion'], $f['telefono'], $f['foto']);
        }
        return ['filas' => $socios, 'total' => $total, 'pagina' => $paginacion['pagina'], 'paginas' => $paginacion['paginas']];
    }

    /** Todos los socios como [id => nombre], para los desplegables de los filtros. */
    public static function nombres($pdo) {
        return $pdo->query('SELECT id, nombre FROM socios ORDER BY nombre')->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public static function buscarPorId($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM socios WHERE id = ?");
        $stmt->execute([$id]);
        $f = $stmt->fetch();
        if (!$f) {
            return null;
        }
        $usuario = Usuario::buscarPorId($pdo, $f['usuario_id']);
        return new Socio($f['id'], $usuario, $f['nombre'], $f['direccion'], $f['telefono'], $f['foto']);
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
        return new Socio($f['id'], $usuario, $f['nombre'], $f['direccion'], $f['telefono'], $f['foto']);
    }

    public static function crear($pdo, $usuarioId, $nombre, $direccion, $telefono, $foto) {
        $stmt = $pdo->prepare(
            "INSERT INTO socios (usuario_id, nombre, direccion, telefono, foto) VALUES (?, ?, ?, ?, ?)"
        );
        return $stmt->execute([$usuarioId, $nombre, $direccion, $telefono, $foto]);
    }

    public static function actualizar($pdo, $id, $usuarioId, $nombre, $direccion, $telefono, $foto) {
        $stmt = $pdo->prepare(
            "UPDATE socios SET usuario_id = ?, nombre = ?, direccion = ?, telefono = ?, foto = ? WHERE id = ?"
        );
        return $stmt->execute([$usuarioId, $nombre, $direccion, $telefono, $foto, $id]);
    }

    public static function eliminar($pdo, $id) {
        $stmt = $pdo->prepare("DELETE FROM socios WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** true si el socio tiene préstamos o reservas, aunque sean viejos (historial). */
    public static function tieneHistorial($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT (SELECT COUNT(*) FROM prestamos WHERE socio_id = ?)
                 + (SELECT COUNT(*) FROM reservas WHERE socio_id = ?)
        ");
        $stmt->execute([$id, $id]);
        return $stmt->fetchColumn() > 0;
    }
}
