
<?php

//save_attendance.php 出欠データの保存・更新API
header('Content-Type: application/json; charset=UTF-8');

require_once 'db.php';
require_once 'auth.php';


// JavaScriptから送られてきたJSONデータを受信
$rawInput = file_get_contents('php://input');

$data = json_decode($rawInput, true);

// データの存在チェック
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '送信データが不正です。']);
    exit;
}

//入力値を取得
$subjectId = trim(($data['id'] ?? ''));

if ($subjectId === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '生徒IDが指定されていません。'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


try {
    $sql = "
        DELETE FROM subjects
        WHERE subject_id = :subject_id
    ";



    $stmt  = $pdo->prepare($sql);

    $stmt->execute([
        ':subject_id' => $subjectId
    ]);

    // 該当する生徒が存在したか確認
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '削除対象の科目が見つかりません。'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => '科目情報を削除しました。'
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('教員削除エラー：' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '科目情報の削除に失敗しました。'
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('科目削除処理エラー：' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '科目削除処理中にエラーが発生しました。'
    ], JSON_UNESCAPED_UNICODE);
}
