
<?php

header('Content-Type: application/json; charset=UTF-8');

require_once 'db.php';
require_once 'auth.php';

try {

    // JavaScriptから送られてきたJSONデータを受信
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    // データの存在チェック
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '送信データが不正です。'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 入力値を取得
    $departmentId = trim((string)($data['department_id'] ?? ''));
    $departmentName = trim($data['department_name'] ?? '');

    // 必要項目の確認
    if ($departmentId === '' || $departmentName === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '入力内容を確認してください。'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 学科アカウントのユーザータイプID
    $departmentUsertypeId = 1;

    // 初期パスワードをランダムに生成
    $initialPassword = bin2hex(random_bytes(8));
    $passwordHash = password_hash($initialPassword, PASSWORD_DEFAULT);

    // usersとdepartmentへの登録をまとめる
    $pdo->beginTransaction();

    // usersへ登録
    $stmtUser = $pdo->prepare("
        INSERT INTO users
            (usertype_id, login_id, password)
        VALUES
            (:usertype_id, :login_id, :password)
    ");

    $stmtUser->execute([
        ':usertype_id' => $departmentUsertypeId,
        ':login_id' => 'dept' . $departmentId,
        ':password' => $passwordHash
    ]);

    // 自動採番されたuser_idを取得
    $userId = $pdo->lastInsertId();

    // departmentへ登録
    $stmtDepartment = $pdo->prepare("
        INSERT INTO department
            (department_id, user_id, department_name)
        VALUES
            (:department_id, :user_id, :department_name)
    ");

    $stmtDepartment->execute([
        ':department_id' => $departmentId,
        ':user_id' => $userId,
        ':department_name' => $departmentName
    ]);

    // 登録を確定
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => '学科情報を新規登録しました。'
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('学科登録エラー：' . $e->getMessage());

    if ($e->getCode() === '23000') {
        http_response_code(409);
        $message = '学科番号が重複しているか、登録内容に規約違反があります。';
    } else {
        http_response_code(500);
        $message = 'データベースへの登録に失敗しました。';
    }

    echo json_encode([
        'success' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('学科登録処理エラー：' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '学科登録処理中にエラーが発生しました。'
    ], JSON_UNESCAPED_UNICODE);
}
