
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
