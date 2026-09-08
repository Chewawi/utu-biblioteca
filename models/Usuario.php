<?php 
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
    
    public function getId() { return $this->id; }
    public function getRol() { return $this->rol; }
    public function getEmail() { return $this->email; }
    public function getPass() { return $this->pass; }
   


    public static function crear($pdo, $rol, $email, $pass) {
        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (rol, email, pass) VALUES (?, ?, ?)"
        );
        return $stmt->execute([$rol, $email, $pass]);
    }

    public static function actualizar($pdo, $id, $rol, $email, $pass) {
        $stmt = $pdo->prepare(
            "UPDATE usuarios SET rol = ?, email = ?, pass = ? WHERE id = ?"
        );
        return $stmt->execute([$rol, $email, $pass, $id]);
    }

    public static function eliminar($pdo, $id) {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }

}