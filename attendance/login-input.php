<?php 
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

//ログイン認証成功後を想定
unset($_SESSION['user']);

    $pdo=new PDO('mysql:host=localhost;dbname=attendance_db;charset=utf8', 'staff', 'password');

    $sql=$pdo->prepare('
     SELECT 
        users.*, 
        usertype.usertype_name, 
        department.department_id
     FROM users 
     JOIN usertype 
        ON users.usertype_id = usertype.usertype_id
     JOIN department
        ON users.user_id = department.user_id
     WHERE users.login_id = ? AND users.password = ?
     ');

    $sql->execute([
    $_POST['login'] ?? '',
    $_POST['password'] ?? ''
    ]);

    $row = $sql->fetch(PDO::FETCH_ASSOC);

    
    if ($row) {
        $_SESSION['user'] = [
            'user_id' => $row['user_id'],
            'usertype_id' => $row['usertype_id'],
            'usertype_name' => $row['usertype_name'],
            'login_id' => $row['login_id'],
            'department_id' => $row['department_id']
    ];

    if ($row['usertype_id'] == 1) {
        header('Location: input.php');
        exit;
    } elseif ($row['usertype_id'] == 2) {
        echo '<script>
        alert("管理者TOPはまだ作成されていません。");
        location.href = "login-input.php";
        </script>';
        exit;
    } elseif ($row['usertype_id'] == 3) {
        echo '<script>
            alert("生徒TOP画面はまだ作成されていません。");
            location.href = "login-input.php";
        </script>';
        exit;
    }
    } else {
        $error = 'ログインIDまたはパスワードが正しくありません。';
    }
    }
    
?>


<?php
// データベース接続
require_once 'db.php';

?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>らくらく出席簿 - 出欠入力</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/login.css">
    
</head>

<body>
    <!-- アプリヘッダー＆ロゴマーク -->
    <header class="app-header">
        <div class="logo-wrapper">
            <!-- SVGロゴ：チャコールグレーのバインダー + 淡いピンクのチェックマーク -->
            <svg width="44" height="44" viewBox="0 0 48 48" fill="none" xmls="http://www.w3.org/2000/svg">
                <!-- バインダークリップ -->
                <rect x="18" y="4" width="12" height="6" rx="2" fill="#343a40" />
                <!-- バインダー本体（チャコールグレー） -->
                <rect x="8" y="8" width="32" height="36" rx="5" fill="#343a40" />
                <!-- 中の用紙（白） -->
                <rect x="12" y="14" width="24" height="26" rx="2" fill="#ffffff" />
                <!-- ノートの罫線 -->
                <line x1="16" y1="20" x2="24" y2="20" stroke="#dee2e6" stroke-width="2" stroke-linecap="round" />
                <line x1="16" y1="26" x2="28" y2="26" stroke="#dee2e6" stroke-width="2" stroke-linecap="round" />
                <!-- スムースチェックマーク（淡いピンク #F7CAD0、バインダーから心地よく飛び出す躍動感） -->
                <path d="M16 29L24 37L41 15" stroke="#F7CAD0" stroke-width="5" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>

            <!-- ロゴタイプ -->
            <div>
                <div class="logo-text-title"><span>らくらく</span>出席簿</div>
                <div class="logo-text-subtitle">Smart Attendance System</div>
            </div>
        </div>
    </header>
<div id="wrapper">
    <div id="today">
        <span>
            <?= date('Y/m/d') ?>
            （<?= ['日','月','火','水','木','金','土'][date('w')] ?>）
        </span>

        <svg xmlns="http://www.w3.org/2000/svg"
            width="24" height="24"
            viewBox="0 0 24 24"
            fill="none" stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round">
            <rect x="3" y="5" width="18" height="16" rx="2"/>
            <line x1="16" y1="3" x2="16" y2="7"/>
            <line x1="8" y1="3" x2="8" y2="7"/>
            <line x1="3" y1="11" x2="21" y2="11"/>
        </svg>
    </div>
    <main>
        <div id="title-flex">
            <h1>ログイン</h1>
            
        </div>
        <?php if (isset($error)): ?>
        <p class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
        <?php endif; ?>
        <form action="login-input.php" method="post">
            <p>ログインID<br><input type="text" name="login"></p><br>
            <p>パスワード<br><input type="password" name="password"></p><br>
            <input type="submit" value="ログイン" class="submit-btn">
        </form>
    </main>
    </div>
</body>

</html>