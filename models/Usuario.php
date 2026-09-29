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

    public static function listar($pdo) {
        $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY email");
        $filas = $stmt->fetchAll();

        $usuarios = [];
        foreach ($filas as $f) {
            $usuarios[] = new Usuario($f['id'], $f['rol'], $f['email'], $f['pass']);
        }
        return $usuarios;
    }

    public static function buscarPorId($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $f = $stmt->fetch();
        return $f ? new Usuario($f['id'], $f['rol'], $f['email'], $f['pass']) : null;
    }

    public static function buscarPorEmail($pdo, $email) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $f = $stmt->fetch();
        return $f ? new Usuario($f['id'], $f['rol'], $f['email'], $f['pass']) : null;
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
     * Usuarios con rol 'socio' que todavía no tienen un socio asociado.
     * Se usa para el <select> del formulario de alta de socio.
     */
    public static function listarDisponiblesParaSocio($pdo, $usuarioIdActual = null) {
        // Traemos todos los usuarios con rol 'socio'...
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE rol = 'socio' ORDER BY email");
        $stmt->execute();
        $filas = $stmt->fetchAll();

        // ...y los ids de usuario que YA tienen un socio, para descartarlos.
        $stmt2 = $pdo->query("SELECT usuario_id FROM socios");
        $yaUsados = $stmt2->fetchAll(PDO::FETCH_COLUMN);

        $usuarios = [];
        foreach ($filas as $f) {
            $yaTieneSocio = in_array($f['id'], $yaUsados);
            $esElMismoQueEstamosEditando = $f['id'] == $usuarioIdActual;
            if (!$yaTieneSocio || $esElMismoQueEstamosEditando) {
                $usuarios[] = new Usuario($f['id'], $f['rol'], $f['email'], $f['pass']);
            }
        }
        return $usuarios;
    }
}
