
<?php
require_once 'csv_helper.php';

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

// テストするCSVファイル
$files = [
    'test_student.csv'
];

foreach ($files as $file) {
    echo "【{$file}】\n";

    try {
        // UTF-8に変換してCSVを開く
        $handle = openCsvAsUtf8($file);

        // CSVの見出しを読み込む
        $header = readCsvHeader($handle);

        // 見出しを内部項目と列番号に対応付ける
        $headerMap = mapCsvHeader(
            $header,
            $aliases,
            $requiredFields
        );

        // 対応表を表示
        echo "見出しの対応結果：\n";
        print_r($headerMap);

        // データ行を読み込む
        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            echo "学籍番号：" . $row[$headerMap['student_id']] . "\n";
            echo "氏名：" . $row[$headerMap['student_name']] . "\n";
            echo "学年：" . $row[$headerMap['grade']] . "\n";
            echo "----------------\n";
        }

        fclose($handle);
    } catch (Throwable $e) {
        echo 'エラー：' . $e->getMessage() . "\n";
    }
}
