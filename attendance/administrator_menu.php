<?php
// データベース接続
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
    <link rel="stylesheet" href="css/admin-menu.css">

</head>

<body>
    <?php require_once 'components/header.php'; ?>

    <div class="admin-body">
        <div class="admin-menu">

            <!-- 学科管理 -->
            <a href="department_manage.php" class="admin-menu-item">
                <svg viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 21h18" />
                    <path d="M5 21V7l7-4 7 4v14" />
                    <path d="M9 9h.01M15 9h.01" />
                    <path d="M9 13h.01M15 13h.01" />
                    <path d="M10 21v-4h4v4" />
                </svg>
                <span>学科管理</span>
            </a>
            <!-- 学校カレンダー・特殊日程管理 -->
            <a href="school_calendar.php" class="admin-menu-item">
                <svg viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="5" width="18" height="16" rx="2" />
                    <path d="M16 3v4M8 3v4M3 10h18" />
                    <path d="m9 16 2 2 4-4" />
                </svg>
                <span>学校カレンダー・特殊日程管理</span>
            </a>
            <!-- 管理者管理 -->
            <a href="admin_manage.php" class="admin-menu-item">
                <svg viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="10" cy="7" r="4" />
                    <path d="M16 11l2 2 4-4" />
                </svg>
                <span>管理者管理</span>
            </a>
            <!-- 監査ログ -->
            <a href="audit_log.php" class="admin-menu-item">
                <svg viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <path d="M14 2v6h6" />
                    <path d="M8 13h8M8 17h8M8 9h2" />
                </svg>
                <span>監査ログ</span>
            </a>
        </div>
    </div>
</body>

</html>