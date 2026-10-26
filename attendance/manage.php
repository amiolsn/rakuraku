<?php
// データベース接続
require_once 'db.php';
require_once 'auth.php';
require_once 'functions.php';

try {
    $department_id = $_SESSION['user']['department_id'];

    // 生徒一覧の取得（学科名も結合して取得）
    $sql = "
        SELECT
            s.student_id,
            s.student_name,
            s.grade,
            s.student_group,
            s.status,
            d.department_name
        FROM students s
        JOIN department d
            ON s.department_id = d.department_id
        WHERE d.department_id = ?
    ";

    $params = [$department_id];

    // 学籍番号が検索欄に入力されている場合だけ条件を追加
    if (isset($_GET['student_id']) && $_GET['student_id'] !== '') {
        $sql .= " AND s.student_id = ?";
        $params[] = (int)$_GET['student_id'];
    }

    // 生徒氏名が検索欄に入力されている場合だけ条件を追加
    if (isset($_GET['student_name']) && $_GET['student_name'] !== '') {
        $sql .= " AND s.student_name LIKE ?";
        $params[] = '%' . $_GET['student_name'] . '%';
    }

    // 学年が選択されている場合だけ条件を追加
    if (isset($_GET['grade']) && $_GET['grade'] !== '') {
        $sql .= " AND grade = ?";
        $params[] = (int)$_GET['grade'];
    }

    // 班が選択されている場合だけ条件を追加
    if (isset($_GET['student_group']) && $_GET['student_group'] !== '') {
        $sql .= " AND s.student_group = ?";
        $params[] = $_GET['student_group'];
    }

    // 班が選択されている場合だけ条件を追加
    if (isset($_GET['student_group']) && $_GET['student_group'] !== '') {
        $sql .= " AND s.student_group = ?";
        $params[] = $_GET['student_group'];
    }


    // 班が選択されている場合だけ条件を追加
    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $sql .= " AND s.status= ?";
        $params[] = $_GET['status'];
    }


    $sql .= " ORDER BY s.student_id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $students = $stmt->fetchAll();

    // 科目一覧の取得
    $subjects = getSubjectsByDepartment($pdo, $department_id);

    // 教員一覧の取得
    $teachers = getTeachersByDepartment($pdo, $department_id);

    // 修業年限の取得
    $years = getDepartmentYears($pdo, $department_id);

    // ログイン中の教員の学科名を取得
    $stmtDepartment = $pdo->prepare("
        SELECT department_name
        FROM department
        WHERE department_id = ?
    ");

    $stmtDepartment->execute([$department_id]);

    $departmentName = $stmtDepartment->fetchColumn();

    // 班一覧の取得（重複なし）
    $groups = getStudentGroup($pdo, $department_id);
} catch (PDOException $e) {
    die('データ取得エラー: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>らくらく出席簿 - 出欠入力</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/tab.css">
    <link rel="stylesheet" href="css/popup.css">
    <link rel="stylesheet" href="css/delete-popup.css">
    <link rel="stylesheet" href="css/import-popup.css">

</head>

<body>
    <?php require_once 'components/header.php'; ?>


    <!-- タブグループ -->
    <div class="tab-h" id="tab-h">
        <!-- タブメニュー -->
        <ul class="tab-contena">
            <li class="tab-elem" data-tabid="tab-page1">生徒管理</li>
            <li class="tab-elem" data-tabid="tab-page2">教員管理</li>
            <li class="tab-elem" data-tabid="tab-page3">科目管理</li>
        </ul>
        <!-- タブ内コンテンツ  ※コンテンツは自由に編集可能 -->
        <div class="tabbody-h" id="tabbody-h">
            <div class="table-container">
                <div class="tabbody-elem" id="tab-page1">
                    <?php include 'components/_manage_students.php'; ?>
                </div>
                <div class="tabbody-elem" id="tab-page2">
                    <!-- 未作成 -->
                    <?php include 'components/_manage_teachers.php'; ?>
                </div>
                <div class="tabbody-elem" id="tab-page3">
                    <!-- 未作成 -->
                    <?php include 'components/_manage_subjects.php'; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="js/common.js" defer></script>
    <script src="js/manage.js" defer></script>
    <script src="js/manage_teacher.js" defer></script>
    <script src="js/manage_subject.js" defer></script>
    <script src="js/tab.js" defer></script>
    <script src="js/popup.js" defer></script>
    <script src="js/delete-popup.js" defer></script>
    <script src="js/import_popup_csv.js" defer></script>
</body>

</html>