
<?php

/**
 * アップロードされたCSVファイルを確認する
 * @return string CSVの一時ファイルパス
 */

function getUploadedCsvPath(string $fieldName): string
{
    if (
        !isset($_FILES[$fieldName]) ||
        $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK
    ) {
        throw new RuntimeException(
            'CSVファイルを受信できませんでした。'
        );
    }

    return $_FILES[$fieldName]['tmp_name'];
}

/**
 * CSVの1行目から見出しを読み込む
 */
function readCsvHeader($handle): array
{
    $header = fgetcsv($handle, 0, ',', '"', '');

    if ($header === false || $header === [null]) {
        throw new RuntimeException(
            'CSVファイルに見出しがありません。'
        );
    }

    // UTF-8 BOMを除去
    $header[0] = preg_replace(
        '/^\xEF\xBB\xBF/',
        '',
        $header[0]
    );

    return $header;
}

/**
 * CSVの見出しが指定した形式か確認する
 */
function validateCsvHeader(
    array $actual,
    array $expected
): void {
    if ($actual !== $expected) {
        throw new RuntimeException(
            'CSVの列名または列順が正しくありません。'
        );
    }
}
/**
 * CSVの学科IDを検証する
 *
 * @param int $csvDepartmentId CSVに記載された学科ID
 * @param ?int $sessionDepartmentId セッションの学科ID
 * @param bool $isAdminAccount 管理者かどうか
 * @return int 登録に使用する学科ID
 */
function resolveDepartmentId(
    int $csvDepartmentId,
    ?int $sessionDepartmentId,
    bool $isAdminAccount
): int {
    if ($isAdminAccount) {
        // 管理者はCSVの学科IDを使用する
        // 実在する学科IDかどうかも別途確認する
        return $csvDepartmentId;
    }

    if ($sessionDepartmentId === null) {
        throw new RuntimeException(
            'セッションから学科IDを取得できません。'
        );
    }

    if ($csvDepartmentId !== $sessionDepartmentId) {
        throw new RuntimeException(
            'CSVにログイン中の学科とは異なる学科IDが含まれています。'
        );
    }

    return $sessionDepartmentId;
}

function removeUtf8Bom(string $value): string
{
    return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
}
