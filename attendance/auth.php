<?php
//セッションを開始し、ログイン状態を確認して未ログインの場合はログイン画面へ遷移させる認証処理
if (session_start() === PHP_SESSION_NONE) {
    session_start();
}

//ログインしていなければログイン画面へ
if (!isset($_SESSION['user'])) {
    header('Location: login-input.php');
    exit;
}
