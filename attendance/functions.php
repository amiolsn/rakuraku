<?php
// functions.php

/** 
 * @param PDO $pdo
 * @param int $department_id
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

/**
 * 対象日のカレンダー情報(休校日判定、理由、最大時限数)を取得する
 * 
 * @param PDO $pdo
 * @param string $target_date
 * @param int $default_max_period
 * @return array ['is_holiday' => bool, 'description' => string, 'max_period' => int]
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
    $is_holiday = false;
    $description = '';
    if ($calendar_info && !empty($calendar_info['is_holiday'])) {
        $is_holiday = true;
        $description = $calendar_info['description'];
    }

    // 時限数の動的決定
    $max_period = (!empty($calendar_info['max_period']) && $calendar_info['max_period'] > 0)
        ? (int)$calendar_info['max_period']
        : $default_max_period;

    return [
        'is_holiday' => $is_holiday,
        'description' => $description,
        'max_period' => $max_period,
    ];
}

/**
 * 対象日の出欠データおよび時限ごとの初期設定（科目・教員）を取得・構築する
 * 
 * @param PDO $pdo
 * @param string $target_date
 * @param int $max_period
 * @return array [
 *     'existing_attendance' => array, // [ "studentId_period" => statusId, ... ]
 *     'existing_periods'    => array  // [ period => ['subject_id' => ..., teacher_id' => ...], ... ]
 * ]
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
    $period_subjects = [];
    $period_teachers = [];

    // DBからとれたデータを箱に入れる
    foreach ($saved_records as $rec) {
        // 学籍番号_時限をキーにしてステータスを記憶
        $p = $rec['period'];
        $key = "{$rec['student_id']}_{$p}";
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
        $subj_keys = isset($period_subjects[$p]) ? array_keys($period_subjects[$p]) : [];
        $match_subj = (count($subj_keys) === 1) ? $subj_keys[0] : '';

        // 教員の判定
        $teach_keys = isset($period_teachers[$p]) ? array_keys($period_teachers[$p]) : [];
        $match_teach = (count($teach_keys) === 1) ? $teach_keys[0] : '';

        $existing_periods[$p] = [
            'subject_id' => $match_subj,
            'teacher_id' => $match_teach,
        ];
    }

    return [
        'existing_attendance' => $existing_attendance,
        'existing_periods' => $existing_periods,
    ];
}

/**
 * 出欠入力用の生徒一覧を取得する（在校生・学年/班の絞り込み対応)
 * 
 * @param PDO $pdo
 * @param int $department_id
 * @param int|null $grade
 * @param string|null $group
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
