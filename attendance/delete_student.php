
<?php

//save_attendance.php 出欠データの保存・更新API
header('Content-Type: application/json; charset=UTF-8');

require_once 'db.php';
require_once 'auth.php';

// JSONレスポンスを返す共通関数
function sendResponse($success, $message, $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode([
        'success' => $success,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// POST以外は受け付けない
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, '不正なリクエストです。', 405);
}


// JSONデータを受信
$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    sendResponse(false, '送信データが不正です。', 400);
}

// 生徒IDを取得
$studentId = $data['id'] ?? '';


//入力値を取得
$studentId = trim(($data['id'] ?? ''));

if (
    (!is_string($studentId) && !is_int($studentId))
    || !ctype_digit((string)$studentId)
    || (int)$studentId <= 0
) {
    sendResponse(false, '生徒IDが正しくありません。', 400);
}

try {
    // 生徒を退校状態に変更する
    $sql = "
        UPDATE students
        SET status = 0
        WHERE student_id = :student_id
          AND status = 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':student_id' => $studentId
    ]);

    // 更新対象がなかった場合
    if ($stmt->rowCount() === 0) {
        sendResponse(
            false,
            '対象の生徒が見つからないか、すでに退校・卒業しています。',
            404
        );
    }

    sendResponse(true, '生徒を退校にしました。');
} catch (PDOException $e) {

    error_log('生徒退校エラー：' . $e->getMessage());

    sendResponse(
        false,
        '生徒の退校処理に失敗しました。',
        500
    );
}
