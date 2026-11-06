<?php

/**
 * 週間集計・月間集計・修了要件計算関数
 */

/**
 * 指定日が含まれる週の「月～金曜日」の日付情報を取得する
 *
 * @param  PDO    $pdo
 * @param  string $date
 * @return array  [ ['date' => '2026-09-07', 'month' => 9, 'day' => 7, 'week_day' => '月'], ...]
 */
function getWeekDates(PDO $pdo, string $date): array
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
            'date'        => $d,
            'month'       => (int)date('n', $current_time),
            'day'         => (int)date('j', $current_time),
            'week_day'    => $week_day_name[$i],
            'max_period'  => 7,
            'is_holiday'  => 0,
            'description' => '',
        ];
    }

    // school_calendarから該当週のカレンダー設定を取得
    $placeholders = implode(',', array_fill(0, count($date_list), '?'));
    $stmt         = $pdo->prepare("
        SELECT calendar_date, is_holiday, max_period, description 
        FROM school_calendar 
        WHERE calendar_date IN ({$placeholders})
    ");
    $stmt->execute($date_list);
    $cal_records = $stmt->fetchAll();

    foreach ($cal_records as $cal) {
        $d = $cal['calendar_date'];
        if (isset($week_dates[$d])) {
            $week_dates[$d]['max_period']  = (int)$cal['max_period'];
            $week_dates[$d]['is_holiday']  = (int)$cal['is_holiday'];
            $week_dates[$d]['description'] = $cal['description'] ?? '';
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

    // 未定義の場合はデフォルト値(280)を返す
    return ($limit !== false && $limit !== null) ? (int)$limit : 280;
}

/**
 * 指定年月における月間・年度累計の出欠データを生徒ごとに集計する
 *
 * @param  PDO    $pdo
 * @param  int    $department_id
 * @param  int    $grade
 * @param  string $target_month  集計対象月('YYYY-MM')
 * @return array
 */
function getMonthlyAttendanceSummary(PDO $pdo, int $department_id, int $grade, string $target_month): array
{
    // 対象月の開始日・終了日を抽出
    $start_date = "{$target_month}-01";
    $end_date   = date('Y-m-t', strtotime($start_date));

    // 今年度の開始日(4月1日)を算出
    $target_year      = (int)date('Y', strtotime($start_date));
    $target_month_num = (int)date('n', strtotime($start_date));
    $fiscal_year      = ($target_month_num >= 4) ? $target_year : $target_year - 1;
    $fiscal_start     = "{$fiscal_year}-04-01";

    // カレンダーから「当月」および「年度累計」の授業日・時限数を取得
    $cal_stmt = $pdo->prepare("
        SELECT
            calendar_date,
            max_period
        FROM
            school_calendar
        WHERE
            calendar_date
                BETWEEN :fiscal_start
                AND :month_end
        AND is_holiday = 0
        ORDER BY
            calendar_date ASC
    ");
    $cal_stmt->execute([
        ':fiscal_start' => $fiscal_start,
        ':month_end'    => $end_date,
    ]);
    $calendar_records = $cal_stmt->fetchAll();

    $monthly_days               = []; // 当月の授業日
    $month_total_periods        = 0;  // 当月の総授業時間数
    $cumulative_total_periods   = 0;  // 累計の総授業時間数

    foreach ($calendar_records as $cal) {
        $c_date  = $cal['calendar_date'];
        $periods = (int)$cal['max_period'];

        $cumulative_total_periods += $periods;

        // 当月分のみ抽出
        if ($c_date >= $start_date && $c_date <= $end_date) {
            $monthly_days[$c_date] = $periods;
            $month_total_periods += $periods;
        }
    }

    // 対象生徒一覧の取得
    $students = getAttendanceStudents($pdo, $department_id, $grade);
    if (empty($students)) {
        return [
            'students'                  => [],
            'month_total_periods'       => $month_total_periods,
            'cumulative_total_periods'  => $cumulative_total_periods,
            'class_avg_month_rate'      => 0.0,
            'class_avg_cumulative_rate' => 0.0,
        ];
    }

    // 年度開始～当月末までの出欠レコードを一括取得
    $att_stmt = $pdo->prepare("
        SELECT
            student_id,
            attendance_date,
            period,
            attendance_status_id
        FROM attendance
        WHERE
            attendance_date
                BETWEEN :fiscal_start
                AND :month_end
    ");
    $att_stmt->execute([
        ':fiscal_start' => $fiscal_start,
        ':month_end'    => $end_date,
    ]);
    $attendance_rows = $att_stmt->fetchAll();

    // 出欠レコードをマッピング
    $att_map = [];
    foreach ($attendance_rows as $row) {
        $att_map[$row['student_id']][$row['attendance_date']][$row['period']] = (int)$row['attendance_status_id'];
    }

    // 生徒ごとの集計処理
    $summary_list           = [];
    $total_month_rates      = 0;
    $total_cumulative_rates = 0;

    // 修了要件(欠席上限時間)をDBから取得
    $absence_limit = getAbsenceLimitHours($pdo, $department_id, $grade);

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

        foreach ($monthly_days as $date_str => $max_p) {
            $day_records = $st_att[$date_str] ?? [];

            $day_has_present   = false;
            $day_all_absent    = true;

            for ($p = 1; $p <= $max_p; $p++) {
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
            } elseif ($day_all_absent && $max_p > 0) {
                $absent_days++;
            }

            // 早退判定
            $last_period_status = $day_records[$max_p] ?? 1;
            if ($last_period_status === 2 && !$day_all_absent) {
                $early_count++;
            }
        }

        // 累計（4月～当月）の集計変数
        $cumulative_absent_h  = 0;
        $cumulative_present_h = 0;

        foreach ($calendar_records as $cal) {
            $c_date      = $cal['calendar_date'];
            $max_p       = (int)$cal['max_period'];
            $day_records = $st_att[$c_date] ?? [];

            for ($p = 1; $p <= $max_p; $p++) {
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
    ];
}
