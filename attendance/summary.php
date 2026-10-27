<?php
// 共通設定・認証・関数
require_once 'db.php';
require_once 'auth.php';
require_once 'functions.php';

try {
    $department_id = (int)$_SESSION['user']['department_id'];

    // マスタ情報取得
    $years = getDepartmentYears($pdo, $department_id);
    $department_name = getDepartmentName($pdo, $department_id);
    $groups = getStudentGroup($pdo, $department_id);

    // 現在のタブ判定
    $active_tab = $_GET['tab'] ?? 'monthly';
    if (!in_array($active_tab, ['monthly', 'weekly', 'total'], true)) {
        $active_tab = 'monthly';
    }

    // 選択学年(未選択時は１年)
    $selected_grade = (isset($_GET['grade']) && $_GET['grade'] !== '') ? (int)$_GET['grade'] : 1;

    // 週間集計用データの取得
    if ($active_tab === 'weekly') {
        $target_week_date = $_GET['date'] ?? date('Y-m-d');
        $week_dates = getWeekDates($target_week_date);
        $date_only_list = array_column($week_dates, 'date');

        // 生徒一覧の取得
        $students = getAttendanceStudents($pdo, $department_id, $selected_grade);

        // 出欠データの取得
        $weekly_attendance = getWeeklyAttendance($pdo, $date_only_list);
    }
} catch (PDOException $e) {
    die('データ取得エラー: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
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
    <!-- 共通ヘッダー -->
    <?php require_once 'components/header.php'; ?>

    <div class="summary-container">
        <div class="summary-header">
            <h2>出席状況</h2>

            <!-- タブに応じたコントローラー（学年・日付等） -->
            <form action="" method="GET" id="filter-form" class="summary-filter">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab, ENT_QUOTES, 'UTF-8') ?>">

                <!-- 学年選択プルダウン -->
                <select name="grade" onchange="this.form.submit()">
                    <?php
                    // components/_grade_options.phpを利用
                    include 'components/_grade_options.php';
                    ?>
                </select>

                <!-- 各タブ固有の操作パーツ（週カレンダーや年月プルダウン -->
                <?php if ($active_tab === 'weekly'): ?>
                    <?php
                    $target_week_date = $_GET['date'] ?? date('Y-m-d');
                    ?>
                    <input type="date" name="date" value="<?= htmlspecialchars($target_week_date, ENT_QUOTES, 'UTF-8') ?>" onchange="this.form.submit()">
                <?php elseif ($active_tab === 'monthly'): ?>
                    <?php
                    $target_month = $_GET['month'] ?? date('Y-m');
                    ?>
                    <input type="month" name="month" value="<?= htmlspecialchars($target_month, ENT_QUOTES, 'UTF-8') ?>" onchange="this.form.submit()">
                <?php endif; ?>
            </form>
        </div>

        <!-- タブメニュー -->
        <div class="tab-h">
            <ul class="tab-contena">
                <li class="tab-elem <?= $active_tab === 'monthly' ? 'is-active' : '' ?>">
                    <a href="summary.php?tab=monthly&grade=<?= $selected_grade ?>">月間集計</a>
                </li>
                <li class="tab-elem <?= $active_tab === 'weekly' ? 'is-active' : '' ?>">
                    <a href="summary.php?tab=weekly&grade=<?= $selected_grade ?>">週間集計</a>
                </li>
                <li class="tab-elem <?= $active_tab === 'total' ? 'is-active' : '' ?>">
                    <a href="summary.php?tab=monthly&grade=<?= $selected_grade ?>">訓練全期間集計</a>
                </li>
            </ul>

            <!-- タブコンテンツ領域 -->
            <div class="tabbody-h">
                <div class="table-container">
                    <?php
                    if ($active_tab === 'weekly') {
                        include 'components/_summary_weekly.php';
                    } elseif ($active_tab === 'total') {
                        include 'components/_summary_total.php';
                    } else {
                        include 'components/_summary_monthly.php';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>