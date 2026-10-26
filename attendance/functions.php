<?php
// functions.php

/** 
 * @param PDO $pdo
 * @param int $departmentId
 * @return array 
 */

// 指定学科の科目一覧を取得する
function getSubjectsByDepartment(PDO $pdo, int $departmentId): array
{
    $stmt = $pdo->prepare("
        SELECT subject_id, subject_name
        FROM subjects s
        JOIN department d
            ON s.department_id = d.department_id
        WHERE d.department_id = ?
        ORDER BY subject_id ASC 
    ");
    $stmt->execute([$departmentId]);
    return $stmt->fetchAll();
}

// 教員一覧を取得
function getTeachersByDepartment(PDO $pdo, int $departmentId): array
{
    $stmt = $pdo->prepare("
        SELECT teacher_id, teacher_name
        FROM teachers t
        JOIN department d 
            ON t.department_id = d.department_id
        WHERE d.department_id = ?
        ORDER BY teacher_id ASC
    ");
    $stmt->execute([$departmentId]);
    return $stmt->fetchAll();
}

// 修業年数の取得
function getDepartmentYears(PDO $pdo, int $departmentId): int
{
    $stmt = $pdo->prepare("
        SELECT MAX(grade) AS term
        FROM course_requirements
        WHERE department_id = ?
    ");
    $stmt->execute([$departmentId]);
    $result = $stmt->fetch();
    return (int)($result['term'] ?? 0);
}

// 班一覧の取得
function getStudentGroup(PDO $pdo, int $departmentId): array
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
    $stmt->execute([$departmentId]);
    return $stmt->fetchAll();
}

/**
 * 対象日のカレンダー情報(休校日判定、理由、最大時限数)を取得する
 * 
 * @param PDO $pdo
 * @param string $targetDate
 * @param int $defaultMaxPeriod
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
