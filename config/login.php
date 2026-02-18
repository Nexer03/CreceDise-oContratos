<?php
session_start();
require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['correo'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        echo "<script>alert('Por favor complete todos los campos'); window.location.href='../index.php';</script>";
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, nombre, password FROM usuarios WHERE correo = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            $_SESSION['usuario_correo'] = $email; // Store email for display
            $_SESSION['flash_message'] = "¡Bienvenido de nuevo, " . $user['nombre'] . "!";
            $_SESSION['flash_type'] = "success";
            header("Location: ../index.php"); 
            exit;
        } else {
            echo "<script>alert('Correo o contraseña incorrectos'); window.location.href='../index.php';</script>";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
