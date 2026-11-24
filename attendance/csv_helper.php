
<?php
// CSVのアップロード確認・見出し検証・学科IDの権限チェック・UTF-8 BOM除去を行う共通処理
//csv_helper.php

/**
 * アップロードされたCSVファイルを確認する
 * @return string CSVの一時ファイルパス
 */


/**
 * CSVファイルを読み込み、UTF-8に変換した状態で返す
 *
 * UTF-8・UTF-8 BOM・Shift_JIS・Windows-31Jに対応
 *
 * @param string $tmpPath CSVファイルの一時保存先
 * @return resource UTF-8に変換されたCSVの読み込み用ハンドル
 */
function openCsvAsUtf8(string $tmpPath)
{
    // CSVファイル全体を読み込む
    $content = file_get_contents($tmpPath);

    if ($content === false) {
        throw new RuntimeException('CSVファイルを読み込めません。');
    }

    if ($content === '') {
        throw new RuntimeException('CSVファイルが空です。');
    }

    // UTF-8 BOMを除去
    if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
        $content = substr($content, 3);
    }

    // 文字コードを判定
    $encoding = mb_detect_encoding(
        $content,
        ['UTF-8', 'SJIS-win'],
        true
    );

    if ($encoding === false) {
        throw new RuntimeException(
            '対応していない文字コードです。UTF-8またはShift_JISで保存してください。'
        );
    }

    // UTF-8以外ならUTF-8に変換
    if ($encoding !== 'UTF-8') {
        $content = mb_convert_encoding(
            $content,
            'UTF-8',
            $encoding
        );
    }

    // メモリ上に一時的な読み込み用ファイルを作る
    $handle = fopen('php://temp', 'r+');

    if ($handle === false) {
        throw new RuntimeException(
            'CSVファイルの読み込み準備に失敗しました。'
        );
    }

    // 変換したCSVデータを書き込む
    if (fwrite($handle, $content) === false) {
        fclose($handle);
        throw new RuntimeException(
            'CSVデータの変換に失敗しました。'
        );
    }

    // 読み込み位置を先頭に戻す
    rewind($handle);

    return $handle;
}

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
    //CSVの見出しを読み込む　
    //$handle:読み込むファイル
    //0:行の長さの上限を指定しない
    //,:カンマ区切り
    //":囲み文字にダブルクォーテーションを使用
    //'':エスケープ文字を使用しない
    $header = fgetcsv($handle, 0, ',', '"', '');

    if ($header === false || $header === [null]) {
        throw new RuntimeException(
            'CSVファイルに見出しがありません。'
        );
    }

    // UTF-8 BOMを除去
    //preg_replace()：正規表現を使って文字列を置換する関数
    ///^\xEF\xBB\xBF/：文字列の先頭にあるUTF-8 BOMを指定
    //''：空文字に置き換える
    //$header[0]：見出しの最初の項目
    $header[0] = preg_replace(
        '/^\xEF\xBB\xBF/',
        '',
        $header[0]
    );

    //見出しを返す
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
 * CSVの見出しを内部項目と列番号に対応付ける
 *
 * @param array $header CSVから読み込んだ見出し
 * @param array $aliases 内部項目と許可する列名の対応表
 * @param array $requiredFields 必須項目の一覧
 * @return array 内部項目とCSVの列番号の対応表
 */
function mapCsvHeader(
    array $header,
    array $aliases,
    array $requiredFields
): array {
    // 内部項目と列番号を対応付ける配列
    $headerMap = [];

    // CSVの見出しを1つずつ確認する
    foreach ($header as $index => $columnName) {

        // 見出しの前後にある空白を除去
        $columnName = trim($columnName);

        // 対応表を確認する
        foreach ($aliases as $field => $names) {

            // 許可された列名に一致するか確認
            if (in_array($columnName, $names, true)) {

                // 同じ内部項目に対応する列が既に存在する場合
                if (isset($headerMap[$field])) {
                    throw new RuntimeException(
                        "CSVに「{$field}」に対応する列が複数あります。"
                    );
                }

                // 内部項目とCSVの列番号を対応付ける
                $headerMap[$field] = $index;
            }
        }
    }

    // 必須項目がすべて存在するか確認
    foreach ($requiredFields as $field) {

        if (!isset($headerMap[$field])) {
            throw new RuntimeException(
                "CSVに必須列「{$field}」がありません。"
            );
        }
    }

    // 対応表を返す
    return $headerMap;
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
//・文字コードの判定・UTF-8への変換
// ・BOM除去
// ・見出しの読み込み
// ・列名の照合
// ・必須列の確認
// ・列名に対応するデータの取得
