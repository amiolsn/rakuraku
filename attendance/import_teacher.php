<?php

/**
 * 
 * @var int $departmentId
 * @var int $department_id
 */


require_once 'csv_helper.php';
require_once 'functions.php';
function importTeachers(
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

        if (isset($header[0])) {
            $header[0] = removeUtf8Bom($header[0]);
        }

        // 教員CSVの列名対応表
        $aliases = [
            'teacher_id' => ['teacher_id', '教員番号'],
            'teacher_name' => ['teacher_name', '氏名', '教員氏名'],
            'isAdmin' => ['isAdmin', 'is_admin', '管理者フラグ'],
            'department_id' => ['department_id', '学科ID', '学科番号']
        ];

        // 必須項目
        $requiredFields = [
            'teacher_id',
            'teacher_name',
            'isAdmin',
            'department_id'
        ];

        // CSVの見出しと内部項目を対応付ける
        $headerMap = mapCsvHeader(
            $header,
            $aliases,
            $requiredFields
        );

        // CSV全行を読み込み、先に入力チェックする
        $teachers = [];
        $teacherIds = [];
        $lineNumber = 1;





        // 2行目以降を1行ずつ読み込む
        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $lineNumber++;

            // 空行を読み飛ばす
            if ($row === [null] || $row === []) {
                continue;
            }

            // CSVの見出しに対応する列番号からデータを取得
            $teacherId = trim($row[$headerMap['teacher_id']] ?? '');
            $teacherName = trim($row[$headerMap['teacher_name']] ?? '');
            $isAdmin = trim($row[$headerMap['isAdmin']] ?? '');
            $department_id = trim($row[$headerMap['department_id']] ?? '');

            // 管理者フラグが0または1か確認
            if (!in_array($isAdmin, ['0', '1'], true)) {
                throw new RuntimeException(
                    "{$lineNumber}行目：管理者フラグが不正です。"
                );
            }

            // 文字列から整数に変換
            $isAdmin = (int)$isAdmin;

            // 学科アカウントから管理者は作成できない
            if (!$isAdminAccount && $isAdmin === 1) {
                throw new RuntimeException(
                    "{$lineNumber}行目：管理者を登録する権限がありません。"
                );
            }


            // ここで入力チェックを行う
            // 問題がなければ登録処理を行う
            //必要項目の確認
            //必要項目の確認
            if (
                $teacherId === '' ||
                $teacherName === '' ||
                $department_id === ''
            ) {
                throw new RuntimeException(
                    "{$lineNumber}行目：入力内容を確認してください。"
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

            // 解決した学科IDが正しいか確認
            if ($resolvedDepartmentId <= 0) {
                throw new RuntimeException(
                    "{$lineNumber}行目：学科IDが正しくありません。"
                );
            }


            // CSV内で学籍番号が重複していないか確認
            if (isset($teacherIds[$teacherId])) {
                throw new RuntimeException(
                    "{$lineNumber}行目：教員番号が重複しています。"
                );
            }

            $teacherIds[$teacherId] = true;

            $teachers[] = [
                'teacher_id' => $teacherId,
                'teacher_name' => $teacherName,
                'is_admin' => $isAdmin,
                'department_id' => $resolvedDepartmentId
            ];
        }

        if (count($teachers) === 0) {
            throw new RuntimeException(
                '登録する教員データがありません。'
            );
        }
    } finally {
        fclose($handle);
    }

    // 全行のチェックが終わってからDB登録を開始
    $pdo->beginTransaction();

    try {

        // 教員登録用SQL
        $stmtTeacher = $pdo->prepare("
        INSERT INTO teachers
            (teacher_id, teacher_name, is_admin, user_id, department_id)
        VALUES
            (:teacher_id, :teacher_name, :is_admin, :user_id, :department_id)
    ");

        // 管理者登録用SQL
        $stmtUser = $pdo->prepare("
        INSERT INTO users
            (usertype_id, login_id, password)
        VALUES
            (:usertype_id, :login_id, :password)
    ");

        // CSVから読み込んだ教員を1人ずつ登録
        foreach ($teachers as $teacher) {
            $userId = null;

            // 管理者教員の場合のみusersにも登録
            if ($teacher['is_admin'] === 1) {
                // 初期パスワードをランダムに生成
                $initialPassword = bin2hex(random_bytes(8));
                $passwordHash = password_hash(
                    $initialPassword,
                    PASSWORD_DEFAULT
                );

                $stmtUser->execute([
                    ':usertype_id' => 2,
                    ':login_id' => $teacher['teacher_id'],
                    ':password' => $passwordHash
                ]);

                // 自動採番されたuser_idを取得
                $userId = (int)$pdo->lastInsertId();
            }

            // teachersへ登録
            $stmtTeacher->execute([
                ':teacher_id' => $teacher['teacher_id'],
                ':teacher_name' => $teacher['teacher_name'],
                ':is_admin' => $teacher['is_admin'],
                ':user_id' => $userId,
                ':department_id' => $teacher['department_id']
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
    return count($teachers) . '人の教員を登録しました。';
}
