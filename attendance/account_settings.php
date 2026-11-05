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
            <form action="update_password.php" method="post" class="update-password">

                <!-- アカウント情報 -->
                <div class="account-info">
                    <div class="info-group">
                        <label>アカウント名</label>
                        <p class="info-value">
                            <?= htmlspecialchars($_SESSION['user']['user_name'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>

                    <div class="info-group">
                        <label>ログインID</label>
                        <p class="info-value">
                            <?= htmlspecialchars($_SESSION['user']['login_id'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>

                    <div class="info-group">
                        <label>ユーザー種別</label>
                        <p class="info-value">
                            <?= htmlspecialchars($_SESSION['user']['usertype_name'], ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <div class="info-group">
                            <label>所属学科</label>
                            <p class="info-value">
                                <?= htmlspecialchars($_SESSION['user']['department_id'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                    </div>

                    <!-- パスワード変更 -->
                    <div class="password-section">
                        <p class="section-title">パスワード変更</p>

                        <div class="new-password">
                            <label for="new-password">新しいパスワード</label>
                            <input
                                type="password"
                                id="new-password"
                                name="new-password"
                                required>

                            <label for="confirm-password">新しいパスワード（確認）</label>
                            <input
                                type="password"
                                id="confirm-password"
                                name="confirm-password"
                                required>

                            <p id="password-message"></p>
                        </div>

                        <input
                            type="button"
                            value="パスワード変更"
                            class="password-btn">
                    </div>

            </form>
        </main>
    </div>
    <script src="js/password_change.js"></script>
    <script src="js/password-visibility.js"></script>
</body>

</html>