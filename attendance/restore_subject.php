
<?php
// restore_subject.php 科目再有効化API
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

// 科目IDを取得
$subjectId = $data['id'] ?? '';

if (
    (!is_string($subjectId) && !is_int($subjectId))
    || !ctype_digit((string)$subjectId)
    || (int)$subjectId <= 0
) {
    sendResponse(false, '科目番号が正しくありません。', 400);
}

try {

    // 科目を再有効化
    $sql = "
        UPDATE subjects
        SET is_active = 1
        WHERE subject_id = :subject_id
          AND is_active = 0
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':subject_id' => $subjectId
    ]);

    // 更新対象がなかった場合
    if ($stmt->rowCount() === 0) {
        sendResponse(
            false,
            '対象の科目が見つからないか、すでに有効になっています。',
            404
        );
    }

    sendResponse(true, '科目を再有効化しました。');
} catch (PDOException $e) {

    error_log('科目再有効化エラー：' . $e->getMessage());

    sendResponse(
        false,
        '科目の再有効化に失敗しました。',
        500
    );
}
