<?php

/**
 * attendance/setup/setup_calendar_2026.php
 * 2026年4月〜10月の学校カレンダー（祝日・長期休暇・台風・時限変更）一括生成スクリプト
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

try {
    $pdo->beginTransaction();

    echo "<h3>2026年度 学校カレンダー生成・運用フロー検証</h3>";

    // -------------------------------------------------------------------------
    // フェーズ1：年度当初の年間行事予定登録
    // -------------------------------------------------------------------------
    echo "<h4>【フェーズ1】年度当初の年間予定一括登録</h4>";

    // 1-1. ベースカレンダー生成（土日・祝日・慰霊の日・平日7限を自動判定）
    $base_count = generateBaseSchoolCalendar($pdo, '2026-04-01', '2027-03-31', 7);
    echo "・ベースカレンダー生成完了: {$base_count} 日分 （2026年・2027年祝日反映済）<br>";

    // 期間指定で休校日を登録するヘルパー関数
    $set_range_holiday = function (PDO $pdo, string $start, string $end, string $desc) {
        $stmt = $pdo->prepare("
            UPDATE school_calendar
            SET is_holiday = 1, max_period = 0, description = :desc
            WHERE calendar_date BETWEEN :start AND :end
        ");
        $stmt->execute([':desc' => $desc, ':start' => $start, ':end' => $end]);
    };

    // 1-2. 年間予定表通りの長期休暇・学校行事休校を登録
    $set_range_holiday($pdo, '2026-04-01', '2026-04-08', '春休み');
    $set_range_holiday($pdo, '2026-08-17', '2026-08-28', '夏休み');
    $set_range_holiday($pdo, '2026-09-28', '2026-10-01', '秋休み'); // ★ 10/1も休校として登録
    $set_range_holiday($pdo, '2026-12-28', '2027-01-05', '冬休み');
    $set_range_holiday($pdo, '2027-03-12', '2027-03-31', '春休み');

    setSpecialCalendarDate($pdo, '2026-07-10', true, 0, '調整日休校');
    setSpecialCalendarDate($pdo, '2026-09-07', true, 0, '後期入校選考試験');
    setSpecialCalendarDate($pdo, '2026-10-22', true, 0, '一般入試');
    setSpecialCalendarDate($pdo, '2027-03-11', true, 0, '修了式');

    echo "・年間予定（春・夏・秋・冬休み、入試、修了式等）の登録完了<br>";

    // 検証：この時点での 10月1日 の状態を確認
    $check_stmt = $pdo->prepare("SELECT calendar_date, is_holiday, max_period, description FROM school_calendar WHERE calendar_date = '2026-10-01'");
    $check_stmt->execute();
    $before_1001 = $check_stmt->fetch();
    echo "<blockquote style='color:#555;'>［当初登録］10月1日: 休校フラグ={$before_1001['is_holiday']} / 時限数={$before_1001['max_period']} / 理由={$before_1001['description']}</blockquote>";

    // -------------------------------------------------------------------------
    // フェーズ2：運用中の変更登録（台風休校・振替授業の発生）
    // -------------------------------------------------------------------------
    echo "<h4>【フェーズ2】運用中の変更登録（台風休校・振替授業）</h4>";

    // 2-1. 台風による臨時休校の発生
    setSpecialCalendarDate($pdo, '2026-06-01', true, 0, '台風休校');
    setSpecialCalendarDate($pdo, '2026-06-02', true, 0, '台風休校（午後再開・暫定全日休校）');
    setSpecialCalendarDate($pdo, '2026-06-26', true, 0, '台風休校');
    setSpecialCalendarDate($pdo, '2026-08-07', true, 0, '台風休校');
    echo "・台風臨時休校の登録完了<br>";

    // 2-2. 授業時限の一括変更（6/15〜7/13を8時間授業化）
    $updated_periods = updatePeriodForDateRange($pdo, '2026-06-15', '2026-07-13', 8, '台風振替授業');
    echo "・平日授業日の8限化（台風振替）: {$updated_periods} 日分<br>";

    // 2-3. 休校予定日（秋休み最終日）を振替授業日へ個別変更
    setSpecialCalendarDate($pdo, '2026-10-01', false, 7, '振替授業');

    // 検証：上書き変更後の 10月1日 の状態を確認
    $check_stmt->execute();
    $after_1001 = $check_stmt->fetch();
    echo "<blockquote style='color:#0066cc; font-weight:bold;'>［変更後］10月1日: 休校フラグ={$after_1001['is_holiday']} / 時限数={$after_1001['max_period']} / 理由={$after_1001['description']}</blockquote>";

    $pdo->commit();
    echo "<p style='color: green; font-weight: bold;'>全カレンダーデータの運用シミュレーション登録が正常に完了しました！</p>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<p style='color: red;'>エラー発生: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
}
