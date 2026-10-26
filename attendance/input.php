<?php
// データベース接続
require_once 'db.php';
require_once 'auth.php';
require_once 'functions.php';

// 対象日付の決定
// URLから日付を取り出し、日付の形式が正しいかチェック
$target_date = isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])
    ? $_GET['date']     // 正しい日付ならその日付を
    : date('Y-m-d');    // なければサーバーの今日の日付

// 今日の日付かを判定
$is_today = ($target_date === date('Y-m-d'));

// 曜日の計算
$week_days = ['日', '月', '火', '水', '木', '金', '土'];
// 該当日の日付から曜日番号を取得（日=0, 月=1, …)
$target_w = $week_days[date('w', strtotime($target_date))];

try {
    $department_id = $_SESSION['user']['department_id'];

    // 班一覧の取得（重複なし）
    $groups = getStudentGroup($pdo, $department_id);

    // 生徒一覧の取得（学科名も結合して取得）
    $sql = "
        SELECT
            s.student_id,
            s.student_name,
            s.grade,
            s.student_group,
            d.department_name
        FROM students s
        LEFT OUTER JOIN department d
            ON s.department_id = d.department_id
        WHERE s.status = 1
        AND d.department_id = ?
    ";

    $params = [$department_id];

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

    $sql .= " ORDER BY s.student_id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $students = $stmt->fetchAll();


    // 科目一覧の取得
    $subjects = getSubjectsByDepartment($pdo, $department_id);

    // 教員一覧の取得
    $teachers = getTeachersByDepartment($pdo, $department_id);

    // カレンダー情報の取得
    $calendar_info = getCalendarInfo($pdo, $target_date);
    $is_holiday = $calendar_info['is_holiday'];
    $description = $calendar_info['description'];
    $max_period = $calendar_info['max_period'];

    // 修業年限の取得
    $years = getDepartmentYears($pdo, $department_id);

    // 出欠データおよび時限設定の取得
    $attendance_date = getAttendanceData($pdo, $target_date, $max_period);
    $existing_attendance = $attendance_date['existing_attendance'];
    $existing_periods = $attendance_date['existing_periods'];
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
</head>

<body>
    <!-- ヘッダー -->
    <?php require_once 'components/header.php'; ?>

    <div class="filter-controller">
        <!-- 日付選択コントローラー -->
        <?php require_once 'components/_date_controller.php' ?>

        <!-- 学年・班選択プルダウン -->
        <form action="" method="GET" class="period-header select">
            <select id="grade" name="grade" onchange="this.form.submit()">
                <?php
                $selected_grade = $_GET['grade'] ?? '';
                include 'components/_grade_options.php';
                ?>
            </select>

            <select id="student_group" name="student_group" onchange="this.form.submit()">
                <?php
                $selected_group = $_GET['student_group'] ?? '';
                include 'components/_group_options.php';
                ?>
            </select>
        </form>
    </div>

    <?php if ($is_holiday): ?>
        <div class="holiday-alert-banner">
            本日は「<?= $description ?>」のため休校日です。出欠の新規入力。保存はできません。
        </div>
    <?php else: ?>
        <p class="header-lead">入力または変更を行った時限の出欠のみが、右下の「確定して保存」で送信されます。</p>
    <?php endif; ?>

    <!-- 出欠入力テーブル -->
    <?php require_once 'components/_attendance_table.php'; ?>

    <!-- PHPデータをJavaScriptで使えるように定義 -->
    <script>
        const targetDate = <?= json_encode($target_date) ?>;
        const students = <?= json_encode($students) ?>;
        const max_period = <?= $max_period ?>;
    </script>

    <!-- 分割したJSファイルの読込 -->
    <script src="js/attendance.js"></script>

</body>

</html>