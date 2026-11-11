<?php

/**
 * 学校カレンダー生成・祝日計算・時間数管理関数
 */

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
    $holidays["{$year}-06-23"] = '慰霊の日';
    $holidays["{$year}-08-11"] = '山の日';
    $holidays["{$year}-11-03"] = '文化の日';
    $holidays["{$year}-11-23"] = '勤労感謝の日';

    // ハッピーマンデー（成人の日:1月第2月曜 / 海の日: 7月第3月曜 / 敬老の日: 9月第3月曜 / スポーツの日: 10月第2月曜）
    $holidays[date('Y-m-d', strtotime("second monday of january {$year}"))]  = '成人の日';
    $holidays[date('Y-m-d', strtotime("third monday of july {$year}"))]      = '海の日';
    $holidays[date('Y-m-d', strtotime("third monday of september {$year}"))] = '敬老の日';
    $holidays[date('Y-m-d', strtotime("second monday of october {$year}"))]  = '体育の日';

    // 春分の日・秋分の日
    $spring_equinox_day                                            = floor(20.8431 + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));
    $autumn_equinox_day                                            = floor(23.2488 + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));
    $holidays[sprintf('%04d-03-%02d', $year, $spring_equinox_day)] = '春分の日';
    $holidays[sprintf('%04d-09-%02d', $year, $autumn_equinox_day)] = '秋分の日';

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

    $stmt = $pdo->prepare("
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
        ON DUPLICATE KEY UPDATE
            is_holiday = VALUES(is_holiday),
            max_period = VALUES(max_period),
            description = VALUES(description)
    ");

    $start = new DateTime($start_date);
    $end   = new DateTime($end_date);
    $end->modify('+1 day');   // 終了日も含める
    $interval = new DateInterval('P1D');
    $period   = new DatePeriod($start, $interval, $end);

    $count = 0;
    foreach ($period as $dt) {
        $date_str = $dt->format('Y-m-d');
        $w        = (int)$dt->format('w');

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

        $stmt->execute([
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
            description = COALESCE(NULLIF(:desc, ''), description)
        WHERE calendar_date BETWEEN :start_date AND :end_date
        AND is_holiday = 0    
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':max_period' => $new_max_period,
        ':desc'       => $description,
        ':start_date' => $start_date,
        ':end_date'   => $end_date,
    ]);

    return $stmt->rowCount();
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
function setSpecialCalendarDate(PDO $pdo, string $target_date, bool $is_holiday, int $max_period, string $description): bool
{
    $stmt = $pdo->prepare("
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
            is_holiday = VALUES(is_holiday),
            max_period = VALUES(max_period),
            description = VALUES(description)
    ");

    return $stmt->execute([
        ':calendar_date' => $target_date,
        ':is_holiday'    => $is_holiday ? 1 : 0,
        ':max_period'    => $max_period,
        ':description'   => $description,
    ]);
}
