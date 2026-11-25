
<?php

// delete_department.php 学科情報の削除API
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
    sendResponse(false, '送信データが不正です。', 400);
}

// 学科番号を取得
$departmentId = $data['id'] ?? '';

if (!is_string($departmentId) && !is_int($departmentId)) {
    sendResponse(false, '学科番号が不正です。', 400);
}

$departmentId = trim((string)$departmentId);

if ($departmentId === '') {
    sendResponse(false, '学科番号が指定されていません。', 400);
}

try {
    // トランザクション開始
    $pdo->beginTransaction();

    // 学科情報とユーザーIDを取得
    $sql = "
        SELECT user_id
        FROM department
        WHERE department_id = :department_id
        FOR UPDATE
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':department_id' => $departmentId
    ]);

    $department = $stmt->fetch(PDO::FETCH_ASSOC);

    // 学科が存在しない場合
    if (!$department) {
        $pdo->rollBack();
        sendResponse(false, '削除対象の学科が見つかりません。', 404);
    }

    $userId = $department['user_id'];

    // 学科に所属する生徒が存在するか確認
    $sql = "
        SELECT COUNT(*)
        FROM students
        WHERE department_id = :department_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':department_id' => $departmentId
    ]);

    if ((int)$stmt->fetchColumn() > 0) {
        $pdo->rollBack();
        sendResponse(
            false,
            'この学科には生徒が登録されているため削除できません。',
            409
        );
    }

    // 学科情報を削除
    $sql = "
        DELETE FROM department
        WHERE department_id = :department_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':department_id' => $departmentId
    ]);

    // 学科アカウントを削除
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

    sendResponse(true, '学科情報と関連アカウントを削除しました。');
} catch (PDOException $e) {

    // エラーが発生した場合はすべて取り消す
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('学科削除エラー：' . $e->getMessage());

    sendResponse(
        false,
        '学科情報の削除に失敗しました。データベースのエラーを確認してください。',
        500
    );
} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('学科削除処理エラー：' . $e->getMessage());

    sendResponse(
        false,
        '学科削除処理中にエラーが発生しました。',
        500
    );
}
