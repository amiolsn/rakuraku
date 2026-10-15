<?php
//save_attendance.php 出欠データの保存・更新API
header('Content-Type: application/json; charset=UTF-8');

require_once 'db.php';

// JavaScriptから送られてきたJSONデータを受信
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// データの存在チェック
if(!$data || empty($data['record']) || empty($data['target_date'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '送信データが不正です。']);
    exit;
}

$target_date = $data['target_date'];
$period_setting = $data['periods']; // 時限ごとの科目・教員
$records = $data['records'];        // 出欠レコードの配列

try {
    // トランザクションの開始
    $pdo -> beginTransaction();

    // 登録・更新SQL
    $sql = "
        INSERT INTO attendance (
            student_id,
            attendance_date,
            period,
            subject_id,
            teachers_id,
            attendance_status_id
        ) VALUES (
            :student_id,
            :attendance_date,
            :period,
            :subject_id,
            :teacher_id,
            :attendance_status_id
        )
        ON DUPLICATE KEY UPDATE
            subject_id = VALUE(subject_id),
            teachers_id = VALUE(teachers_id),
            attendance_status_id = VALUE(attendance_status_id)
    ";

     $stmt = $pdo->prepare($sql);

    // ループして保存を実行
    foreach($records as $item) {
        $p = $item['period'];   // ここから続き
    }
} catch(Exception $e) {}

?>