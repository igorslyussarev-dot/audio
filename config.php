<?php
// Конфигурация подключения к PostgreSQL
$host = 'localhost';
$port = '5432';
$db = 'audiobag';
$user = 'postgres';
$pass = 'j3qq4h7h2v';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Ошибка подключения к PostgreSQL: " . $e->getMessage());
}
