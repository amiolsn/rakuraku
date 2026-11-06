<?php

/**
 * マスタ情報・生徒情報・科目データおよび検索関数
 */

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
    if (isset($filters['student_name']) && $filters['student_name'] !== '') {
        $sql .= " AND s.student_name LIKE ?";
        $params[] = '%' . $filters['student_name'] . '%';
    }

    // 学年が選択されている場合だけ条件を追加
    if (isset($filters['grade']) && $filters['grade'] !== '') {
        $sql .= " AND grade = ?";
        $params[] = (int)$filters['grade'];
    }

    // 班が選択されている場合だけ条件を追加
    if (isset($filters['student_group']) && $filters['student_group'] !== '') {
        $sql .= " AND s.student_group = ?";
        $params[] = $filters['student_group'];
    }

    // 在校状況が選択されている場合だけ条件を追加
    if ($filters['status'] !== '') {
        $sql .= " AND s.status = ?";
        $params[] = $filters['status'];
    }

    $sql .= " ORDER BY s.student_id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

// 教員検索
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

// 科目検索
function searchSubjects(PDO $pdo, int $department_id, array $filters = []): array
{
    $sql = "
        SELECT
            s.subject_id,
            s.subject_name,
            d.department_name
        FROM subjects s
        JOIN department d
            ON s.department_id = d.department_id
        WHERE d.department_id = ?
    ";

    $params = [$department_id];

    // 科目番号が検索欄に入力されている場合だけ条件を追加
    if (!empty($filters['subject_id'])) {
        $sql .= " AND s.subject_id = ?";
        $params[] = (int)$filters['subject_id'];
    }

    // 科目名が検索欄に入力されている場合だけ条件を追加
    if (isset($_GET['subject_name']) && $_GET['subject_name'] !== '') {
        $sql .= " AND s.subject_name LIKE ?";
        $params[] = '%' . $_GET['subject_name'] . '%';
    }


    $sql .= " ORDER BY s.subject_id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}
