
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

    $handle = openCsvAsUtf8($tmpPath);

    try {
        // CSVの見出しを確認
        $header = readCsvHeader($handle);


        error_log('CSV header: ' . var_export($header, true));

        $aliases = [
            'subject_name' => ['subject_name', '科目名', '科目'],
            'department_id' => ['department_id', '学科ID', '学科番号']
        ];

        $requiredFields = [
            'subject_name',
            'department_id'
        ];

        $headerMap = mapCsvHeader($header, $aliases, $requiredFields);

        // 全行を読み込み、入力内容を先に検証する
        $subjects = [];
        $lineNumber = 1;




        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $lineNumber++;

            // 空行を読み飛ばす
            if ($row === [null] || $row === []) {
                continue;
            }

            $subjectName = trim($row[$headerMap['subject_name']] ?? '');
            $department_id = trim($row[$headerMap['department_id']] ?? '');

            if ($subjectName === '') {
                throw new RuntimeException(
                    "{$lineNumber}行目：科目名を入力してください。"
                );
            }

            $csvDepartmentId = trim($department_id);

            // 学科アカウントの場合はログイン中の学科IDと照合する
            $sessionDepartmentId = $isAdminAccount
                ? null
                : $departmentId;

            // CSVの学科IDを検証して取得する
            $resolvedDepartmentId = resolveDepartmentId(
                $csvDepartmentId,
                $sessionDepartmentId,
                $isAdminAccount
            );

            // 学科IDを取得できなかった場合
            if ($resolvedDepartmentId === null) {
                throw new RuntimeException(
                    "{$lineNumber}行目：学科情報を取得できません。"
                );
            }


            $subjects[] = [
                'subject_name' => $subjectName,
                'department_id' => $resolvedDepartmentId
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
