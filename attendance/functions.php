<?php

// functions.php

/**
 * @param  PDO   $pdo
 * @param  int   $department_id
 * @return array
 */

// 指定学科の科目一覧を取得する
function getSubjectsByDepartment(PDO $pdo, int $department_id): array
{
    $stmt = $pdo->prepare("
        SELECT subject_id, subject_name, department_name
        FROM subjects s
        JOIN department d
            ON s.department_id = d.department_id
        WHERE d.department_id = ?
        ORDER BY subject_id ASC 
    ");
    $stmt->execute([$department_id]);
    return $stmt->fetchAll();
}

// 教員一覧を取得
function getTeachersByDepartment(PDO $pdo, int $department_id): array
{
    $stmt = $pdo->prepare("
        SELECT teacher_id, teacher_name, department_name
        FROM teachers t
        JOIN department d 
            ON t.department_id = d.department_id
        WHERE d.department_id = ?
        ORDER BY teacher_id ASC
    ");
    $stmt->execute([$department_id]);
    return $stmt->fetchAll();
}

// 修業年数の取得
function getDepartmentYears(PDO $pdo, int $department_id): int
{
    $stmt = $pdo->prepare("
        SELECT MAX(grade) AS term
        FROM course_requirements
        WHERE department_id = ?
    ");
    $stmt->execute([$department_id]);
    $result = $stmt->fetch();
    return (int)($result['term'] ?? 0);
}

// 班一覧の取得
function getStudentGroup(PDO $pdo, int $department_id): array
{
    $stmt = $pdo->prepare("
        SELECT DISTINCT student_group
        FROM students
        WHERE department_id = ?
        AND status = 1
        AND student_group IS NOT NULL
        AND student_group <> ''
        ORDER BY student_group ASC
    ");
    $stmt->execute([$department_id]);
    return $stmt->fetchAll();
}

// 学科IDから学科名を取得
function getDepartmentName(PDO $pdo, int $department_id): string
{
    $stmt = $pdo->prepare("
        SELECT department_name
        FROM department
        WHERE department_id = ?
    ");
    $stmt->execute([$department_id]);
    return (string)$stmt->fetchColumn();
}

/**
 * 対象日のカレンダー情報(休校日判定、理由、最大時限数)を取得する
 *
 * @param  PDO    $pdo
 * @param  string $target_date
 * @param  int    $default_max_period
 * @return array  ['is_holiday' => bool, 'description' => string, 'max_period' => int]
 */
function getCalendarInfo(PDO $pdo, string $target_date, int $default_max_period = 7): array
{
    // カレンダーテーブルから対象日の情報を取得
    $stmt = $pdo->prepare("
        SELECT
            calendar_date,
            is_holiday,
            max_period,
            description
        FROM school_calendar
        WHERE calendar_date = :att_date
    ");
    $stmt->execute(['att_date' => $target_date]);
    $calendar_info = $stmt->fetch();

    // 休校フラグと休校理由の判定
    $is_holiday  = false;
    $description = '';
    if ($calendar_info && !empty($calendar_info['is_holiday'])) {
        $is_holiday  = true;
        $description = $calendar_info['description'];
    }

    // 時限数の動的決定
    $max_period = (!empty($calendar_info['max_period']) && $calendar_info['max_period'] > 0)
        ? (int)$calendar_info['max_period']
        : $default_max_period;

    return [
        'is_holiday'  => $is_holiday,
        'description' => $description,
        'max_period'  => $max_period,
    ];
}

/**
 * 対象日の出欠データおよび時限ごとの初期設定（科目・教員）を取得・構築する
 *
 * @param  PDO    $pdo
 * @param  string $target_date
 * @param  int    $max_period
 * @return array  [
 *                'existing_attendance' => array, // [ "studentId_period" => statusId, ... ]
 *                'existing_periods'    => array  // [ period => ['subject_id' => ..., teacher_id' => ...], ... ]
 *                ]
 */

function getAttendanceData(PDO $pdo, string $target_date, int $max_period): array
{
    // その日の出欠データDBから探す
    $stmt = $pdo->prepare("
        SELECT student_id, period, subject_id, teacher_id, attendance_status_id
        FROM attendance
        WHERE attendance_date = :att_date
    ");
    $stmt->execute(['att_date' => $target_date]);
    $saved_records = $stmt->fetchAll();

    // 整理用の箱を用意
    $existing_attendance = [];
    $period_subjects     = [];
    $period_teachers     = [];

    // DBからとれたデータを箱に入れる
    foreach ($saved_records as $rec) {
        // 学籍番号_時限をキーにしてステータスを記憶
        $p                         = $rec['period'];
        $key                       = "{$rec['student_id']}_{$p}";
        $existing_attendance[$key] = $rec['attendance_status_id'];

        // 科目IDと教員IDを時限ごとに集める（連想配列のキーにして重複を削除）
        if (!empty($rec['subject_id'])) {
            $period_subjects[$p][$rec['subject_id']] = true;
        }
        if (!empty($rec['teacher_id'])) {
            $period_teachers[$p][$rec['teacher_id']] = true;
        }
    }

    // 時限ごとに全員一致しているかを確認
    $existing_periods = [];
    for ($p = 1; $p <= $max_period; $p++) {
        // 科目の判定
        // 登録されている科目が1つだけなら採用
        $subj_keys  = isset($period_subjects[$p]) ? array_keys($period_subjects[$p]) : [];
        $match_subj = (count($subj_keys) === 1) ? $subj_keys[0] : '';

        // 教員の判定
        $teach_keys  = isset($period_teachers[$p]) ? array_keys($period_teachers[$p]) : [];
        $match_teach = (count($teach_keys) === 1) ? $teach_keys[0] : '';

        $existing_periods[$p] = [
            'subject_id' => $match_subj,
            'teacher_id' => $match_teach,
        ];
    }

    return [
        'existing_attendance' => $existing_attendance,
        'existing_periods'    => $existing_periods,
    ];
}

/**
 * 出欠入力用の生徒一覧を取得する（在校生・学年/班の絞り込み対応)
 *
 * @param  PDO         $pdo
 * @param  int         $department_id
 * @param  int|null    $grade
 * @param  string|null $group
 * @return array
 */
// 生徒一覧の取得
function getAttendanceStudents(PDO $pdo, int $department_id, ?int $grade = null, ?string $group = null): array
{
    $sql = "
        SELECT
                s.student_id,
                s.student_name,
                s.grade,
                s.student_group,
                d.department_name
        FROM students s
        LEFT OUTER JOIN department d
            ON s.department_id = d.department_id
        WHERE s.status = 1
        AND d.department_id = ?
    ";

    $params = [$department_id];

    // 学年指定がある場合
    if ($grade !== null) {
        $sql .= " AND s. grade = ?";
        $params[] = $grade;
    }

    // 班指定がある場合
    if ($group !== null) {
        $sql .= " AND s.student_group = ?";
        $params[] = $group;
    }

    $sql .= " ORDER BY s.student_id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * 管理画面用の生徒一覧・検索結果を習得する
 *
 * @param  PDO   $pdo
 * @param  int   $department_id
 * @param  array $filters       検索条件連想配列[student_id, student_name, grade, student_group, status]
 * @return array
 */
function searchStudents(PDO $pdo, int $department_id, array $filters = []): array
{
    $sql = "
        SELECT
            s.student_id,
            s.student_name,
            s.grade,
            s.student_group,
            s.status,
            d.department_name
        FROM students s
        JOIN department d
            ON s.department_id = d.department_id
        WHERE d.department_id = ?
    ";

    $params = [$department_id];

    // 学籍番号が検索欄に入力されている場合だけ条件を追加
    if (!empty($filters['student_id'])) {
        $sql .= " AND s.student_id = ?";
        $params[] = (int)$filters['student_id'];
    }

    // 生徒氏名が検索欄に入力されている場合だけ条件を追加
    if (isset($_GET['student_name']) && $_GET['student_name'] !== '') {
        $sql .= " AND s.student_name LIKE ?";
        $params[] = '%' . $_GET['student_name'] . '%';
    }

    // 学年が選択されている場合だけ条件を追加
    if (isset($_GET['grade']) && $_GET['grade'] !== '') {
        $sql .= " AND grade = ?";
        $params[] = (int)$_GET['grade'];
    }

    // 班が選択されている場合だけ条件を追加
    if (isset($_GET['student_group']) && $_GET['student_group'] !== '') {
        $sql .= " AND s.student_group = ?";
        $params[] = $_GET['student_group'];
    }

    // 在校状況が選択されている場合だけ条件を追加
    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $sql .= " AND s.status= ?";
        $params[] = $_GET['status'];
    }

    $sql .= " ORDER BY s.student_id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}


function searchTeachers(PDO $pdo, int $department_id, array $filters = []): array
{
    $sql = "
        SELECT
            t.teacher_id,
            t.teacher_name,
            d.department_name
        FROM teachers t
        JOIN department d
            ON t.department_id = d.department_id
        WHERE d.department_id = ?
    ";

    $params = [$department_id];

    // 教員番号が検索欄に入力されている場合だけ条件を追加
    if (!empty($filters['teacher_id'])) {
        $sql .= " AND t.teacher_id = ?";
        $params[] = (int)$filters['teacher_id'];
    }

    // 教員氏名が検索欄に入力されている場合だけ条件を追加
    if (isset($_GET['teacher_name']) && $_GET['teacher_name'] !== '') {
        $sql .= " AND t.teacher_name LIKE ?";
        $params[] = '%' . $_GET['teacher_name'] . '%';
    }


    $sql .= " ORDER BY t.teacher_id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}



/**
 * 指定日が含まれる週の「月～金曜日」の日付情報を取得する
 *
 * @param  string $date
 * @return array  [ ['date' => '2026-09-07', 'month' => 9, 'day' => 7, 'week_day' => '月'], ...]
 */
function getWeekDates(string $date): array
{
    $timestamp = strtotime($date);
    $w         = (int)date('w', $timestamp);

    // 月曜日のタイムスタンプを計算
    $diff_to_monday = ($w === 0) ? -6 : 1 - $w;
    $monday_time    = strtotime("{$diff_to_monday} days", $timestamp);

    $week_day_name = ['月', '火', '水', '木', '金'];
    $week_dates    = [];

    for ($i = 0; $i < 5; $i++) {
        $current_time = strtotime("+{$i} days", $monday_time);
        $week_dates[] = [
            'date'     => date('Y-m-d', $current_time),
            'month'    => (int)date('n', $current_time),
            'day'      => (int)date('j', $current_time),
            'week_day' => $week_day_name[$i],
        ];
    }

    return $week_dates;
}

/**
 * 指定された日付リスト（週間）の出欠データを一括取得する
 *
 * @param  PDO   $pdo
 * @param  array $date_list
 * @return array ["student_id_YYYY-MM-DD_period" => attendance_status_id, ...]
 */
function getWeeklyAttendance(PDO $pdo, array $date_list): array
{
    if (empty($date_list)) {
        return [];
    }

    // IN句のプレイスホルダー
    $place_holders = implode(',', array_fill(0, count($date_list), '?'));

    $stmt = $pdo->prepare("
        SELECT student_id, attendance_date, period, attendance_status_id
        FROM attendance
        WHERE attendance_date IN ({$place_holders})
    ");
    $stmt->execute($date_list);
    $records = $stmt->fetchAll();

    $weekly_attendance = [];
    foreach ($records as $rec) {
        $key                     = "{$rec['student_id']}_{$rec['attendance_date']}_{$rec['period']}";
        $weekly_attendance[$key] = (int)$rec['attendance_status_id'];
    }

    return $weekly_attendance;
}

/**
 * 指定年の日本の祝日一覧を取得する
 *
 * @param  int   $year
 * @return array ['YYYY-MM-DD' => '祝日名', ...]
 */
function getJapaneseHolidays(int $year): array
{
    $holidays = [];

    // 固定祝日
    $holidays["{$year}-01-01"] = '元旦';
    $holidays["{$year}-02-11"] = '建国記念の日';
    $holidays["{$year}-02-23"] = '天皇誕生日';
    $holidays["{$year}-04-29"] = '昭和の日';
    $holidays["{$year}-05-03"] = '憲法記念日';
    $holidays["{$year}-05-04"] = 'みどりの日';
    $holidays["{$year}-05-05"] = 'こどもの日';
    $holidays["{$year}-08-11"] = '山の日';
    $holidays["{$year}-11-03"] = '文化の日';
    $holidays["{$year}-11-23"] = '勤労感謝の日';

    // ハッピーマンデー（成人の日:1月第2月曜 / 海の日: 7月第3月曜 / 敬老の日: 9月第3月曜 / スポーツの日: 10月第2月曜）
    $holidays[date('Y-m-d', strtotime("second monday of january {$year}"))]  = '成人の日';
    $holidays[date('Y-m-d', strtotime("third monday of july {$year}"))]      = '海の日';
    $holidays[date('Y-m-d', strtotime("third monday of september {$year}"))] = '敬老の日';
    $holidays[date('Y-m-d', strtotime("second monday of octorber {$year}"))] = '体育の日';

    // 春分の日・秋分の日
    $spring_equinox_day                                            = floor(20.8431 + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));
    $autumn_equinox_day                                            = floor(23.2488 + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));
    $holidays[sprintf('%04d-03-%02d', $year, $spring_equinox_day)] = '春分の日';
    $holidays[sprintf('%04d-09-$02d', $year, $autumn_equinox_day)] = '秋分の日';

    // 国民の休日の判定（祝日に挟まれた平日）
    $citizen_holidays = [];
    foreach ($holidays as $date => $name) {
        $two_days_later = date('Y-m-d', strtotime('+2 days', strtotime($date)));
        $between_day    = date('Y-m-d', strtotime('+1 day', strtotime($date)));

        // 2日後も祝日であり、かつ間の日が祝日でもない場合
        if (isset($holidays[$two_days_later]) && !isset($holidays[$between_day])) {
            if ((int)date('w', strtotime($between_day)) !== 0) {
                $citizen_holidays[$between_day] = '国民の休日';
            }
        }
    }
    $holidays = array_merge($holidays, $citizen_holidays);

    // 振替休日の判定
    $substitute_holidays = [];
    foreach ($holidays as $date) {
        if ((int)date('w', strtotime($date)) === 0) {
            $next_day = date('Y-m-d', strtotime('+1 day', strtotime($date)));
            while (isset($holidays[$next_day]) || isset($substitute_holidays[$next_day])) {
                $next_day = date('Y-m-d', strtotime('+1 day', strtotime($next_day)));
            }
            $substitute_holidays[$next_day] = '振替休日';
        }
    }

    $all_holidays = array_merge($holidays, $substitute_holidays);
    ksort($all_holidays);

    return $all_holidays;
}

/**
 * 指定期間のベースカレンダーを自動生成・初期化する（平日/土日/祝日自動判定)
 *
 * @param  PDO    $pdo
 * @param  string $start_date         開始日 (Y-m-d)
 * @param  string $end_date           終了日 (Y-m-d)
 * @param  int    $default_max_period (初期値7)
 * @return int    登録・更新件数
 */
function generateBaseSchoolCalendar(PDO $pdo, string $start_date, string $end_date, int $default_max_period = 7): int
{
    $start_year = (int)date('Y', strtotime($start_date));
    $end_year   = (int)date('Y', strtotime($end_date));

    // 対象年度の祝日を取得
    $holidays = [];
    for ($y = $start_year; $y <= $end_year; $y++) {
        $holidays = array_merge($holidays, getJapaneseHolidays($y));
    }

    $stmt = $pdo -> prepare("
        INSERT INTO school_calendar (
            calendar_date,
            is_holiday,
            max_period,
            description
        )
        VALUE (
            :calendar_date,
            :is_holiday,
            :max_period,
            :description
        )
        ON DUPLICATE KEY VALUE
            is_holiday = VALUES(is_holiday),
            max_period = VALUES(max_period),
            description = VALUES(description)
    ");

    $start = new DateTime($start_date);
    $end   = new DateTime($end_date);
    $end -> modify('+1 day');   // 終了日も含める
    $interval = new DateInterval('P1D');
    $period   = new DatePeriod($start, $interval, $end);

    $count = 0;
    foreach ($period as $dt) {
        $date_str = $dt -> format('Y-m-d');
        $w        = $dt -> format('w');

        if (isset($holidays[$date_str])) {
            // 祝日（振替休日・国民の休日を含む）
            $is_holiday = 1;
            $max_period = 0;
            $desc       = $holidays[$date_str];
        } elseif ($w === 0) {
            // 日曜
            $is_holiday = 1;
            $max_period = 0;
            $desc       = '日曜日';
        } elseif ($w === 6) {
            // 土曜
            $is_holiday = 1;
            $max_period = 0;
            $desc       = '土曜日';
        } else {
            // 平日授業日
            $is_holiday = 0;
            $max_period = $default_max_period;
            $desc       = '';
        }

        $stmt -> execute([
            ':calendar_date' => $date_str,
            ':is_holiday'    => $is_holiday,
            ':max_period'    => $max_period,
            ':description'   => $desc,
        ]);
        $count++;
    }

    return $count;
}

/**
 * 特定期間の時限数を一括変更する
 *
 * @param  PDO    $pdo
 * @param  string $start_date     開始日 (Y-m-d)
 * @param  string $end_date       終了日 (Y-m-d)
 * @param  int    $new_max_period 新しい時限数
 * @param  string $description    概要・変更理由
 * @return int    登録・更新件数
 */
function updatePeriodForDateRange(PDO $pdo, string $start_date, string $end_date, int $new_max_period, string $description = ''): int
{
    $sql = "
        UPDATE school_calendar
        SET max_period = :max_period,
            description =
                CASE
                    WHEN :desc <> '' THEN :desc
                    ELSE description
                END
        WHERE calendar_Date BETWEEN :start_date AND :end_date
        AND is_holiday = 0    
    ";
    $stmt = $pdo -> prepare($sql);
    $stmt -> execute([
        ':max_period' => $new_max_period,
        ':desc'       => $description,
        ':start_date' => $start_date,
        ':end_date'   => $end_date,
    ]);

    return $stmt -> rowCount();
}

/**
 * 特定の日付を休校日（または授業日）に個別変更する
 *
 * @param  PDO    $pdo
 * @param  string $target_date 対象日 ('Y-m-d')
 * @param  bool   $is_holiday  休校にするかどうか
 * @param  int    $max_period
 * @param  string $description 理由
 * @return bool
 */
function setSpecialCalendar(PDO $pdo, string $target_date, bool $is_holiday, int $max_period, string $description): bool
{
    $stmt = $pdo -> prepare("
        INSERT INTO school_calendar (
            calendar_date,
            is_holiday,
            max_period,
            description
        )
        VALUES (
            :calendar_date,
            :is_holiday,
            :max_period,
            :description
        )
        ON DUPLICATE KEY UPDATE
            is_holiday = VALUE(is_holiday),
            max_period = VALUE(max_period),
            description = VALUES(description)
    ");

    return $stmt -> execute([
        ':calendar_date' => $target_date,
        ':is_holiday'    => $is_holiday ? 1 : 0,
        ':max_period'    => $max_period,
        ':description'   => $description,
    ]);
}
