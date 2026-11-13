<?php
// 共通設定の読込
require_once 'db.php';
require_once 'auth.php';
require_once 'functions.php';

try {
    //ユーザＩＤを取得してログインユーザーを特定する
    $userId = $_SESSION['user']['user_id'] ?? null;

    //ユーザーIDが取得できなかったら
    if (!$userId) {
        http_response_code(401);
        echo "<script>
            alert('ユーザー情報を取得できませんでした。再度ログインしてください。');
            location.href = 'login-input.php';
        </script>";
        exit;
    }

    //フォームから送信された新しいパスワードを取得する
    $password = $_POST['new-password'] ?? '';

    //未入力の場合エラー？
    if (empty($password)) {
        echo "<script>
            alert('パスワードを入力してください。');
            history.back();
        </script>";
        exit;
    }

    //パスワードを更新するためのSQLを組み立て
    $stmtUser = $pdo->prepare('
        UPDATE users
        SET password = :password
        WHERE user_id = :user_id
    ');

    //実行
    $stmtUser->execute([
        ':user_id' => $userId,
        ':password' => $password
    ]);

    echo "<script>
        alert('パスワードを変更しました。ログイン画面へ移動します。');
        location.href = 'login-input.php';
    </script>";
    exit;
} catch (PDOException $e) {
    error_log('パスワード変更エラー：' . $e->getMessage());

    echo "<script>
        alert('パスワードの変更に失敗しました。時間をおいて再度お試しください。');
        history.back();
    </script>";
    exit;
}
