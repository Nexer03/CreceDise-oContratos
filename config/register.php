<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre   = trim($_POST['nombre']);
    $correo   = trim($_POST['correo']);
    $telefono = trim($_POST['telefono'] ?? '');
    $ciudad   = trim($_POST['ciudad'] ?? '');
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {

        $check = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
        $check->execute([$correo]);

        if ($check->rowCount() > 0) {
            die("Este correo ya está registrado.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, correo, password, telefono, ciudad)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([$nombre, $correo, $password, $telefono, $ciudad]);

        $_SESSION['usuario_id'] = $pdo->lastInsertId();
        $_SESSION['usuario_nombre'] = $nombre;

        header("Location: index.php");
        exit;

    } catch (PDOException $e) {
        die("Error en registro: " . $e->getMessage());
    }
}
