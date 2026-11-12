<?php

/**
 * 2025年年度・2026年度 学校カレンダー（祝日・長期休暇・台風・時限変更）一括作成
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

try {
    $pdo->beginTransaction();

    echo "<h3>学校カレンダー一括セットアップ（2025年度・2026年度）</h3>";

    // 期間指定で休校日を登録するヘルパー関数
    $set_range_holiday = function (PDO $pdo, string $start, string $end, string $desc) {
        $stmt = $pdo->prepare("
            UPDATE school_calendar
            SET is_holiday = 1, max_period = 0, description = :desc
            WHERE calendar_date BETWEEN :start AND :end
        ");
        $stmt->execute([':desc' => $desc, ':start' => $start, ':end' => $end]);
    };

    // 【フェーズ1】2025年度 カレンダー登録 (2025-04-01 ～ 2026-03-31)

    echo "<h4>■ 2025年度 カレンダー登録/h4>";

    // 1-1. ベースカレンダー生成（土日・祝日・平日7限を自動判定）
    $cal_2025_count = generateBaseSchoolCalendar($pdo, '2025-04-01', '2026-03-31', 7);
    echo "・2025年度べース生成完了: {$cal_2025_count} 日分<br>";

    // 1-2. 2025年度 休校行事日程を登録
    $set_range_holiday($pdo, '2025-04-01', '2025-04-08', '春休み');
    $set_range_holiday($pdo, '2025-08-12', '2025-08-22', '夏休み');
    $set_range_holiday($pdo, '2025-09-25', '2025-09-30', '秋休み');
    $set_range_holiday($pdo, '2025-12-26', '2026-01-06', '冬休み');
    $set_range_holiday($pdo, '2026-03-12', '2026-03-31', '春休み');

    setSpecialCalendarDate($pdo, '2025-04-09', true, 0, '入校式');
    setSpecialCalendarDate($pdo, '2025-07-11', true, 0, '調整日休校');
    setSpecialCalendarDate($pdo, '2025-09-08', true, 0, '後期入校選考試験');
    setSpecialCalendarDate($pdo, '2025-09-24', true, 0, '前期修了式');
    setSpecialCalendarDate($pdo, '2025-10-23', true, 0, '一般入試');
    setSpecialCalendarDate($pdo, '2025-10-24', true, 0, '調整日休校');
    setSpecialCalendarDate($pdo, '2026-03-11', true, 0, '修了式');

    echo "・2025年度の長期休暇・休校の設定完了<br>";

    // 【フェーズ2】2026年度 カレンダー登録 (2026-04-01 ～ 2027-03-31)

    echo "<h4>■ 2026年度 カレンダー登録/h4>";

    // 2-1. ベースカレンダー生成（土日・祝日・平日7限を自動判定）
    $cal_2026_count = generateBaseSchoolCalendar($pdo, '2026-04-01', '2027-03-31', 7);
    echo "・2026年度べース生成完了: {$cal_2026_count} 日分<br>";

    // 2-2. 2026年度 休校行事日程を登録
    $set_range_holiday($pdo, '2026-04-01', '2026-04-07', '春休み');
    $set_range_holiday($pdo, '2026-08-17', '2026-08-28', '夏休み');
    $set_range_holiday($pdo, '2026-09-28', '2026-10-01', '秋休み');
    $set_range_holiday($pdo, '2026-12-28', '2027-01-05', '冬休み');
    $set_range_holiday($pdo, '2027-03-12', '2027-03-31', '春休み');

    setSpecialCalendarDate($pdo, '2026-04-08', true, 0, '入校式');
    setSpecialCalendarDate($pdo, '2026-07-10', true, 0, '調整日休校');
    setSpecialCalendarDate($pdo, '2026-09-07', true, 0, '後期入校選考試験');
    setSpecialCalendarDate($pdo, '2026-10-22', true, 0, '一般入試');
    setSpecialCalendarDate($pdo, '2027-03-11', true, 0, '修了式');

    // 2-3. 運用中の変更（台風休校、8限振替、10/1授業日化)
    setSpecialCalendarDate($pdo, '2026-06-01', true, 0, '台風休校');
    setSpecialCalendarDate($pdo, '2026-06-02', true, 0, '台風休校(午後再開・暫定全日休校)');
    setSpecialCalendarDate($pdo, '2026-06-26', true, 0, '台風休校');
    setSpecialCalendarDate($pdo, '2026-08-07', true, 0, '台風休校');

    $updated_periods = updatePeriodForDateRange($pdo, '2026-06-15', '2026-07-13', 8, '台風振替授業');
    echo "・台風振替(8限化)設定: {$updated_periods}日分<br>";

    // 10月1日 振替授業に上書き
    setSpecialCalendarDate($pdo, '2026-10-01', false, 7, '振り替え授業');
    echo "・10月1日を振替授業へ変更完了<br>";

    $pdo -> commit();
    echo "<p style='color: green; font-weight: bold;'>2025・2026年度の学校カレンダーが正常に作成されました</p>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<p style='color: red;'>エラー発生: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
}
