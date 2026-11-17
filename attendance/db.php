<?php

// データベース接続共通設定

date_default_timezone_set('Asia/Tokyo');

$db_host = 'localhost';
$db_name = 'attendance_db';
$db_user = 'staff';
$db_pass = 'password';

$dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

$option = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // エラー時に例外をスロー
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // 連想配列で取得
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $option);
} catch (PDOException $e) {
    die('データベース接続失敗: ' .htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
