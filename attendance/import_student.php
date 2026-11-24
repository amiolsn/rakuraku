<?php

/**
 * 
 * @var int $departmentId
 * @var int $department_id
 */


require_once 'csv_helper.php';
require_once 'functions.php';
function importStudents(
    PDO $pdo,
    ?int $departmentId,
    bool $isAdminAccount,
    string $tmpPath
): string {

    //CSVを読み込む
    //列名やデータの形式を確認
    //データベースに登録する(usersから)
    $handle = fopen($tmpPath, 'r');
    if ($handle === false) {
        throw new RuntimeException('CSVファイルを開けません。');
    }

    // 1行目の見出しを読み飛ばす
    try {
        // 1行目の見出しを読み込む
        $header = readCsvHeader($handle);

        validateCsvHeader($header, [
            'student_id',
            'department_id',
            'student_name',
            'grade',
            'student_group',
            'status'
        ]);


        // 学科の修業年限を取得
        $years = getDepartmentYears($pdo, $departmentId);

        if ($years === 0) {
            throw new RuntimeException(
                '学科の修業年限を取得できません。'
            );
        }

        // CSV全行を読み込み、先に入力チェックする
        $students = [];
        $studentIds = [];
        $lineNumber = 1;


        // 2行目以降を1行ずつ読み込む
        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $lineNumber++;

            // 空行を読み飛ばす
            if ($row === [null] || $row === []) {
                continue;
            }

            if (count($row) !== 6) {
                throw new RuntimeException(
                    "{$lineNumber}行目：列数が正しくありません。"
                );
            }
            $studentId = trim($row[0] ?? '');
            $department_id = trim($row[1] ?? '');
            $studentName = trim($row[2] ?? '');
            $grade = trim($row[3] ?? '');
            $studentGroup = trim($row[4] ?? '');
            $status = trim($row[5] ?? '');

            // ここで入力チェックを行う
            // 問題がなければ登録処理を行う
            //必要項目の確認
            if (
                $studentId === '' ||
                $department_id === '' ||
                $studentName === '' ||
                !ctype_digit($grade) ||
                (int)$grade < 1 ||
                (int)$grade > $years ||
                !in_array($status, ['0', '1'], true)
            ) {
                http_response_code(400);
                throw new RuntimeException(
                    "{$lineNumber}行目：入力内容を確認してください。"
                );
            }

            // CSV内で学籍番号が重複していないか確認
            if (isset($studentIds[$studentId])) {
                throw new RuntimeException(
                    "{$lineNumber}行目：学籍番号が重複しています。"
                );
            }

            $csvDepartmentId = $department_id;

            if ($isAdminAccount) {
                $sessionDepartmentId = null;
            } else {
                $sessionDepartmentId = $departmentId;
            }


            if (
                $rtdepartmentId = resolveDepartmentId(
                    $csvDepartmentId,
                    $sessionDepartmentId,
                    $isAdminAccount
                ) === null ||
                !is_numeric($rtdepartmentId) ||
                (int)$rtdepartmentId <= 0
            ) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => '学科情報を取得できません。'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $studentIds[$studentId] = true;

            $students[] = [
                'student_id' => $studentId,
                'department_id' => $rtdepartmentId,
                'student_name' => $studentName,
                'grade' => (int)$grade,
                'student_group' => $studentGroup === ''
                    ? null
                    : $studentGroup,
                'status' => (int)$status
            ];
        }

        if (count($students) === 0) {
            throw new RuntimeException(
                '登録する生徒データがありません。'
            );
        }
    } finally {
        fclose($handle);
    }

    // 全行のチェックが終わってからDB登録を開始
    $pdo->beginTransaction();

    try {
        $stmtUser = $pdo->prepare("
    INSERT INTO users
    (usertype_id, login_id, password)
    VALUES
    (:usertype_id, :login_id, :password)
    ");

        $stmtStudent = $pdo->prepare("
    INSERT INTO students
    (student_id, department_id, user_id,
    student_name, grade, student_group, status)
    VALUES
    (:student_id, :department_id, :user_id,
    :student_name, :grade, :student_group, :status)
    ");

        foreach ($students as $student) {
            // 初期パスワードを生成してハッシュ化
            $initialPassword = bin2hex(random_bytes(8));
            $passwordHash = password_hash(
                $initialPassword,
                PASSWORD_DEFAULT
            );

            // usersに登録
            $stmtUser->execute([
                ':usertype_id' => 3,
                ':login_id' => $student['student_id'],
                ':password' => $passwordHash
            ]);

            // 自動採番されたuser_idを取得
            $userId = $pdo->lastInsertId();

            // studentsに登録
            $stmtStudent->execute([
                ':student_id' => $student['student_id'],
                ':department_id' => $student['department_id'],
                ':user_id' => $userId,
                ':student_name' => $student['student_name'],
                ':grade' => $student['grade'],
                ':student_group' => $student['student_group'],
                ':status' => $student['status']
            ]);
        }

        // 全員の登録に成功したら確定
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
    return count($students) . '人の生徒を登録しました。';
}
