<?php

require_once 'db.php';
require_once 'functions.php';

try {
    $pdo -> beginTransaction();

    echo "<h3>1. 2026年7月 ベースカレンダー生成（土日祝日の自動判定）</h3>";
    $count = generateBaseSchoolCalendar($pdo, '2026-07-01', '2-26-07-31', 7);
    echo "ベースカレンダー作成完了: {$count}日分<br>";

    echo "<h3>2. 台風振替対応7月1日＝7月13日（授業日）を8時間授,業に変更</h3>";
    $updated_periods = updatePeriodForDateRange($pdo, '2-26-07-01', '2026-07-13', 8, '台風授業振替(8h)');
    echo "時限変更適用: {$updated_periods}日分<br>";

    echo "<h3>3. 調整日設定: 7月10日を休校日に変更</h3>";
    setSpecialCalendarDate($pdo, '2026-07-10', true, 0, '調整日休校');
    echo "7月10日を休校日に設定完了<br>";

    $pdo -> commit();
    echo "<h2 style='color:green;'>カレンダーセットアップ完了</h2>";

    // 登録結果の確認表示
    $stmt = $pdo -> query("
        SELECT * FROM school_calendar
        WHERE calendar_date
            BETWEEN '2026-07-01'
            AND '2026-07-31'
        ORDER BY calendar_date ASC    
        ");
    $list = $stmt -> fetchAll() ;

    echo "<table border='1' cellpadding='4' style='border-collapse:collapse; text-align:center;'>";
    echo "<tr style='background:#f0f0f0;'><th>日付</th><th>曜日</th><th>休校フラグ</th><th>時限数</th><th>理由・摘要</th></tr>";
    $wName = ['日', '月', '火', '水', '木', '金', '土'];
    foreach ($list as $row) {
        $w  = (int)date('w', strtotime($row['calendar_date']));
        $bg = $row['is_holiday'] ? '#ffeef0' : '#ffffff';
        if ($row['max_period'] === 8) {
            $bg = '#eef6ff';
        }

        echo "<tr style='background:{$bg};'>";
        echo "<td>{$row['calendar_date']}</td>";
        echo "<td>{$wName[$w]}</td>";
        echo "<td>" . ($row['is_holiday'] ? '休校(1)' : '授業日(0)') . "</td>";
        echo "<td>{$row['max_period']}</td>";
        echo "<td>" . htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    if ($pdo -> inTransaction()) {
        $pdo -> rollBack();
    }
    die("<h3 style='color:red;'>エラー: " .htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') ."</h3>");
}
