<?php

/**
 * 情報システム科 1年生(2026/04/01 ～ 2026/10/31) および 2年生(2025/04/01 ～ 2026/10/31)の
 * 出欠テストデータ生成スクリプト
 * ※事前にsetup_calendar.phpでカレンダー登録が必要
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

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

    // 学校全体の交通渋滞・バス遅延日（1限複数人遅刻）を事前に決定
    // 約1.5％の確率（2～3か月に1回）で朝の交通渋滞が発生
    $traffic_jad_dates = [];
    $all_unique_dates  = array_unique(array_merge(
        array_column($days_grade1, 'calendar_date'),
        array_column($days_grade2, 'calendar_date')
    ));
    foreach ($all_unique_dates as $d) {
        if (mt_rand(1, 1000) <= 15) {
            $traffic_jad_dates[$d] = true;
        }
    }

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
    $insert_for_grade = function (array $students, array $days, int $grade) use (&$insert_stmt, &$total_records, $subject_ids, $teacher_ids, $traffic_jad_dates, $STATUS_PRESENT, $STATUS_ABSENT, $STATUS_LATE, $STATUS_EXCUSED) {
        if (empty($students)) {
            return;
        }

        $all_student_ids = array_column($students, 'student_id');

        foreach ($days as $day) {
            $date       = $day['calendar_date'];
            $max_period = (int)$day['max_period'];

            // この日が交通渋滞日なら、クラスからランダムに2～4名を1限遅刻対象に選出
            $jam_late_students = [];
            if (isset($traffic_jad_dates[$date])) {
                $pick_count = min(mt_rand(2, 4), count($all_student_ids));
                $picked_key = (array)array_rand($all_student_ids, $pick_count);
                foreach ($picked_key as $k) {
                    $jam_late_students[] = (int)$all_student_ids[$k];
                }
            }

            foreach ($students as $s_idx => $student) {
                $student_id = (int)$student['student_id'];

                // 初期化：その日の連続ブロック範囲
                $absent_start  = null;
                $absent_end    = null;
                $excused_start = null;
                $excused_end   = null;
                $is_late_1st   = false;

                // 連続コマの公欠ブロック判定
                $excused_permil = ($grade === 2 || $s_idx === 2) ? 15 : 0;

                if (mt_rand(1, 1000) <= $excused_permil) {
                    $ex_pat = mt_rand(1, 4);
                    if ($ex_pat === 1) {
                        // 午前公欠
                        $excused_start = 1;
                        $excused_end   = min(4, $max_period);
                    } elseif ($ex_pat === 2) {
                        // 午後公欠
                        $excused_start = 5;
                        $excused_end   = $max_period;
                    } elseif ($ex_pat === 3) {
                        // 午後の一部公欠
                        $excused_start = 5;
                        $excused_end   = min(6, $max_period);
                    } else {
                        // 終日公欠
                        $excused_start = 1;
                        $excused_end   = $max_period;
                    }
                }

                // 連続コマの欠席ブロック判定（公欠がない日）
                if ($excused_start === null) {
                    // $s_idx === 0はアラート対象の欠席多発生徒(約25%=週1~1.5日ペース）
                    // それ以外の一般生徒は約1.5%(2~3か月に1回ペース）
                    $absent_permil = ($s_idx === 0) ? 250 : 15;

                    if (mt_rand(1, 1000) <= $absent_permil) {
                        $ab_pat = mt_rand(1, 10);
                        if ($ab_pat <= 5) {
                            // 終日欠席
                            $absent_start = 1;
                            $absent_end   = $max_period;
                        } elseif ($ab_pat <= 8) {
                            // 午後欠席・早退
                            $absent_start = 5;
                            $absent_end   = $max_period;
                        } else {
                            // 午前一部欠席
                            $absent_start = 1;
                            $absent_end   = min(2, $max_period);
                        }
                    }
                }

                // 一限目の遅刻判定
                $is_1st_blocked = ($excused_start !== null && $excused_start <= 1) || ($absent_start !== null && $absent_start <= 1);

                if (!$is_1st_blocked) {
                    if ($s_idx === 1 && mt_rand(1, 100) <= 65) {
                        // 常習遅刻生徒：登校日の65%で1限遅刻
                        $is_late_1st = true;
                    } elseif (in_array($student_id, $jam_late_students, true)) {
                        $is_late_1st = true;
                    }
                }

                // コマごとのデータ生成
                for ($p = 1; $p <= $max_period; $p++) {
                    // 曜日と時限から科目・単葉教員を分散して割り当て
                    $sub_idx = ($p + (int)date('N', strtotime($date))) % count($subject_ids);
                    $tch_idx = ($p + (int)date('w', strtotime($date))) % count($teacher_ids);

                    if ($excused_start !== null && $p >= $excused_start && $p <= $excused_end) {
                        $status = $STATUS_EXCUSED;
                    } elseif ($absent_start !== null && $p >= $absent_start && $p <= $absent_end) {
                        $status = $STATUS_ABSENT;
                    } elseif ($p === 1 && $is_late_1st) {
                        $status = $STATUS_LATE;
                    } else {
                        $status = $STATUS_PRESENT;
                    }

                    $insert_stmt -> execute([
                        ':student_id'             => $student_id,
                        ':attendance_date'        => $date,
                        ':period'                 => $p,
                        ':subject_id'             => $subject_ids[$sub_idx],
                        ':teacher_id'             => $teacher_ids[$tch_idx],
                        ':status_id'              => $status
                    ]);
                    $total_records++;
                }
            }
        }
    };

    echo "・1年生データ投入中(2026/04/01 ～ 2026/10/31) ... <br>";
    $insert_for_grade($students_grade1, $days_grade1, 1);

    echo "・2年生データ投入中(2025/04/01 ～ 2026/10/31) ... <br>";
    $insert_for_grade($students_grade2, $days_grade2, 2);

    $pdo->commit();
    echo "<p style='color: green, font-weight: bold;'>出欠データの登録完了 （合計: {$total_records} 件）</p>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("<p style='color:red;'>エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>");
}
