
<?php
session_start();

// POST以外からのアクセスを拒否
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login-input.php');
    exit;
}

// ログインしていない場合
if (!isset($_SESSION['user'])) {
    header('Location: login-input.php');
    exit;
}

// セッション情報を破棄
$_SESSION = [];
session_destroy();

// ログイン画面へ移動
header('Location: login-input.php');
exit;
?>