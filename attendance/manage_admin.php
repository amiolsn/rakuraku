<?php
// データベース接続
require_once 'db.php';
require_once 'auth.php';
require_once 'functions.php';

// 初期表示するタブを取得
$activeTab = $_GET['tab'] ?? 'department';

// 不正なタブ指定を防止
if (!in_array($activeTab, ['department', 'admin'], true)) {
    $activeTab = 'department';
}

// タブIDに変換
$activeTabId = ($activeTab === 'admin') ? 'tab-page2' : 'tab-page1';

// 検索条件の取得と生徒一覧の取得
$department_filters = [
    'department_id' => $_GET['department_id'] ?? '',
    'user_id' => $_GET['user_id'] ?? '',
    'department_name' => $_GET['department_name'] ?? ''
];
$departments = searchDepartments($pdo, $department_filters);
// プルダウン用（全学科）
$allDepartments = searchDepartments($pdo);
?>


<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>らくらく出席簿 - 学科・管理者管理</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/tab.css">
    <link rel="stylesheet" href="css/popup.css">
    <link rel="stylesheet" href="css/delete-popup.css">
    <link rel="stylesheet" href="css/import-popup.css">
</head>

<body data-page="manage-admin">
    <?php require_once 'components/header.php'; ?>

    <!-- タブグループ -->
    <div class="tab-h" id="tab-h">

        <!-- タブメニュー -->
        <ul class="tab-contena">
            <li class="tab-elem <?= $activeTabId === 'tab-page1' ? 'active' : '' ?>"
                data-tabid="tab-page1">
                学科管理
            </li>

            <li class="tab-elem <?= $activeTabId === 'tab-page2' ? 'active' : '' ?>"
                data-tabid="tab-page2">
                管理者管理
            </li>
        </ul>

        <!-- タブ内コンテンツ -->
        <div class="tabbody-h" id="tabbody-h">
            <div class="table-container">

                <!-- 学科管理 -->
                <div class="tabbody-elem <?= $activeTabId === 'tab-page1' ? 'active' : '' ?>"
                    id="tab-page1">
                    <?php include 'components/_manage_departments.php'; ?>
                    <?php include 'components/_delete_popup.php'; ?>
                </div>

                <!-- 管理者管理 -->
                <div class="tabbody-elem <?= $activeTabId === 'tab-page2' ? 'active' : '' ?>"
                    id="tab-page2">
                    <?php include 'components/_manage_admin.php'; ?>
                </div>

            </div>
        </div>
    </div>

    <script src="js/common.js" defer></script>
    <script src="js/manage_department.js" defer></script>
    <script src="js/tab.js" defer></script>
    <script src="js/delete-popup.js" defer></script>
    <script src="js/popup.js" defer></script>
    <script src="js/import_popup_csv.js" defer></script>
</body>

</html>