<?php

$host = '127.0.0.1';
$db   = 'biblioteca';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('No se pudo conectar a la base de datos. Revisá que MySQL esté iniciado y que la base "biblioteca" exista (importá database.sql).');
}
