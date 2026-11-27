<?php
// データベース接続
require_once 'db.php';
require_once 'auth.php';
require_once 'functions.php';

// 初期表示するタブを取得
$activeTab = $_GET['tab'] ?? '';

if (!in_array($activeTab, ['department', 'admin'], true)) {
    $activeTab = '';
}

// URLで指定されていない場合は初期値を学科管理にする
$initialTab = $activeTab !== '' ? $activeTab : 'department';

$activeTabId = ($initialTab === 'admin')
    ? 'tab-page2'
    : 'tab-page1';


// ログインユーザーの権限を取得 //変更11/27
$is_admin = (int)$_SESSION['user']['is_admin'] === 1;

// ログインユーザーの所属学科ID
$user_department_id = (int)$_SESSION['user']['department_id']; //追加11/27

// 管理者はGETで選択した学科ID、未指定なら全学科 //追加11/27
$selected_department_id = null;

//もし管理者かつ学科を選択しているなら　//追加11/27
if ($is_admin && isset($_GET['department_id']) && $_GET['department_id'] !== '') {
    //selected_department_id(選択した学科ID)に選択した学科のIDを代入
    $selected_department_id = (int)$_GET['department_id'];
} elseif (!$is_admin) {
    //管理者でなければユーザーの学科IDを使用
    $selected_department_id = $user_department_id;
}

// 学科一覧を取得 //追加11/27
$stmt = $pdo->query("
    SELECT department_id, department_name
    FROM department
    ORDER BY department_id ASC
");
$department_options = $stmt->fetchAll();

// 管理者が選択した学科IDが実在するか確認 //変更11/27
if ($is_admin && $selected_department_id !== null) {
    $department_ids = array_column($department_options, 'department_id'); //変更11/27

    if (!in_array($selected_department_id, array_map('intval', $department_ids), true)) {
        $selected_department_id = null;
    }
}

// 検索関数に渡す学科ID //変更11/27
$department_id = $selected_department_id;

// 学科管理の検索条件 //変更11/27
$department_filters = [
    'department_number' => $_GET['department_id'] ?? '', //変更11/27
    'department_id' => $_GET['department_filter_id'] ?? '' //変更11/27
];

$department_results = searchDepartments($pdo, null, $department_filters);
// 検索条件の取得と教員一覧の取得
$admin_filters = [
    'teacher_id' => $_GET['teacher_id'] ?? '',
    'teacher_name' => $_GET['teacher_name'] ?? '',
    'login_id' => $_GET['login_id'] ?? ''
];

// 管理者管理で検索条件が指定されているか //追加11/27
$has_admin_search = (
    !empty($_GET['teacher_id']) ||
    !empty($_GET['teacher_name']) ||
    !empty($_GET['login_id']) ||
    !empty($_GET['department_id'])
);

$admins = searchAdmins($pdo, $department_id, $admin_filters);


$teacher_filters = [
    'teacher_id' => $_GET['add_teacher_id'] ?? '',
    'teacher_name' => $_GET['add_teacher_name'] ?? ''
];

$teachers = searchTeachersForAdmin($pdo, $teacher_filters);
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
    <link rel="stylesheet" href="css/admin-menu.css">
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

                </div>

                <!-- 管理者管理 -->
                <div class="tabbody-elem <?= $activeTabId === 'tab-page2' ? 'active' : '' ?>"
                    id="tab-page2">
                    <?php include 'components/_manage_admin.php'; ?>
                </div>

            </div>
        </div>
    </div>
    <?php include 'components/_delete_popup.php'; ?>

    <script src="js/common.js" defer></script>
    <script src="js/manage_department.js" defer></script>
    <script src="js/manage_admin.js" defer></script>
    <script src="js/tab.js" defer></script>
    <script src="js/delete-popup.js" defer></script>
    <script src="js/popup.js" defer></script>
    <script src="js/import_popup_csv.js" defer></script>
</body>

</html>