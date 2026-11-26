<?php

/**
 * 週間集計・月間集計・訓練全期間集計・修了要件計算関数
 */

/**
 * 日付文字列から学校年度（4月始まり）を返す
 * 例: '2026-11-25' -> 2026 / '2026-02-10' -> 2025
 */
function getSchoolYear(string $date_str): int
{
    $year  = (int)date('Y', strtotime($date_str));
    $month = (int)date('n', strtotime($date_str));
    return ($month >= 4) ? $year : $year - 1;
}

/**
 * 指定日が含まれる週の「月～金曜日」の日付情報を取得する
 *
 * @param  PDO    $pdo
 * @param  string $date
 * @param  int    $department_id 学科ID(0:全科共通)
 * @return array  [ ['date' => '2026-09-07', 'month' => 9, 'day' => 7, 'week_day' => '月'], ...]
 */
function getWeekDates(PDO $pdo, string $date, int $department_id = 0): array
{
    $timestamp = strtotime($date);
    $w         = (int)date('w', $timestamp);

    // 月曜日のタイムスタンプを計算
    $diff_to_monday = ($w === 0) ? -6 : 1 - $w;
    $monday_time    = strtotime("{$diff_to_monday} days", $timestamp);

    $week_day_name = ['月', '火', '水', '木', '金'];
    $date_list     = [];
    $week_dates    = [];

    for ($i = 0; $i < 5; $i++) {
        $current_time = strtotime("+{$i} days", $monday_time);
        $d            = date('Y-m-d', $current_time);
        $date_list[]  = $d;

        $week_dates[$d] = [
            'date'         => $d,
            'month'        => (int)date('n', $current_time),
            'day'          => (int)date('j', $current_time),
            'week_day'     => $week_day_name[$i],
            'start_period' => 1,
            'end_period'   => 7,
            'max_period'   => 7,
            'is_holiday'   => 0,
            'description'  => '',
        ];
    }

    // school_calendarから該当週のカレンダー設定を取得（共通「0」→学科専用「>0」で上書き）
    $placeholders = implode(',', array_fill(0, count($date_list), '?'));
    $stmt         = $pdo->prepare("
        SELECT calendar_date, department_id, is_holiday, start_period, end_period, max_period, description 
        FROM school_calendar 
        WHERE calendar_date IN ({$placeholders})
          AND department_id IN (?, 0)
        ORDER BY department_id ASC
    ");
    $params = array_merge($date_list, [$department_id]);
    $stmt->execute($params);
    $cal_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cal_records as $cal) {
        $d = $cal['calendar_date'];
        if (isset($week_dates[$d])) {
            $week_dates[$d]['start_period'] = (int)$cal['start_period'];
            $week_dates[$d]['end_period']   = (int)$cal['end_period'];
            $week_dates[$d]['max_period']   = (int)$cal['max_period'];
            $week_dates[$d]['is_holiday']   = (int)$cal['is_holiday'];
            $week_dates[$d]['description']  = $cal['description'] ?? '';
        }
    }

    return array_values($week_dates);
}

/**
 * 学科・学年の許容欠席上限時間を取得する
 */
