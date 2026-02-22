<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

// Auth check
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

// Admin check
$isAdmin = 0;
if (isset($_SESSION['is_admin'])) {
    $isAdmin = (int)$_SESSION['is_admin'];
} else {
    try {
        $stAdmin = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = :id LIMIT 1");
        $stAdmin->execute([':id' => (int)$_SESSION['usuario_id']]);
        $rowAdmin = $stAdmin->fetch(PDO::FETCH_ASSOC);
        $isAdmin = (int)($rowAdmin['is_admin'] ?? 0);
        $_SESSION['is_admin'] = $isAdmin;
    } catch (PDOException $e) {
        $isAdmin = 0;
    }
}

if (empty($isAdmin)) {
    http_response_code(403);
    die("Acceso denegado.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = (int)($_POST['user_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($userId <= 0 || empty($nombre) || empty($correo)) {
        $_SESSION['flash_message'] = "ID, Nombre y Correo son obligatorios.";
        $_SESSION['flash_type'] = "error";
        header("Location: ../admin_analitica.php");
        exit;
    }

    try {
        $sql = "UPDATE usuarios SET nombre = :nombre, correo = :correo, telefono = :telefono, ciudad = :ciudad";
        $params = [
            ':nombre' => $nombre,
            ':correo' => $correo,
            ':telefono' => $telefono,
            ':ciudad' => $ciudad,
            ':id' => $userId
        ];
        
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql .= ", password = :password";
            $params[':password'] = $hashed_password;
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($params)) {
            $_SESSION['flash_message'] = "Datos de usuario actualizados correctamente.";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Error al actualizar los datos.";
            $_SESSION['flash_type'] = "error";
        }
    } catch (PDOException $e) {
        // Check for duplicate email error
        if ($e->getCode() == 23000) {
           $_SESSION['flash_message'] = "Ese correo electrónico ya está registrado por otro usuario.";
           $_SESSION['flash_type'] = "error";
        } else {
           $_SESSION['flash_message'] = "Error en la base de datos: " . $e->getMessage();
           $_SESSION['flash_type'] = "error";
        }
    }

    header("Location: ../admin_analitica.php");
    exit;
} else {
    // If not POST, redirect
    header("Location: ../admin_analitica.php");
    exit;
}
?>
