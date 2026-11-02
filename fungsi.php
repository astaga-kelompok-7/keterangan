<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function wajib_login() {
    if (empty($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function wajib_admin() {
    wajib_login();

    if (($_SESSION['role'] ?? '') !== 'admin') {
        header("Location: dashboard.php");
        exit;
    }
}

function rupiah($angka) {
    return 'Rp' . number_format((float)$angka, 0, ',', '.');
}

function h($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}
?>