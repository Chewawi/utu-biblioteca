<?php

$host = '127.0.0.1';
$db   = 'biblioteca';
$user = 'root';
$pass = '';

$pdo = new PDO(
    "mysql:host=$host;dbname=$db;charset=utf8mb4",
    $user,
    $pass
);
    
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
