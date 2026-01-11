<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['login'])) {
    echo json_encode(['ok' => false]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['carrito']) || empty($data['carrito'])) {
    echo json_encode(['ok' => false]);
    exit;
}

$_SESSION['carrito'] = $data['carrito'];
$_SESSION['total']   = $data['total'];

echo json_encode(['ok' => true]);
