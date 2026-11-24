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

    // 文字コードをUTF-8に変換してCSVを開く
    $handle = openCsvAsUtf8($tmpPath);

    // 1行目の見出しを読み飛ばす
    try {
        // 1行目の見出しを読み込む
        $header = readCsvHeader($handle);

        // 生徒CSVの列名対応表
        $aliases = [
            'student_id' => ['student_id', '学籍番号'],
            'department_id' => ['department_id', '学科ID', '学科番号'],
            'student_name' => ['student_name', '氏名', '生徒氏名'],
            'grade' => ['grade', '学年'],
            'student_group' => ['student_group', 'クラス', '組'],
            'status' => ['status', '在校状況', '在籍状況']
        ];

        // 必須項目
        $requiredFields = [
            'student_id',
            'department_id',
            'student_name',
            'grade',
            'student_group',
            'status'
        ];

        // CSVの見出しと内部項目を対応付ける
        $headerMap = mapCsvHeader(
            $header,
            $aliases,
            $requiredFields
        );



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

            //student_idに対応付けている別名をtrimするようにしている
            $studentId = trim($row[$headerMap['student_id']] ?? '');
            $department_id = trim($row[$headerMap['department_id']] ?? '');
            $studentName = trim($row[$headerMap['student_name']] ?? '');
            $grade = trim($row[$headerMap['grade']] ?? '');
            $studentGroup = trim($row[$headerMap['student_group']] ?? '');
            $status = trim($row[$headerMap['status']] ?? '');

            // ここで入力チェックを行う
            // 問題がなければ登録処理を行う
            //必要項目の確認
            if (
                $studentId === '' ||
                $department_id === '' ||
                $studentName === '' ||
                !ctype_digit($grade) ||
                (int)$grade < 1 ||
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

            // CSVの学科IDが数字か確認
            if (!ctype_digit($csvDepartmentId) || (int)$csvDepartmentId <= 0) {
                throw new RuntimeException(
                    "{$lineNumber}行目：学科IDが正しくありません。"
                );
            }

            // 管理者の場合はセッションの学科IDを使用しない
            if ($isAdminAccount) {
                $sessionDepartmentId = null;
            } else {
                $sessionDepartmentId = $departmentId;
            }

            // CSVの学科IDとログイン中の学科IDを照合
            $resolvedDepartmentId = resolveDepartmentId(
                (int)$csvDepartmentId,
                $sessionDepartmentId,
                $isAdminAccount
            );

            // 確定した学科の修業年限を取得
            $years = getDepartmentYears($pdo, $resolvedDepartmentId);

            if ($years === 0) {
                throw new RuntimeException(
                    "{$lineNumber}行目：学科の修業年限を取得できません。"
                );
            }

            // 学年が修業年限の範囲内か確認
            if ((int)$grade > $years) {
                throw new RuntimeException(
                    "{$lineNumber}行目：学年が学科の修業年限を超えています。"
                );
            }

            // 解決した学科IDが正しいか確認
            if ($resolvedDepartmentId <= 0) {
                throw new RuntimeException(
                    "{$lineNumber}行目：学科IDが正しくありません。"
                );
            }


            $studentIds[$studentId] = true;

            $students[] = [
                'student_id' => $studentId,
                'department_id' => $resolvedDepartmentId,
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
