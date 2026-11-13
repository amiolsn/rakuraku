<?php

/**
 * 情報システム科 1年生(2026/04/01 ～ 2026/10/31) および 2年生(2025/04/01 ～ 2026/10/31)の
 * 出欠テストデータ生成スクリプト
 * ※事前にsetup_calendar.phpでカレンダー登録が必要
 */
require_once __DIR__ . '../db.php';
require_once __DIR__ . '../functions.php';

set_time_limit(300);    // 処理タイムアウト防止

try {
    $pdo->beginTransaction();

    echo "<h3>出欠テストデータ生成開始</h3>";

    // 1. マスタデータ取得(生徒・科目・教員）
    $dept_stmt = $pdo -> query("
        SELECT department_id
        FROM department
        WHERE department_name LIKE '%情報システム科%'
        LIMIT 1
    ");
    $dept_id = $dept_stmt -> fetchColumn() ?: 1;

    $subject_stmt = $pdo -> prepare("
        SELECT subject_id
        FROM subjects
        WHERE department_id = :dept_id
    ");
    $subject_stmt -> execute([':dept_id' => $dept_id]);
    $subject_ids = $subject_stmt -> fetchAll(PDO::FETCH_COLUMN);

    $teacher_stmt = $pdo -> prepare("
        SELECT teacher_id
        FROM teachers
        WHERE department_id = :dept_id
    ");
    $teacher_stmt -> execute([':dept_id' => $dept_id]);
    $teacher_ids = $teacher_stmt -> fetchAll(PDO::FETCH_COLUMN);

    if (empty($subject_ids) || empty($teacher_ids)) {
        throw new Exception("科目または教員マスタが見つかりません。マスタデータを先に登録してください");
    }

    $students_stmt = $pdo -> prepare("
        SELECT student_id, student_name, grade
        FROM students
        WHERE department_id = :dept_id
            AND status = 1
        ORDER BY grade ASC, student_id ASC 
    ");
    $students_stmt -> execute([':dept_id' => $dept_id]);
    $all_students = $students_stmt -> fetchAll(PDO::FETCH_ASSOC);

    $students_grade1 = array_values(array_filter($all_students, fn ($s) => $s['grade'] == 1));
    $students_grade2 = array_values(array_filter($all_students, fn ($s) => $s['grade'] == 2));

    echo "・対象生徒: 1年生 " . count($students_grade1) . "名 / 2年生 " . count($students_grade2) . "名<br>";

    // 2. カレンダーから授業日を取得
    $get_school_days = function (PDO $pdo, string $start, string $end) {
        $stmt = $pdo -> prepare("
            SELECT calendar_date, max_period
            FROM school_calendar
            WHERE calendar_date
                BETWEEN :start AND :end
            AND is_holiday = 0
            AND max_period > 0
            ORDER BY calendar_date ASC
        ");
        $stmt -> execute([':start' => $start, ':end' => $end]);
        return $stmt -> fetchAll(PDO::FETCH_ASSOC);
    };

    // 1年生: 2026年4月1日 ～ 10月31日
    $days_grade1 = $get_school_days($pdo, '2026-04-01', '2026-10-31');
    // 2年生: 2025年4月1日 ～ 2026年10月31日
    $days_grade2 = $get_school_days($pdo, '2025-04-01', '2026-10-31');

    if (empty($days_grade1) || empty($days_grade2)) {
        throw new Exception("授業データが見つかりません。先に setup_calendar.php を実行してください。");
    }

    echo "・授業日数: 1年生=" .count($days_grade1) . "日 / 2年生=" . count($days_grade2) . "日<br>";

    // 3. 出欠パターン定義と投入処理

    // 出欠ステータスID(1:出席, 2:欠席, 3:遅刻, 4:公欠)
    $STATUS_PRESENT = 1;
    $STATUS_ABSENT  = 2;
    $STATUS_LATE    = 3;
    $STATUS_EXCUSED = 4;

    // 生徒の並び順($student_index)に応じて出欠傾向を振り分ける
    $determine_status = function (int $student_index) use ($STATUS_PRESENT, $STATUS_ABSENT, $STATUS_LATE, $STATUS_EXCUSED) {
        $rand = mt_rand(1, 100);
        if ($student_index === 0) {
            // パターンA: アラート対象（出席率76%, 欠席20%, 遅刻4%)
            if ($rand <= 76) {
                return $STATUS_PRESENT;
            }
            if ($rand <= 96) {
                return $STATUS_ABSENT;
            }
            return $STATUS_LATE;
        } elseif ($student_index <= 2) {
            // パターンB: 遅刻・公欠混在(出席率約91%, 遅刻5%, 公欠3%, 欠席1%)
            if ($rand <= 91) {
                return $STATUS_PRESENT;
            }
            if ($rand <= 96) {
                return $STATUS_LATE;
            }
            if ($rand <= 99) {
                return $STATUS_EXCUSED;
            }
            return $STATUS_ABSENT;
        } else {
            // パターンC: 皆勤・安定(出席率 約99%);
            if ($rand <= 98) {
                return $STATUS_PRESENT;
            }
            if ($rand <= 99) {
                return $STATUS_LATE;
            }
            return $STATUS_ABSENT;
        }
    };

    // DB登録用プリペアドステイトメント(既存データがあれば上書き更新）
    $insert_stmt = $pdo -> prepare("
        INSERT INTO attendance(
            student_id,
            attendance_date,
            period,
            subject_id,
            teacher_id,
            attendance_status_id 
        ) VALUES (
            :student_id,
            :attendance_date,
            :period,
            :subject_id,
            :teacher_id,
            :status_id 
        ) ON DUPLICATE KEY UPDATE
            subject_id = VALUES(subject_id),
            teacher_id = VALUES(teacher_id),
            attendance_status_id = VALUES(attendance_status_id)
    ");

    $total_records = 0;

    // 学年ごとの生徒・授業日ループ処理
    $insert_for_grade = function (array $students, array $days) use (&$insert_stmt, &$total_records, $subject_ids, $teacher_ids, $determine_status) {
        foreach ($students as $s_idx => $student) {
            $student_id = (int)$student['student_id'];

            foreach ($days as $day) {
                $date       = $day['calendar_date'];
                $max_period = (int)$day['max_period'];

                for ($p = 1; $p <= $max_period; $p++) {
                    // 曜日と時限から科目・単葉教員を分散して割り当て
                    $sub_idx = ($p + (int)date('N', strtotime($date))) % count($subject_ids);
                    $tch_idx = ($p + (int)date('w', strtotime($date))) % count($teacher_ids);

                    $status = $determine_status($s_idx);

                    $insert_stmt -> execute([
                        ':student_id'             => $student_id,
                        ':attendance_date'        => $date,
                        ':period'                 => $p,
                        ':subject_id'             => $subject_ids[$sub_idx],
                        ':teacher_id'             => $subject_ids[$tch_idx],
                        ':status_id'              => $status
                    ]);
                    $total_records++;
                }
            }
        }
    };

    echo "・1年生データ投入中(2026/04/01 ～ 2026/10/31) ... <br>";
    $insert_for_grade($students_grade1, $days_grade1);

    echo "・2年生データ投入中(2025/04/01 ～ 2026/10/31) ... <br>";
    $insert_for_grade($students_grade2, $days_grade2);

    $pdo->commit();
    echo "<p style='color: green, font-weight: bold,'>出欠データの登録完了 （合計: {$total_records} 件）</p>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("<p style='color:red;'>エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>");
}
