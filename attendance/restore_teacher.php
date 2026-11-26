
<?php
// restore_teacher.php 教員再有効化API
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

// 教員IDを取得
$teacherId = $data['id'] ?? '';

if (
    (!is_string($teacherId) && !is_int($teacherId))
    || !ctype_digit((string)$teacherId)
    || (int)$teacherId <= 0
) {
    sendResponse(false, '教員IDが正しくありません。', 400);
}

try {

    // 教員を再有効化
    $sql = "
        UPDATE teachers
        SET is_active = 1
        WHERE teacher_id = :teacher_id
          AND is_active = 0
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':teacher_id' => $teacherId
    ]);

    // 更新対象がなかった場合
    if ($stmt->rowCount() === 0) {
        sendResponse(
            false,
            '対象の教員が見つからないか、すでに有効になっています。',
            404
        );
    }

    sendResponse(true, '教員を再有効化しました。');
} catch (PDOException $e) {

    error_log('教員再有効化エラー：' . $e->getMessage());

    sendResponse(
        false,
        '教員の再有効化に失敗しました。',
        500
    );
}
