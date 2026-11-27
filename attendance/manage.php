<?php
// データベース接続
require_once 'db.php';
require_once 'auth.php';
require_once 'functions.php';

try {
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
    $departments = $stmt->fetchAll();

    // 管理者が選択した学科IDが実在するか確認　//追加11/27
    if ($is_admin && $selected_department_id !== null) {
        $department_ids = array_column($departments, 'department_id');

        if (!in_array($selected_department_id, array_map('intval', $department_ids), true)) {
            $selected_department_id = null;
        }
    }

    // 検索関数に渡す学科ID //変更11/27
    $department_id = $selected_department_id;

    //もしdepartment_idがnullなら（管理者で全学科を表示）
    if ($department_id !== null) { //追加11/27
        // 修業年限の取得
        $years = getDepartmentYears($pdo, $department_id);
        // 班一覧の取得（重複なし）
        $groups = getStudentGroup($pdo, $department_id);
        // ログイン中教員の学科名を取得
        $department_name = getDepartmentName($pdo, $department_id);
    } else { //追加11/27
        $years = 0;
        $groups = [];
        $department_name = '全学科';
    }

    // 検索条件の取得と生徒一覧の取得
    $student_filters = [
        'student_id' => $_GET['student_id'] ?? '',
        'student_name' => $_GET['student_name'] ?? '',
        'grade' => $_GET['grade'] ?? '',
        'student_group' => $_GET['student_group'] ?? '',
        'status' => $_GET['status'] ?? '1',
    ];
    $students = searchStudents($pdo, $department_id, $student_filters);

    // 検索条件の取得と教員一覧の取得
    $teacher_filters = [
        'teacher_status' => $_GET['teacher_status'] ?? '1',
        'teacher_id' => $_GET['teacher_id'] ?? '',
        'teacher_name' => $_GET['teacher_name'] ?? ''
    ];
    $teachers = searchTeachers($pdo, $department_id, $teacher_filters);

    // 検索条件の取得と科目一覧の取得
    //変更：科目検索条件
    $subject_filters = [
        'subject_status' => $_GET['subject_status'] ?? '1',
        'subject_id' => $_GET['subject_id'] ?? '',
        'subject_name' => $_GET['subject_name'] ?? ''
    ];

    //変更：科目一覧取得
    $subjects = searchSubjects($pdo, $department_id, $subject_filters);
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

                    <?php include 'components/_manage_teachers.php'; ?>
                </div>

                <div class="tabbody-elem" id="tab-page3">

                    <?php include 'components/_manage_subjects.php'; ?>
                </div>

            </div>
        </div>
    </div>

    <?php include 'components/_delete_popup.php'; ?>
    <!-- <script src="js/nav.js"></script> -->
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