function getAbsenceLimitHours(PDO $pdo, int $department_id, int $grade): int
{
    $stmt = $pdo->prepare("
        SELECT max_absence_hours
        FROM course_requirements
        WHERE department_id = ? AND grade = ?
    ");
    $stmt->execute([$department_id, $grade]);
    $limit = $stmt->fetchColumn();

    // 未定義の場合は学年×280時間を返す
    return ($limit !== false && $limit !== null) ? (int)$limit : ($grade * 280);
}

/**
 * 指定年月における月間・訓練通算の出欠データを生徒ごとに集計する
 *
 * @param  PDO    $pdo
 * @param  int    $department_id
 * @param  int    $grade         現在選択中の生徒学年
 * @param  string $target_month  集計対象月('YYYY-MM')
 * @return array
 */
function getMonthlyAttendanceSummary(PDO $pdo, int $department_id, int $grade, string $target_month): array
{
    // 対象月の開始日・末日を抽出
    $start_date  = "{$target_month}-01";
    $month_end   = date('Y-m-t', strtotime($start_date));
    $today       = date('Y-m-d');

    // 未来日を含めないように集計終了日は「月末日」と「本日」の早いほうにする
    $end_date = ($month_end > $today) ? $today : $month_end;

    // 動的学年判定：現在の学校年度と選択学年から「入校年度」を逆算
    $current_fy    = getSchoolYear($today);
    $enrollment_fy = $current_fy - ($grade - 1);
    $target_fy     = getSchoolYear($start_date);

    // 表示対象月における「当時の学年」を算出
    $target_grade = $target_fy - $enrollment_fy + 1;

    // 入校前は空結果を返す
    if ($target_grade < 1) {
        return [
            'students'                 => [],
            'month_total_periods'      => 0,
            'cumulative_total_periods' => 0,
            'class_avg_month'          => 0.0,
            'class_avg_cumul'          => 0.0,
            'target_grade'             => $target_grade,
            'absence_limit'            => 0,
        ];
    }

    // 訓練開始日（入校年度の4月1日）を起算日とする
    $training_start = "{$enrollment_fy}-04-01";

    // カレンダーから「入校年度4月1日～対象終了日」のデータを取得
    $cal_stmt = $pdo->prepare("
        SELECT
            calendar_date,
            department_id,
            is_holiday,
            start_period,
            end_period,
            max_period
        FROM
            school_calendar
        WHERE
            calendar_date
                BETWEEN :training_start AND :month_end
        AND department_id IN (:dept_id, 0)
        ORDER BY
            calendar_date ASC,
            department_id ASC
    ");
    $cal_stmt->execute([
        ':training_start' => $training_start,
        ':month_end'      => $end_date,
        ':dept_id'        => $department_id,
    ]);
    $raw_calendar = $cal_stmt -> fetchAll(PDO::FETCH_ASSOC);

    // 日付ごとに学科専用設定を優先して確定させる
    $merged_calendar = [];
    foreach ($raw_calendar as $row) {
        $merged_calendar[$row['calendar_date']] = $row;
    }

    // 有効な授業日のみを抽出し、有効コマ数(end_period-start_period + 1)を算出
    $calendar_records           = []; // 入校～対象日までの全有効授業日
    $monthly_days               = []; // 当月の有効授業日
    $month_total_periods        = 0;  // 当月の総授業時間数
    $cumulative_total_periods   = 0;  // 通算の総授業時間数

    foreach ($merged_calendar as $c_date => $cal) {
        $is_holiday = (int)$cal['is_holiday'];
        $start_p    = (int)$cal['start_period'];
        $end_p      = (int)$cal['end_period'];

        // 休校日や有効時限がない日はスキップ
        if ($is_holiday === 1 || $start_p <= 0 || $end_p < $start_p) {
            continue;
        }

        $valid_periods = $end_p - $start_p + 1;

        $calendar_records[] = [
            'calendar_date' => $c_date,
            'start_period'  => $start_p,
            'end_period'    => $end_p,
        ];
        $cumulative_total_periods += $valid_periods;

        // 当月分のみ抽出
        if ($c_date >= $start_date && $c_date <= $end_date) {
            $monthly_days[$c_date] = [
                'start' => $start_p,
                'end'   => $end_p,
            ];
            $month_total_periods += $valid_periods;
        }
    }

    // 対象生徒一覧の取得
    $students = getAttendanceStudents($pdo, $department_id, $grade);
    if (empty($students)) {
        return [
            'students'                  => [],
            'month_total_periods'       => $month_total_periods,
            'cumulative_total_periods'  => $cumulative_total_periods,
            'class_avg_month'           => 0.0,
            'class_avg_cumul'           => 0.0,
            'target_grade'              => $target_grade,
            'absent_limit'              => getAbsenceLimitHours($pdo, $department_id, $target_grade),
        ];
    }

    // 入校日～対象終了日までの出欠レコードを一括取得
    $att_stmt = $pdo->prepare("
        SELECT
            student_id,
            attendance_date,
            period,
            attendance_status_id
        FROM attendance
        WHERE
            attendance_date
                BETWEEN :training_start
                AND :month_end
    ");
    $att_stmt->execute([
        ':training_start' => $training_start,
        ':month_end'      => $end_date,
    ]);
    $attendance_rows = $att_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 出欠レコードをマッピング
    $att_map = [];
    foreach ($attendance_rows as $row) {
        $att_map[$row['student_id']][$row['attendance_date']][$row['period']] = (int)$row['attendance_status_id'];
    }

    // 生徒ごとの集計処理
    $summary_list           = [];
    $total_month_rates      = 0;
    $total_cumulative_rates = 0;

    // 表示時点の学年($target_grade)に応じた欠席上限時間を取得
    $absence_limit = getAbsenceLimitHours($pdo, $department_id, $target_grade);

    foreach ($students as $st) {
        $st_id  = (int)$st['student_id'];
        $st_att = $att_map[$st_id] ?? [];

        // 当月の集計変数
        $present_days    = 0;  // 出席日数
        $absent_days     = 0;   // 欠席日数
        $late_count      = 0;    // 遅刻回数
        $early_count     = 0;   // 早退回数
        $month_present_h = 0;   // 出席時間
        $month_absent_h  = 0;    // 欠席時間

        foreach ($monthly_days as $date_str => $range) {
            $start_p     = $range['start'];
            $end_p       = $range['end'];
            $day_records = $st_att[$date_str] ?? [];

            $day_has_present   = false;
            $day_all_absent    = true;

            // 有効時限(start_period～end_period)のみを集計
            for ($p = $start_p; $p <= $end_p; $p++) {
                $status = $day_records[$p] ?? 1;    // 記録がなければ出席(１)とみなす

                if ($status === 1) {
                    // 出席
                    $month_present_h++;
                    $day_has_present = true;
                    $day_all_absent  = false;
                } elseif ($status === 2) {
                    // 欠席
                    $month_absent_h++;
                } elseif ($status === 3) {
                    // 遅刻(出席としてカウント）
                    $month_present_h++;
                    $day_has_present = true;
                    $day_all_absent  = false;
                    $late_count++;
                } elseif ($status === 4) {
                    // 公欠(出席としてカウント）
                    $month_present_h++;
                    $day_has_present = true;
                    $day_all_absent  = false;
                }
            }

            // 日数カウント
            if ($day_has_present) {
                $present_days++;
            } elseif ($day_all_absent) {
                $absent_days++;
            }

            // 早退判定
            $last_period_status = $day_records[$end_p] ?? 1;
            if ($last_period_status === 2 && !$day_all_absent) {
                $early_count++;
            }
        }

        // 入校からの通算集計
        $cumulative_absent_h  = 0;
        $cumulative_present_h = 0;

        foreach ($calendar_records as $cal) {
            $c_date      = $cal['calendar_date'];
            $start_p     = $cal['start_period'];
            $end_p       = $cal['end_period'];
            $day_records = $st_att[$c_date] ?? [];

            for ($p = $start_p; $p <= $end_p; $p++) {
                $status = $day_records[$p] ?? 1;
                if ($status === 2) {
                    $cumulative_absent_h++;
                } else {
                    $cumulative_present_h++;
                }
            }
        }

        // 出席率の計算
        $month_rate = ($month_total_periods > 0)
            ? round(($month_present_h / $month_total_periods) * 100, 1)
            : 100.0;

        $cumulative_rate = ($cumulative_total_periods > 0)
            ? round(($cumulative_present_h / $cumulative_total_periods) * 100, 1)
            : 100.0;

        // 残欠席猶予時間
        $remaining_absent_h = max(0, $absence_limit - $cumulative_absent_h);

        $total_month_rates += $month_rate;
        $total_cumulative_rates += $cumulative_rate;

        $summary_list[] = [
            'student_id'          => $st_id,
            'student_name'        => $st['student_name'],
            'present_days'        => $present_days, // 出席日数
            'absent_days'         => $absent_days,   // 欠席日数
            'late_count'          => $late_count,    // 遅刻回数
            'early_count'         => $early_count,  // 早退回数
            'month_present_h'     => $month_present_h,  // 今月出席時間
            'month_absent_h'      => $month_absent_h,    // 今月欠席時間
            'month_rate'          => $month_rate,    // 今月出席率
            'cumulative_absent_h' => $cumulative_absent_h,  // 欠席時間累計
            'cumulative_rate'     => $cumulative_rate,  // 累計出席率
            'remaining_absent_h'  => $remaining_absent_h,    // 残欠席時間
        ];
    }

    // クラス平均出席率
    $st_count        = count($students);
    $class_avg_month = ($st_count > 0) ? round($total_month_rates / $st_count, 1) : 0.0;
    $class_avg_cumul = ($st_count > 0) ? round($total_cumulative_rates / $st_count, 1) : 0.0;

    return [
        'students'                 => $summary_list,
        'month_total_periods'      => $month_total_periods,  // 今月総授業時間
        'cumulative_total_periods' => $cumulative_total_periods,    // 累計総授業時間
        'class_avg_month'          => $class_avg_month,  // クラス今月平均
        'class_avg_cumul'          => $class_avg_cumul,  // クラス累計平均
        'target_grade'             => $target_grade,
        'absent_limit'             => $absence_limit,
    ];
}

/**
 * 訓練全期間集計データを取得する（クラス通算一覧 + 個人別集計推移）
 *
 * @param  PDO   $pdo
 * @param  int   $department_id
 * @param  int   $grade
 * @return array
 */
function getTotalAttendanceSummary(PDO $pdo, int $department_id, int $grade): array
{
    $today         = date('Y-m-d');
    $current_fy    = getSchoolYear($today);
    $enrollment_fy = $current_fy - ($grade - 1);

    $training_start = "{$enrollment_fy}-04-01";
    $fy_end         = ($current_fy + 1). "-03-31";  // 当該年度の3月31日まで（予定訓練数算出）

    // 入校年度4月1日 ～ 当該年度3月31日までのカレンダーを取得
    $cal_stmt = $pdo -> prepare("
        SELECT
            calendar_date,
            department_id,
            is_holiday,
            start_period,
            end_period
        FROM
            school_calendar
        WHERE
            calendar_date BETWEEN :start_date AND :end_date
            AND department_id IN (:dept_id, 0)
        ORDER BY
            calendar_date ASC,
            department_id ASC
    ");
    $cal_stmt -> execute([
        ':start_date' => $training_start,
        ':end_date'   => $fy_end,
        ':dept_id'    => $department_id,
    ]);
    $raw_calendar = $cal_stmt -> fetchAll(PDO::FETCH_ASSOC);

    $merged_calendar = [];
    foreach ($raw_calendar as $row) {
        $merged_calendar[$row['calendar_date']] = $row;
    }

    // 対象生徒一覧
    $students      = getAttendanceStudents($pdo, $department_id, $grade);
    $absence_limit = getAbsenceLimitHours($pdo, $department_id, $grade);

    // 入校日 ～ 本日までの出欠データ取得
    $att_stmt = $pdo -> prepare("
        SELECT
            student_id,
            attendance_date,
            period,
            attendance_status_id
        FROM attendance
        WHERE attendance_date BETWEEN :start_date AND :today
    ");
    $att_stmt -> execute([
        ':start_date' => $training_start,
        ':today'      => $today,
    ]);
    $attendance_rows = $att_stmt -> fetchAll(PDO::FETCH_ASSOC);

    $att_map = [];
    foreach ($attendance_rows as $row) {
        $att_map[$row['student_id']][$row['attendance_date']][$row['period']] = (int)$row['attendance_status_id'];
    }

    // カレンダーを「前年度まで（1年次など）」と「今年度（4月～3月の各月）」に整理
    $prev_years_days      = [];  // 昨年3月31日までの有効授業日
    $prev_years_scheduled = 0;  // 昨年までの実施済み訓練時数
    $current_fy_months    = [];

    // 今年度4月～3月の12か月分を初期化
    for ($m_offset = 0; $m_offset < 12; $m_offset++) {
        $m_ts                   = strtotime("+{$m_offset} months", strtotime("{$current_fy}-04-01"));
        $ym                     = date('Y-m', $m_ts);
        $m_num                  = (int)date('n', $m_ts);
        $current_fy_months[$ym] = [
            'ym'          => $ym,
            'label'       => "{$m_num}月",
            'scheduled_h' => 0,
            'elapsed_h'   => 0,
            'days'        => [],
        ];
    }

    $total_elapsed_periods   = 0;
    $total_scheduled_periods = 0;

    foreach ($merged_calendar as $c_date => $cal) {
        $is_holiday = (int)$cal['is_holiday'];
        $start_p    = (int)$cal['start_period'];
        $end_p      = (int)$cal['end_period'];

        if ($is_holiday === 1 || $start_p <= 0 || $end_p < $start_p) {
            continue;
        }

        $valid_p = $end_p - $start_p + 1;
        $is_past = ($c_date <= $today);
        $c_fy    = getSchoolYear($c_date);
        $ym      = substr($c_date, 0, 7);

        $total_scheduled_periods += $valid_p;
        if ($is_past) {
            $total_elapsed_periods += $valid_p;
        }

        if ($c_fy < $current_fy) {
            // 過去の学年
            if ($is_past) {
                $prev_years_scheduled += $valid_p;
                $prev_years_days[] = [
                    'date'  => $c_date,
                    'start' => $start_p,
                    'end'   => $end_p,
                ];
            }
        } elseif (isset($current_fy_months[$ym])) {
            // 今年度の各月（4月～3月）
            $current_fy_months[$ym]['scheduled_h'] += $valid_p;
            if ($is_past) {
                $current_fy_months[$ym]['elapsed_h'] += $valid_p;
                $current_fy_months[$ym]['days'][] = [
                    'date'  => $c_date,
                    'start' => $start_p,
                    'end'   => $end_p,
                ];
            }
        }
    }

    // 生徒ごとの全期間＆月別推移データの生成
    $student_totals  = [];
    $sum_total_rates = 0;
    $danger_count    = 0;

    foreach ($students as $st) {
        $st_id  = (int)$st['student_id'];
        $st_att = $att_map[$st_id] ?? [];

        // 過去学年の集計
        $prev_present_h = 0;
        $prev_absent_h  = 0;
        foreach ($prev_years_days as $d_info) {
            $day_rec = $st_att[$d_info['date']] ?? [];
            for ($p = $d_info['start']; $p <= $d_info['end']; $p++) {
                $status = $day_rec[$p] ?? 1;
                if ($status === 2) {
                    $prev_absent_h++;
                } else {
                    $prev_present_h++;
                }
            }
        }
        $prev_rate = ($prev_years_scheduled > 0)
            ? round(($prev_present_h / $prev_years_scheduled) * 100, 1)
            : 100.0;

        // 今年度の月別推移と累積欠席の算出
        $running_absent_h  = $prev_absent_h; // 1年次の欠席時間を初期値として引き継ぐ
        $curr_fy_present_h = 0;
        $curr_fy_absent_h  = 0;
        $curr_fy_elapsed_h = 0;
        $monthly_breakdown = [];

        foreach ($current_fy_months as $ym => $m_info) {
            $m_present = 0;
            $m_absent  = 0;

            foreach ($m_info['days'] as $d_info) {
                $day_rec = $st_att[$d_info['date']] ?? [];
                for ($p = $d_info['start']; $p <= $d_info['end']; $p++) {
                    $status = $day_rec[$p] ?? 1;
                    if ($status === 2) {
                        $m_absent++;
                    } else {
                        $m_present++;
                    }
                }
            }

            $running_absent_h += $m_absent;
            $curr_fy_present_h += $m_present;
            $curr_fy_absent_h += $m_absent;
            $curr_fy_elapsed_h += $m_info['elapsed_h'];

            $m_rate = ($m_info['elapsed_h'] > 0)
                ? round(($m_present / $m_info['elapsed_h']) * 100, 1)
                : 0.0;

            $monthly_breakdown[] = [
                'ym'                  => $ym,
                'label'               => $m_info['label'],
                'scheduled_h'         => $m_info['scheduled_h'],    // 予定訓練時間
                'elapsed_h'           => $m_info['elapsed_h'],    // 消化済み訓練時数
                'present_h'           => $m_present,
                'absent_h'            => $m_absent,
                'rate'                => $m_rate,
                'cumulative_absent_h' => $running_absent_h, // 1年次からの累積欠席時間
                'remaining_absent_h'  => max(0, $absence_limit - $running_absent_h),
            ];
        }

        // 全期間通算
        $total_present_h = $prev_present_h + $curr_fy_present_h;
        $total_absent_h  = $prev_absent_h + $curr_fy_absent_h;
        $total_rate      = ($total_elapsed_periods > 0)
            ? round(($total_present_h / $total_elapsed_periods) * 100, 1)
            : 100.0;
        $curr_fy_rate = ($curr_fy_elapsed_h > 0)
            ? round(($curr_fy_present_h / $curr_fy_elapsed_h) * 100, 1)
            : 100.0;
        $remaining_absent_h = max(0, $absence_limit - $total_absent_h);

        if ($total_rate < 80.0 || $remaining_absent_h <= 20) {
            $danger_count++;
        }
        $sum_total_rates += $total_rate;

        $student_totals[] = [
            'student_id'   => $st_id,
            'student_name' => $st['student_name'],
            // 過去学年実績
            'prev_scheduled_h'        => $prev_years_scheduled,
            'prev_present_h'          => $prev_present_h,
            'prev_absent_h'           => $prev_absent_h,
            'prev_rate'               => $prev_rate,
            'prev_remaining_absent_h' => max(0, $absence_limit - $prev_absent_h),
            // 今年度実績
            'curr_elapsed_h' => $curr_fy_elapsed_h,
            'curr_present_h' => $curr_fy_present_h,
            'curr_absent_h'  => $curr_fy_absent_h,
            'curr_rate'      => $curr_fy_rate,
            // 全期間通算
            'total_present_h'    => $total_present_h,
            'total_absent_h'     => $total_absent_h,
            'total_rate'         => $total_rate,
            'remaining_absent_h' => $remaining_absent_h,
            // 月別推移データ
            'monthly_breakdown' => $monthly_breakdown,
        ];
    }

    $st_count        = count($students);
    $class_avg_total = ($st_count > 0) ? round($sum_total_rates / $st_count, 1) : 0.0;

    return [
        'grade'                   => $grade,
        'enrollment_fy'           => $enrollment_fy,
        'current_fy'              => $current_fy,
        'absent_limit'            => $absence_limit,
        'total_elapsed_periods'   => $total_elapsed_periods,
        'total_scheduled_periods' => $total_scheduled_periods,
        'class_avg_total'         => $class_avg_total,
        'danger_count'            => $danger_count,
        'students'                => $student_totals,
    ];
}

/**
 * 生徒1名分の全期間通算・月別勤怠推移データを取得する
 *
 * @param  PDO        $pdo
 * @param  int        $student_id
 * @return array|null
 */
function getStudentTotalSummary(PDO $pdo, int $student_id): ?array
{
    $stmt = $pdo -> prepare("
        SELECT student_id, student_name, department_id, grade
        FROM students
        WHERE student_id = :student_id
    ");
    $stmt -> execute([':student_id' => $student_id]);
    $student = $stmt -> fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        return null;
    }

    $dept_id = (int)$student['department_id'];
    $grade   = (int)$student['grade'];

    // クラス全体の集計ロジックを再利用して計算の整合性を保つ
    $class_summary = getTotalAttendanceSummary($pdo, $dept_id, $grade);

    foreach ($class_summary['student'] as $st_date) {
        if ((int)$st_date['student_id'] === $student_id) {
            return [
                'student'                 => $st_date,
                'grade'                   => $grade,
                'department_id'           => $dept_id,
                'absence_limit'           => $class_summary['absence_limit'],
                'total_elapsed_periods'   => $class_summary['total_elapsed_periods'],
                'total_scheduled_periods' => $class_summary['total_scheduled_periods'],
            ];
        }
    }

    return null;
}
