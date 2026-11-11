
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
$studentId = trim(($data['id'] ?? ''));

if ($studentId === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '生徒IDが指定されていません。'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 生徒IDを取得
$studentId = $data['id'] ?? '';

if (!is_string($studentId) && !is_int($studentId)) {
    sendResponse(false, '生徒IDが不正です。', 400);
}

// $studentId = (string)$studentId;

try {
    // トランザクション開始
    $pdo->beginTransaction();

    // 生徒情報とユーザーIDを取得
    $sql = "
        SELECT user_id
        FROM students
        WHERE student_id = :student_id
        FOR UPDATE
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':student_id' => $studentId
    ]);

    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    // 生徒が存在しない場合
    if (!$student) {
        $pdo->rollBack();
        sendResponse(false, '削除対象の生徒が見つかりません。', 404);
    }

    $userId = $student['user_id'];

    // ① 出席データを削除
    // 生徒IDまたは対象の欠席届に紐づくデータを削除
    $sql = "
        DELETE FROM attendance
        WHERE student_id = :student_id
        OR absence_request_id IN (
            SELECT absence_request_id
            FROM absence_requests
            WHERE user_id = :user_id
        )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':student_id' => $studentId,
        ':user_id' => $userId
    ]);

    // ② 操作履歴を削除
    $sql = "
        DELETE FROM audit_logs
        WHERE user_id = :user_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $userId
    ]);

    // ③ 欠席・届け出データを削除
    $sql = "
        DELETE FROM absence_requests
        WHERE user_id = :user_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $userId
    ]);

    // ④ 生徒情報を削除
    $sql = "
        DELETE FROM students
        WHERE student_id = :student_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':student_id' => $studentId
    ]);

    // ⑤ ログインアカウントを削除
    $sql = "
        DELETE FROM users
        WHERE user_id = :user_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $userId
    ]);

    // すべて成功した場合のみ確定
    $pdo->commit();

    sendResponse(true, '生徒情報と関連データを削除しました。');
} catch (PDOException $e) {

    // エラーが発生した場合はすべて取り消す
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('生徒削除エラー：' . $e->getMessage());

    sendResponse(
        false,
        '生徒情報の削除に失敗しました。データベースのエラーを確認してください。',
        500
    );
} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('生徒削除処理エラー：' . $e->getMessage());

    sendResponse(
        false,
        '生徒削除処理中にエラーが発生しました。',
        500
    );
}
