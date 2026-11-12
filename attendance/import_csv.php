<?php
// import_csv.php
require_once 'db.php';
require_once 'auth.php';

//レスポンスの形式を指定する
//Content-Type：データの種類を指定するHTTPヘッダー
//application/json：JSON形式であることを示す。
//charset=UTF-8：文字コードをUTF-8に指定する。
header('Content-Type: application/json; charset=UTF-8');

try {
    // ログイン情報から学科IDを取得する
    //?? null：値が存在しない場合は null を代入
    $departmentId = $_SESSION['user']['department_id'] ?? null;

    //管理者かどうかを判定
    //usertype_id：ユーザーの権限を識別するID(2は教員個人)
    $isAdminAccount = (int)($_SESSION['user']['usertype_id'] ?? 0) === 2;

    if (!$isAdminAccount && !$departmentId) {
        throw new RuntimeException(
            '学科情報を取得できません。'
        );
    }

    //登録対象を取得(student, teacher, subject)
    //フォームから送信された登録対象を取得
    $target = $_POST['target'] ?? '';

    //CSVファイルが正常に受信できたか確認
    //UPLOAD_ERR_OK：アップロードが正常に完了したことを示す定数
    if (
        !isset($_FILES['csv_file']) ||
        $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK
    ) {
        throw new RuntimeException('CSVファイルを受信できませんでした。');
    }

    //CSVファイルの一時保存先を取得
    $tmpPath = $_FILES['csv_file']['tmp_name'];


    // 登録対象によって処理を分ける
    switch ($target) {
        case 'student':
            require_once __DIR__ . '/import_student.php';
            $message = importStudents($pdo, $departmentId, $isAdminAccount, $tmpPath);
            break;

        case 'teacher':
            require_once __DIR__ . '/import_teacher.php';
            $message = importTeachers($pdo, $departmentId, $isAdminAccount, $tmpPath);
            break;

        case 'subject':
            require_once __DIR__ . '/import_subject.php';
            $message = importSubjects($pdo, $departmentId, $isAdminAccount, $tmpPath);
            break;

        default:
            throw new RuntimeException('登録対象が不正です。');
    }

    //json_encode():PHPのデータをJSON形式の文字列に変換する関数
    echo json_encode([
        'success' => true,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);

    // 開発中はエラー内容を確認できるようにする。
    // 本番環境では詳細な例外情報を利用者に返さない。
    // echo json_encode([
    //     'success' => false,
    //     'message' => $e instanceof PDOException
    //         ? '登録に失敗しました。入力内容を確認してください。'
    //         : $e->getMessage()
    // ], JSON_UNESCAPED_UNICODE);

    // 開発中の原因調査用
    // echo json_encode([
    //     'success' => false,
    //     'message' => $e->getMessage()
    // ], JSON_UNESCAPED_UNICODE);
}
