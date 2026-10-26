<?php
// functions.php

/**
 * 指定学科の科目一覧を取得する
 * 
 * @param PDO $pdo
 * @param int $departmentId
 * @return array 
 */

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
