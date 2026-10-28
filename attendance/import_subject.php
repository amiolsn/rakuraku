
<?php

require_once __DIR__ . '/csv_helper.php';

/**
 * CSVから科目を一括登録する
 *
 * @param PDO $pdo データベース接続
 * @param int $departmentId ログイン中の学科ID
 * @param string $tmpPath CSVの一時ファイルパス
 * @return string 登録結果のメッセージ
 */
function importSubjects(
    PDO $pdo,
    ?int $departmentId,
    bool $isAdminAccount,
    string $tmpPath
): string {

    // if ($departmentId <= 0) {
    //     throw new RuntimeException('学科情報を取得できません。');
    // }

    $handle = fopen($tmpPath, 'r');
    if ($handle === false) {
        throw new RuntimeException('CSVファイルを開けません。');
    }

    try {
        // CSVの見出しを確認
        $header = readCsvHeader($handle);

        if (isset($header[0])) {
            $header[0] = removeUtf8Bom($header[0]);
        }

        error_log('CSV header: ' . var_export($header, true));

        validateCsvHeader($header, [
            'subject_name',
            'department_id'
        ]);

        // 全行を読み込み、入力内容を先に検証する
        $subjects = [];
        $lineNumber = 1;




        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $lineNumber++;

            // 空行を読み飛ばす
            if ($row === [null] || $row === []) {
                continue;
            }

            if (count($row) !== 2) {
                throw new RuntimeException(
                    "{$lineNumber}行目：列数が正しくありません。"
                );
            }

            $subjectName = trim($row[0] ?? '');
            $department_id = trim($row[1] ?? '');

            if ($subjectName === '') {
                throw new RuntimeException(
                    "{$lineNumber}行目：科目名を入力してください。"
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



            $subjects[] = [
                'subject_name' => $subjectName,
                'department_id' => $rtdepartmentId
            ];
        }

        if (count($subjects) === 0) {
            throw new RuntimeException(
                '登録する科目データがありません。'
            );
        }
    } finally {
        fclose($handle);
    }

    // 全行の検証後にDB登録を開始
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            INSERT INTO subjects (
                subject_name,
                department_id
            )
            VALUES (
                :subject_name,
                :department_id
            )
        ");

        foreach ($subjects as $subject) {
            $stmt->execute([
                ':subject_name' => $subject['subject_name'],
                ':department_id' => $subject['department_id']
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }

    return count($subjects) . '件の科目を登録しました。';
}
