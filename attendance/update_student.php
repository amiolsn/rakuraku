<?php
session_start();

//save_attendance.php 出欠データの保存・更新API
header('Content-Type: application/json; charset=UTF-8');

require_once 'db.php';

// JavaScriptから送られてきたJSONデータを受信
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// データの存在チェック
if (
    !is_array($data) ||
    empty($data['student_id']) ||
    empty($data['changes']) ||
    !is_array($data['changes'])
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '送信データが不正です。']);
    exit;
}


$student_id = $data['student_id']; //学籍番号
$changes = $data['changes'] ;

$allowdFields = [
    'student_name',
    'student_group',
    'status'
];

//変更内容をチェック
$updateData = [];

foreach($changes as $field => $value) {
    if(!in_array($field, $allowdFields, true)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => '更新できない項目が含まれています。'
        ]);
        exit;
    }

    //名前はNOTNULL
    if($field === 'student_name') {
        $value = trim((String)$value);
        
        if($value === '') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '生徒名を入力してください。'
            ]); 
            exit;
        }
    }

    //班は空欄ならNULL
    if($field === 'student_group') {
        $value = trim((String)$value);
        $value = ($value === '') ? null : $value;
    }

    //在校・大綱の値は０または１
    if($field === 'status'){
 error_log('status value: ' . var_export($value, true));
    error_log('status type: ' . gettype($value));


       if (!in_array((string)$value, ['0', '1'], true)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '在校状態の値が不正です。'
            ]);
            exit;
        }
        $value = (int)$value;
    }
    $updateData[$field] = $value;
}

try {
    //更新する項目と値を組み立てる
    $setParts = [];
    foreach($updateData as $field => $value) {
        $setParts[] = "$field = :$field";
    }

    $sql = "
        UPDATE students
        SET " . implode(', ', $setParts) ."
        WHERE student_id = :student_id
    ";

    $stmt  = $pdo->prepare($sql);

    //更新する項目の値を設定
    foreach($updateData as $field => $value) {
        $stmt->bindValue(":$field", $value);
    }

    $stmt->bindValue(':student_id', $student_id);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => '生徒情報を更新しました。'
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    error_log($e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'データベースの更新に失敗しました'
    ]);
}
