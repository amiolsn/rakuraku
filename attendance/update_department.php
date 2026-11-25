<?php
session_start();


header('Content-Type: application/json; charset=UTF-8');

require_once 'db.php';

// JavaScriptから送られてきたJSONデータを受信
$rawInput = file_get_contents('php://input');

error_log('update_department.php に到達しました');
error_log('受信データ: ' . $rawInput);

$data = json_decode($rawInput, true);

// データの存在チェック
if (
    !is_array($data) ||
    empty($data['department_id']) ||
    empty($data['changes']) ||
    !is_array($data['changes'])
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '送信データが不正です。']);
    exit;
}


$department_id = $data['department_id']; //学科番号
$changes = $data['changes'];

$allowdFields = [
    'department_name'
];

//変更内容をチェック
$updateData = [];

foreach ($changes as $field => $value) {
    if (!in_array($field, $allowdFields, true)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '更新できない項目が含まれています。'
        ]);
        exit;
    }

    //名前はNOTNULL
    if ($field === 'department_name') {
        $value = trim((string)$value);

        if ($value === '') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '学科名を入力してください。'
            ]);
            exit;
        }
    }

    $updateData[$field] = $value;
}

error_log(
    'updateData: ' . var_export($updateData, true)
);

if (empty($updateData)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '更新する項目がありません。'
    ]);
    exit;
}


try {
    //更新する項目と値を組み立てる
    $setParts = [];
    foreach ($updateData as $field => $value) {
        $setParts[] = "$field = :$field";
    }


    $sql = "
        UPDATE department
        SET " . implode(', ', $setParts) . "
        WHERE department_id = :department_id
    ";

    $stmt  = $pdo->prepare($sql);

    //更新する項目の値を設定
    foreach ($updateData as $field => $value) {
        if ($value === null) {
            $stmt->bindValue(":$field", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":$field", $value, PDO::PARAM_STR);
        }
    }

    $stmt->bindValue(':department_id', $department_id);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => '学科情報を更新しました。'
    ]);
} catch (PDOException $e) {
    http_response_code(500);

    error_log($e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'データベースの更新に失敗しました'
    ]);
}
