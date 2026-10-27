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
    int $departmentId,
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
            'teacher_id',
            'teacher_name',
            'isAdmin'
        ]);


        // CSVの値を取得
        $teacherId = trim($row[0] ?? '');
        $teacherName = trim($row[1] ?? '');
        $isAdminText = trim($row[2] ?? '');


        // 管理者フラグの値をチェック
        if (!in_array($isAdminText, [0, 1], true)) {
            echo json_encode([
                'success' => false,
                'message' => '管理者フラグが不正です。'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }


        // 学科の修業年限を取得
        $years = getDepartmentYears($pdo, $departmentId);

        if ($years === 0) {
            throw new RuntimeException(
                '学科の修業年限を取得できません。'
            );
        }

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

            if (count($row) !== 3) {
                throw new RuntimeException(
                    "{$lineNumber}行目：列数が正しくありません。"
                );
            }
            $teacherId = trim($row[0] ?? '');
            $teacherName = trim($row[1] ?? '');
            $isAdmin = trim($row[2] ?? '');

            // ここで入力チェックを行う
            // 問題がなければ登録処理を行う
            //必要項目の確認
            //必要項目の確認
            if (
                $teacherId === '' ||
                $teacherName === ''
            ) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => '入力内容を確認してください。'
                ]);
                exit;
            }

            // 管理者フラグは0か1のみ許可
            if (!in_array($isAdminText, ['0', '1'], true)) {
                throw new RuntimeException(
                    "{$lineNumber}行目：管理者フラグが不正です。"
                );
            }

            $isAdmin = (int)$isAdminText;

            // ※ 実際の管理者判定用セッション項目に置き換える
            $isLoggedInAdmin =
                (int)($_SESSION['user']['usertype'] ?? 0) === 2;

            // 学科アカウントから管理者は作成できない
            if (!$isLoggedInAdmin && $isAdmin === 1) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => '管理者を登録する権限がありません。'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // 学科アカウントはログイン中の学科IDを使用する
            // 管理者は登録対象の学科IDを指定する
            if ($isLoggedInAdmin) {
                $departmentId = $row['department_id'] ?? null;
            } else {
                $departmentId = $_SESSION['user']['department_id'] ?? null;
            }


            if (
                $departmentId === null ||
                !is_numeric($departmentId) ||
                (int)$departmentId <= 0
            ) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => '学科情報を取得できません。'
                ], JSON_UNESCAPED_UNICODE);
                exit;
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
                'department_id' => $departmentId
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

        echo json_encode([
            'success' => true,
            'message' => '教員情報を新規登録しました。'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
    return count($teachers) . '人の教員を登録しました。';
}
