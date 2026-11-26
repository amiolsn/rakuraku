
<?php
// add_admin.php 管理者追加API
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

try {

    // POST以外は受け付けない
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, '不正なリクエストです。', 405);
    }

    // ログイン中のユーザーIDを取得
    $loginUserId = $_SESSION['user']['user_id'] ?? null;

    // 管理者権限をDBで確認
    $stmt = $pdo->prepare('
        SELECT is_admin
        FROM teachers
        WHERE user_id = :user_id
    ');

    $stmt->execute([':user_id' => $loginUserId]);
    $loginUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$loginUser || (int)$loginUser['is_admin'] !== 1) {
        sendResponse(false, '管理者権限がありません。', 403);
    }

    // JSONデータを受信
    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data)) {
        sendResponse(false, '送信データが不正です。', 400);
    }

    // 教員番号を取得
    $teacherId = $data['teacher_id'] ?? '';

    if (
        (!is_string($teacherId) && !is_int($teacherId))
        || !ctype_digit((string)$teacherId)
        || (int)$teacherId <= 0
    ) {
        sendResponse(false, '教員IDが正しくありません。', 400);
    }

    $pdo->beginTransaction();

    // 対象教員を取得
    $stmt = $pdo->prepare('
        SELECT teacher_id, user_id, is_admin
        FROM teachers
        WHERE teacher_id = :teacher_id
        FOR UPDATE
    ');

    $stmt->execute([':teacher_id' => $teacherId]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        throw new Exception('対象の教員が見つかりません。');
    }

    if ((int)$teacher['is_admin'] === 1) {
        throw new Exception('この教員はすでに管理者です。');
    }

    // 既存のユーザーアカウントがある場合
    if ($teacher['user_id'] !== null) {

        // アカウントを再有効化
        $stmt = $pdo->prepare('
            UPDATE users
            SET is_active = 1
            WHERE user_id = :user_id
        ');

        $stmt->execute([
            ':user_id' => $teacher['user_id']
        ]);

        if ($stmt->rowCount() === 0) {
            // ユーザーが存在しない場合
            $stmt = $pdo->prepare('
                SELECT user_id
                FROM users
                WHERE user_id = :user_id
            ');

            $stmt->execute([
                ':user_id' => $teacher['user_id']
            ]);

            if (!$stmt->fetch()) {
                throw new Exception('紐づいているユーザーが見つかりません。');
            }
        }
    } else {

        // まだユーザーアカウントがない場合は新規作成
        $loginId = 'teacher' . $teacher['teacher_id'];

        $stmt = $pdo->prepare('
            INSERT INTO users (
                usertype_id,
                login_id,
                password
            )
            VALUES (2, :login_id, :password)
        ');

        $stmt->execute([
            ':login_id' => $loginId,
            ':password' => 'password123'
        ]);

        // 作成したユーザーIDを取得
        $userId = $pdo->lastInsertId();

        // 教員情報にユーザーIDを紐づける
        $stmt = $pdo->prepare('
            UPDATE teachers
            SET user_id = :user_id
            WHERE teacher_id = :teacher_id
        ');

        $stmt->execute([
            ':user_id' => $userId,
            ':teacher_id' => $teacherId
        ]);
    }

    // 管理者権限を付与
    $stmt = $pdo->prepare('
        UPDATE teachers
        SET is_admin = 1
        WHERE teacher_id = :teacher_id
    ');

    $stmt->execute([
        ':teacher_id' => $teacherId
    ]);

    // 登録確定
    $pdo->commit();

    sendResponse(true, '管理者に追加しました。');
} catch (Throwable $e) {

    // エラー発生時は更新を取り消す
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('管理者追加エラー：' . $e->getMessage());

    sendResponse(
        false,
        $e->getMessage(),
        400
    );
}
