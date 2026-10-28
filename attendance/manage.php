<?php
// データベース接続
require_once 'db.php';
require_once 'auth.php';
require_once 'functions.php';

try {
    $department_id = (int)$_SESSION['user']['department_id'];

    // 科目一覧の取得
    $subjects = getSubjectsByDepartment($pdo, $department_id);
    // 教員一覧の取得
    $teachers = getTeachersByDepartment($pdo, $department_id);
    // 修業年限の取得
    $years = getDepartmentYears($pdo, $department_id);
    // 班一覧の取得（重複なし）
    $groups = getStudentGroup($pdo, $department_id);
    // ログイン中教員の学科名を取得
    $department_name = getDepartmentName($pdo, $department_id);

    // 検索条件の取得と生徒一覧の取得
    $student_filters = [
        'student_id' => $_GET['student_id'] ?? '',
        'student_name' => $_GET['student_name'] ?? '',
        'grade' => $_GET['grade'] ?? '',
        'student_group' => $_GET['student_group'] ?? '',
        'status' => $_GET['status'] ?? '',
    ];
    $students = searchStudents($pdo, $department_id, $student_filters);

    // 検索条件の取得と生徒一覧の取得
    $teacher_filters = [
        'teacher_id' => $_GET['teacher_id'] ?? '',
        'teacher_name' => $_GET['teacher_name'] ?? ''
    ];
    $teachers = searchTeachers($pdo, $department_id, $teacher_filters);
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
                <?php include 'components/_import_popup.php'; ?>

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

    <?php include 'components/_delete_popup.php'; ?>

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