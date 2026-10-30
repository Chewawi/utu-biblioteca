<?php
// ============================================================
//  Modelo Usuario
//  Representa una cuenta con email, contraseña (hasheada) y un
//  rol ('admin' o 'socio').
// ============================================================

class Usuario {
    private $id;
    private $rol;
    private $email;
    private $pass;

    public function __construct($id, $rol, $email, $pass) {
        $this->id = $id;
        $this->rol = $rol;
        $this->email = $email;
        $this->pass = $pass;
    }

    // --- getters ---
    public function getId() { return $this->id; }
    public function getRol() { return $this->rol; }
    public function getEmail() { return $this->email; }
    public function getPass() { return $this->pass; }

    // --- acceso a datos ---

    /** Arma un Usuario con una fila de la tabla. */
    private static function desdeFila($f) {
        return new Usuario($f['id'], $f['rol'], $f['email'], $f['pass']);
    }

    public static function listar($pdo) {
        $usuarios = [];
        foreach ($pdo->query("SELECT * FROM usuarios ORDER BY email")->fetchAll() as $f) {
            $usuarios[] = self::desdeFila($f);
        }
        return $usuarios;
    }

    public static function buscarPorId($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $f = $stmt->fetch();
        return $f ? self::desdeFila($f) : null;
    }

    public static function buscarPorEmail($pdo, $email) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $f = $stmt->fetch();
        return $f ? self::desdeFila($f) : null;
    }

    public static function crear($pdo, $rol, $email, $pass) {
        $stmt = $pdo->prepare("INSERT INTO usuarios (rol, email, pass) VALUES (?, ?, ?)");
        $stmt->execute([$rol, $email, $pass]);
        return $pdo->lastInsertId();
    }

    public static function actualizar($pdo, $id, $rol, $email, $pass) {
        $stmt = $pdo->prepare("UPDATE usuarios SET rol = ?, email = ?, pass = ? WHERE id = ?");
        return $stmt->execute([$rol, $email, $pass, $id]);
    }

    public static function eliminar($pdo, $id) {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Usuarios con rol 'socio' que todavía no tienen un socio asociado, más el
     * $usuarioIdActual (el del socio que se está editando, si hay uno).
     * Se usa para el <select> del formulario de socio.
     */
    public static function listarDisponiblesParaSocio($pdo, $usuarioIdActual = null) {
        // LEFT JOIN: un usuario sin socio queda igual en el resultado, con s.id en NULL.
        $stmt = $pdo->prepare("
            SELECT u.*
            FROM usuarios u
            LEFT JOIN socios s ON s.usuario_id = u.id
            WHERE u.rol = 'socio' AND (s.id IS NULL OR u.id = ?)
            ORDER BY u.email
        ");
        $stmt->execute([$usuarioIdActual ?? 0]);

        $usuarios = [];
        foreach ($stmt->fetchAll() as $f) {
            $usuarios[] = self::desdeFila($f);
        }
        return $usuarios;
    }
}
