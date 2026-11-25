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

        // 学科CSVの列名対応表
        $aliases = [
            'department_id' => ['department_id', '学科ID', '学科番号'],
            'user_id' => ['user_id', 'ユーザID', 'ユーザーID'],
            'department_name' => ['department_name', '学科名']
        ];

        // 必須項目
        $requiredFields = [
            'department_id',
            'user_id',
            'department_name'
        ];

        // CSVの見出しと内部項目を対応付ける
        $headerMap = mapCsvHeader(
            $header,
            $aliases,
            $requiredFields
        );



        // CSV全行を読み込み、先に入力チェックする
        $departments = [];
        $departmentIds = [];
        $lineNumber = 1;


        // 2行目以降を1行ずつ読み込む
        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $lineNumber++;

            // 空行を読み飛ばす
            if ($row === [null] || $row === []) {
                continue;
            }

            // 学科CSVの各項目を取得し、前後の空白を除去する
            $departmentId = trim($row[$headerMap['department_id']] ?? '');
            $userId = trim($row[$headerMap['user_id']] ?? '');
            $departmentName = trim($row[$headerMap['department_name']] ?? '');

            // ここで入力チェックを行う
            // 問題がなければ登録処理を行う
            // 必須項目の確認
            if (
                $departmentId === '' ||
                $departmentName === ''
            ) {
                throw new RuntimeException(
                    "{$lineNumber}行目：入力内容を確認してください。"
                );
            }

            // 学科番号が数字か確認
            if (!ctype_digit($departmentId) || (int)$departmentId <= 0) {
                throw new RuntimeException(
                    "{$lineNumber}行目：学科番号が正しくありません。"
                );
            }

            // CSV内で学科番号が重複していないか確認
            if (isset($departmentIds[$departmentId])) {
                throw new RuntimeException(
                    "{$lineNumber}行目：学科番号が重複しています。"
                );
            }

            $departmentIds[$departmentId] = true;

            $departments[] = [
                'department_id' => (int)$departmentId,
                'department_name' => $departmentName
            ];
        }

        if (count($departments) === 0) {
            throw new RuntimeException(
                '登録する学科データがありません。'
            );
        }
    } finally {
        fclose($handle);
    }

    // 全行のチェックが終わってからDB登録を開始
    $pdo->beginTransaction();

    try {

        // usersへの登録
        $stmtUser = $pdo->prepare("
            INSERT INTO users
            (usertype_id, login_id, password)
            VALUES
            (:usertype_id, :login_id, :password)
        ");

        // departmentへの登録
        $stmtDepartment = $pdo->prepare("
            INSERT INTO department
            (department_id, user_id, department_name)
            VALUES
            (:department_id, :user_id, :department_name)
        ");

        foreach ($departments as $department) {

            // 初期パスワードを生成してハッシュ化
            $initialPassword = bin2hex(random_bytes(8));

            $passwordHash = password_hash(
                $initialPassword,
                PASSWORD_DEFAULT
            );

            // usersに登録
            $stmtUser->execute([
                ':usertype_id' => 1, // 要確認：学科アカウントのusertype_id
                ':login_id' => 'dept' + $department['department_id'],
                ':password' => $passwordHash
            ]);

            // 自動採番されたuser_idを取得
            $userId = $pdo->lastInsertId();

            // departmentに登録
            $stmtDepartment->execute([
                ':department_id' => $department['department_id'],
                ':user_id' => $userId,
                ':department_name' => $department['department_name']
            ]);
        }

        // 全件成功したら確定
        $pdo->commit();
    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }

    return count($departments) . '件の学科を登録しました。';
}
