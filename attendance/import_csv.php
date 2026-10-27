<?php
// import_csv.php
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    // ログイン情報から学科IDを取得する
    // 実際のセッション名・権限に合わせて調整してください
    $departmentId = $_SESSION['user']['department_id'] ?? null;

    if (!$departmentId) {
        throw new RuntimeException(
            '学科情報を取得できません。'
        );
    }

    // 登録対象を取得
    $target = $_POST['target'] ?? '';

    // CSVファイルを確認
    if (
        !isset($_FILES['csv_file']) ||
        $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK
    ) {
        throw new RuntimeException('CSVファイルを受信できませんでした。');
    }

    $tmpPath = $_FILES['csv_file']['tmp_name'];


    // 登録対象によって処理を分ける
    switch ($target) {
        case 'student':
            require_once __DIR__ . '/import_student.php';
            $message = importStudents($pdo, (int)$departmentId, $tmpPath);
            break;

        case 'teacher':
            require_once __DIR__ . '/import_teacher.php';
            $message = importTeachers($pdo, (int)$departmentId, $tmpPath);
            break;

        case 'subject':
            require_once __DIR__ . '/import_subject.php';
            $message = importSubjects($pdo, (int)$departmentId, $tmpPath);
            break;

        default:
            throw new RuntimeException('登録対象が不正です。');
    }

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
    http_response_code(400);

    // 開発中の原因調査用
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
