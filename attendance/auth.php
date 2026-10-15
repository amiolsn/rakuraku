<?php 
if(session_start() === PHP_SESSION_NONE) {
    session_start();
}

//ログインしていなければログイン画面へ
if(!isset($_SESSION['user'])) {
    header('Location: login-input.php');
    exit;
}
?>