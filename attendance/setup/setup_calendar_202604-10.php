<?php

/**
 * attendance/setup/setup_calendar_2026.php
 * 2026年4月〜10月の学校カレンダー（祝日・長期休暇・台風・時限変更）一括生成スクリプト
 */

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../functions.php';

try {
    $pdo->beginTransaction();

    echo "<h3>カレンダーデータ生成開始 (2026-04-01 〜 2026-10-31)</h3>";

    // 1. ベースカレンダー生成（土日・祝日・慰霊の日・平日7限を自動判定）
    $base_count = generateBaseSchoolCalendar($pdo, '2026-04-01', '2026-10-31', 7);
    echo "・ベースカレンダー生成完了: {$base_count} 日分<br>";

    // 期間指定で休校日を登録するヘルパー関数
    $set_range_holiday = function (PDO $pdo, string $start, string $end, string $desc) {
        $stmt = $pdo->prepare("
            UPDATE school_calendar
            SET is_holiday = 1, max_period = 0, description = :desc
            WHERE calendar_date BETWEEN :start AND :end
        ");
        $stmt->execute([':desc' => $desc, ':start' => $start, ':end' => $end]);
    };

    // 2. 長期休暇の設定
    $set_range_holiday($pdo, '2026-04-01', '2026-04-08', '春休み');
    $set_range_holiday($pdo, '2026-08-17', '2026-08-28', '夏休み');
    $set_range_holiday($pdo, '2026-09-28', '2026-09-30', '秋休み'); // 10/1は振替授業のため9/30まで

    // 3. 単日の特別休校・行事
    setSpecialCalendarDate($pdo, '2026-06-01', true, 0, '台風休校');
    setSpecialCalendarDate($pdo, '2026-06-02', true, 0, '台風休校（午後再開・暫定全日休校）');
    setSpecialCalendarDate($pdo, '2026-06-26', true, 0, '台風休校');
    setSpecialCalendarDate($pdo, '2026-07-10', true, 0, '調整日休校');
    setSpecialCalendarDate($pdo, '2026-08-07', true, 0, '台風休校');
    setSpecialCalendarDate($pdo, '2026-09-07', true, 0, '後期入校選考試験');
    setSpecialCalendarDate($pdo, '2026-10-22', true, 0, '一般入試');

    // 4. 台風振替授業（8時間授業）の設定（平日授業日のみ8限化）
    $updated_periods = updatePeriodForDateRange($pdo, '2026-06-15', '2026-07-13', 8, '台風振替授業');
    echo "・台風振替（8限化）設定完了: {$updated_periods} 日分<br>";

    // 5. 10月1日の振替授業設定（授業日・7限として登録）
    setSpecialCalendarDate($pdo, '2026-10-01', false, 7, '振替授業');

    $pdo->commit();
    echo "<p style='color: green; font-weight: bold;'>全カレンダーデータが正常に反映されました！</p>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<p style='color: red;'>エラー発生: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
}
