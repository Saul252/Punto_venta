<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function protegerPagina() {
    if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
        header("Location: /punto/login.php");
        exit;
    }
}
