<?php
// delete_admin.php 管理者権限の解除API
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

// JavaScriptから送られたJSONデータを受信
$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    sendResponse(false, '送信データが不正です。', 400);
}

// 教員番号を取得
$teacherId = $data['id'] ?? '';

if (!is_string($teacherId) && !is_int($teacherId)) {
    sendResponse(false, '教員番号が不正です。', 400);
}

$teacherId = trim((string)$teacherId);

if ($teacherId === '') {
    sendResponse(false, '教員番号が指定されていません。', 400);
}

try {
    // 管理者フラグを0に更新
    $sql = "
        UPDATE teachers
        SET is_admin = 0
        WHERE teacher_id = :teacher_id
          AND is_admin = 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':teacher_id' => $teacherId
    ]);

    // 管理者として登録されていない、または教員番号が存在しない場合
    if ($stmt->rowCount() === 0) {
        sendResponse(
            false,
            '対象の管理者が見つからないか、すでに管理者権限が解除されています。',
            404
        );
    }

    sendResponse(true, '管理者権限を解除しました。');
} catch (PDOException $e) {
    error_log('管理者権限解除エラー：' . $e->getMessage());

    sendResponse(
        false,
        '管理者権限の解除に失敗しました。データベースのエラーを確認してください。',
        500
    );
}
