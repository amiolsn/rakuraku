<?php
// 共通設定・認証・関数
require_once 'db.php';
require_once 'auth.php';
require_once 'functions.php';

try {
    $department_id = (int)$_SESSION['user']['department_id'];

    // ---------------------------
    // マスタ情報取得
    // ---------------------------
    $years           = getDepartmentYears($pdo, $department_id);
    $department_name = getDepartmentName($pdo, $department_id);
    $groups          = getStudentGroup($pdo, $department_id);

    // ---------------------------
    // 共通検索条件
    // ---------------------------
    $selected_grade = (isset($_GET['grade']) && $_GET['grade'] !== '')
        ? (int)$_GET['grade']
        : 1;

    // ---------------------------
    // 月間集計データ取得
    // ---------------------------
    $target_month = !empty($_GET['month'])
        ? $_GET['month']
        : date('Y-m');

    $monthly_summary = getMonthlyAttendanceSummary(
        $pdo,
        $department_id,
        $selected_grade,
        $target_month
    );

    // ---------------------------
    // 週間集計データ取得
    // ---------------------------
    $target_week_date = $_GET['date'] ?? date('Y-m-d');

    $week_dates = getWeekDates($pdo, $target_week_date);

    $date_only_list = array_column($week_dates, 'date');

    // 生徒一覧
    $students = getAttendanceStudents(
        $pdo,
        $department_id,
        $selected_grade
    );

    // 出欠データ
    $weekly_attendance = getWeeklyAttendance(
        $pdo,
        $date_only_list
    );

    // ---------------------------
    // 訓練全期間集計データ取得
    // ---------------------------

    // ここに全期間集計用のデータ取得処理を記述

} catch (PDOException $e) {
    die('データ取得エラー: ' . htmlspecialchars(
        $e->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    ));
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>らくらく出席簿 - 出席状況集計</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/tab.css">
    <link rel="stylesheet" href="css/summary.css">
</head>

<body>
    <?php require_once 'components/header.php'; ?>

    <div class="summary-container">

        <div class="summary-header">
            <h2>出席状況</h2>
        </div>
        <div class="summary-filter">

            <form action="" method="GET">

                <input type="hidden" name="tab" value="weekly">

                <!-- 学年選択 -->
                <select name="grade" onchange="this.form.submit()">
                    <?php include 'components/_grade_options.php'; ?>
                </select>

                <!-- 週選択 -->
                <div class="week-navigation">

                    <button type="button"
                        class="btn-nav"
                        onclick="moveWeek(-7)">◀ 前週</button>

                    <input type="date"
                        id="target-date-input"
                        name="date"
                        value="<?= htmlspecialchars($target_week_date, ENT_QUOTES, 'UTF-8') ?>"
                        onchange="this.form.submit()">

                    <button type="button"
                        class="btn-nav"
                        onclick="moveWeek(7)">次週 ▶</button>

                    <button type="button"
                        class="btn-nav btn-today"
                        onclick="goToday()">今週</button>

                </div>
            </form>
        </div>

        <!-- タブグループ -->
        <div class="tab-h" id="tab-h">

            <!-- タブメニュー -->
            <ul class="tab-contena">
                <li class="tab-elem" data-tabid="tab-monthly">
                    月間集計
                </li>

                <li class="tab-elem" data-tabid="tab-weekly">
                    週間集計
                </li>

                <li class="tab-elem" data-tabid="tab-total">
                    訓練全期間集計
                </li>
            </ul>

            <!-- タブコンテンツ -->
            <div class="tabbody-h" id="tabbody-h">

                <!-- =========================
                     月間集計
                ========================== -->
                <div class="tabbody-elem" id="tab-monthly">
                    <div class="table-container">
                        <?php include 'components/_summary_monthly.php'; ?>
                    </div>

                </div>


                <!-- =========================
                     週間集計
                ========================== -->
                <div class="tabbody-elem" id="tab-weekly">
                    <div class="table-container">
                        <?php include 'components/_summary_weekly.php'; ?>
                    </div>

                </div>


                <!-- =========================
                     訓練全期間集計
                ========================== -->
                <div class="tabbody-elem" id="tab-total">
                    <div class="table-container">
                        <?php include 'components/_summary_total.php'; ?>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <script src="js/tab.js" defer></script>
    <script src="js/summary-test.js" defer></script>

</body>

</html>