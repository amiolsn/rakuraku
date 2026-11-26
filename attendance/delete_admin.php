
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

// JSONデータを受信
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
    // トランザクション開始
    $pdo->beginTransaction();

    // 対象の管理者情報を取得
    $sql = "
        SELECT teacher_id, user_id
        FROM teachers
        WHERE teacher_id = :teacher_id
          AND is_admin = 1
        FOR UPDATE
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':teacher_id' => $teacherId
    ]);

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        throw new Exception(
            '対象の管理者が見つからないか、すでに管理者権限が解除されています。'
        );
    }

    // 管理者権限を解除
    $sql = "
        UPDATE teachers
        SET is_admin = 0
        WHERE teacher_id = :teacher_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':teacher_id' => $teacherId
    ]);

    // ユーザーアカウントを無効化
    if ($admin['user_id'] !== null) {
        $sql = "
            UPDATE users
            SET is_active = 0
            WHERE user_id = :user_id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $admin['user_id']
        ]);
    }

    // 更新を確定
    $pdo->commit();

    sendResponse(true, '管理者権限を解除しました。');
} catch (Throwable $e) {

    // エラー発生時は更新を取り消す
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('管理者権限解除エラー：' . $e->getMessage());

    sendResponse(
        false,
        '管理者権限の解除に失敗しました。',
        500
    );
}
