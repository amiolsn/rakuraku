<?php
/**
 * 2026年7月度 出欠テストデータ生成スクリプト（1年生・2年生対応）
 * ・school_calendar を参照し、休校日はスキップ、各日の max_period 分のコマを生成
 * ・情報システム科の1年生・2年生を対象に多様な出欠パターン（皆勤・遅刻・早退・病欠・公欠）を登録
 */
require_once 'db.php';

try {
    $pdo->beginTransaction();

    // 1. カレンダーから2026年7月の授業日（is_holiday = 0）を取得
    $calStmt = $pdo->query("
        SELECT calendar_date, max_period 
        FROM school_calendar 
        WHERE calendar_date BETWEEN '2026-07-01' AND '2026-07-31'
          AND is_holiday = 0
        ORDER BY calendar_date ASC
    ");
    $schoolDays = $calStmt->fetchAll();

    if (empty($schoolDays)) {
        throw new Exception("7月の授業日カレンダーデータが見つかりません。");
    }

    // 2. 対象生徒（1年生および2年生の在校生）の取得
    $stuStmt = $pdo->query("
        SELECT student_id, student_name, grade 
        FROM students 
        WHERE grade IN (1, 2) AND status = 1 
        ORDER BY grade ASC, student_id ASC
    ");
    $students = $stuStmt->fetchAll();

    if (empty($students)) {
        throw new Exception("1年生および2年生の在校生データが見つかりません。");
    }

    // デフォルトの科目ID・教員IDを取得
    $subjectId = (int)$pdo->query("SELECT subject_id FROM subjects LIMIT 1")->fetchColumn() ?: 1;
    $teacherId = (int)$pdo->query("SELECT teacher_id FROM teachers LIMIT 1")->fetchColumn() ?: 1;

    // 3. 出欠データの登録（UPSERT）
    $attStmt = $pdo->prepare("
        INSERT INTO attendance (student_id, attendance_date, period, subject_id, teacher_id, attendance_status_id)
        VALUES (:student_id, :attendance_date, :period, :subject_id, :teacher_id, :status_id)
        ON DUPLICATE KEY UPDATE
            subject_id = VALUES(subject_id),
            teacher_id = VALUES(teacher_id),
            attendance_status_id = VALUES(attendance_status_id)
    ");

    $recordCount = 0;
    $gradeCount  = [1 => 0, 2 => 0];

    foreach ($students as $index => $st) {
        $studentId = (int)$st['student_id'];
        $grade     = (int)$st['grade'];
        $pattern   = $index % 5; // 生徒ごとに出欠の傾向を分ける

        if (isset($gradeCount[$grade])) {
            $gradeCount[$grade]++;
        }

        foreach ($schoolDays as $dayIndex => $dayInfo) {
            $dateStr   = $dayInfo['calendar_date'];
            $maxPeriod = (int)$dayInfo['max_period'];

            for ($p = 1; $p <= $maxPeriod; $p++) {
                // デフォルトは 1:出席
                $statusId = 1;

                switch ($pattern) {
                    case 0:
                        // パターン0: 皆勤賞（全コマ出席）
                        $statusId = 1;
                        break;

                    case 1:
                        // パターン1: 遅刻テスト用（たまに1限が遅刻）
                        if ($p === 1 && $dayIndex % 4 === 0) {
                            $statusId = 3; // 遅刻
                        }
                        break;

                    case 2:
                        // パターン2: 早退テスト用（金曜日の最終2コマを欠席）
                        $w = (int)date('w', strtotime($dateStr));
                        if ($w === 5 && $p >= ($maxPeriod - 1)) {
                            $statusId = 2; // 欠席（早退判定）
                        }
                        break;

                    case 3:
                        // パターン3: 病欠テスト用（7/8・7/9が終日欠席）
                        if ($dateStr === '2026-07-08' || $dateStr === '2026-07-09') {
                            $statusId = 2; // 欠席
                        }
                        break;

                    case 4:
                        // パターン4: 公欠テスト用（7/15が終日公欠）
                        if ($dateStr === '2026-07-15') {
                            $statusId = 4; // 公欠
                        }
                        break;
                }

                $attStmt->execute([
                    ':student_id'      => $studentId,
                    ':attendance_date' => $dateStr,
                    ':period'          => $p,
                    ':subject_id'      => $subjectId,
                    ':teacher_id'      => $teacherId,
                    ':status_id'       => $statusId,
                ]);
                $recordCount++;
            }
        }
    }

    $pdo->commit();
    echo "<h2 style='color:green;'>1・2年生の出欠テストデータ登録完了！</h2>";
    echo "1年生対象: {$gradeCount[1]} 名<br>";
    echo "2年生対象: {$gradeCount[2]} 名<br>";
    echo "授業日数: " . count($schoolDays) . " 日<br>";
    echo "登録レコード総数: {$recordCount} 件<br><br>";
    echo '<ul>';
    echo '<li><a href="summary.php?tab=weekly&grade=1&date=2026-07-01">週間集計（1年生）で確認する</a></li>';
    echo '<li><a href="summary.php?tab=weekly&grade=2&date=2026-07-01">週間集計（2年生）で確認する</a></li>';
    echo '</ul>';

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("<h3 style='color:red;'>エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</h3>");
}
