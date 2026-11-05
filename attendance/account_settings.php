<?php
// 共通設定の読込
require_once 'db.php';
require_once 'auth.php';
require_once 'functions.php';
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
    <?php require_once 'components/header.php'; ?>
    <div id="wrapper">
        <div id="today">
            <span>
                <?= date('Y/m/d') ?>
                （<?= ['日', '月', '火', '水', '木', '金', '土'][date('w')] ?>）
            </span>

            <svg xmlns="http://www.w3.org/2000/svg"
                width="24" height="24"
                viewBox="0 0 24 24"
                fill="none" stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round">
                <rect x="3" y="5" width="18" height="16" rx="2" />
                <line x1="16" y1="3" x2="16" y2="7" />
                <line x1="8" y1="3" x2="8" y2="7" />
                <line x1="3" y1="11" x2="21" y2="11" />
            </svg>
        </div>
        <main>
            <div id="title-flex">
                <h1>アカウント管理</h1>

            </div>
            <?php if (isset($error)): ?>
                <p class="error">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>
            <form action="" method="post">
                <p>アカウント名</p><br>
                <p id="current-password"><?= htmlspecialchars($_SESSION['user']['user_name'], ENT_QUOTES, 'UTF-8') ?></p>
                <p>ログインID<br></p><br>
                <p id="current-password"><?= htmlspecialchars($_SESSION['user']['login_id'], ENT_QUOTES, 'UTF-8') ?></p>
                <!-- 隠したい👀 -->
                <p>現在のパスワード</p><br>
                <p id="current-password"><?= htmlspecialchars($_SESSION['user']['password'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="new-password">
                    新しいパスワード<br>
                    <input type="password" name="password">
                </p>

                <input type="button" value="パスワード変更" class="passowrd-btn">
            </form>
        </main>
    </div>
    <script src="js/password_change.js"></script>
    <script src="js/password-visibility.js"></script>
</body>

</html>