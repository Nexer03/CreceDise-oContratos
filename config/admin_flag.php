<?php
// Requiere: session_start() ya ejecutado
// Requiere: $pdo disponible (si no hay BD, simplemente no dará admin)

$isAdmin = 0;

if (isset($_SESSION['is_admin'])) {
    $isAdmin = (int)$_SESSION['is_admin'];
} elseif (isset($_SESSION['usuario_id']) && isset($pdo)) {
    try {
        $st = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = :id LIMIT 1");
        $st->execute([':id' => (int)$_SESSION['usuario_id']]);
        $isAdmin = (int)($st->fetchColumn() ?: 0);
        $_SESSION['is_admin'] = $isAdmin; // cache
    } catch (Throwable $e) {
        $isAdmin = 0;
    }
}