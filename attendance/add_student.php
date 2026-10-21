<?php

//save_attendance.php 出欠データの保存・更新API
header('Content-Type: application/json; charset=UTF-8');

require_once 'db.php';
require_once 'auth.php';

try {
     // ログイン情報から学科IDを取得する
// 実際のセッション名・権限に合わせて調整してください
$departmentId = $_SESSION['user']['department_id'];

if(!$departmentId) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => '学科情報を取得できません。']);
    exit;
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
$studentId = trim($data['student_id'] ?? '');
$studentName = trim($data['student_name'] ?? '');
$grade = trim((string)($data['grade'] ?? ''));
$studentGroup = trim((string)($data['student_group'] ?? ''));
$status = (string)($data['status'] ?? '');

//必要項目の確認
if(
     $studentId === '' ||
    $studentName === '' ||
    !ctype_digit($grade) ||
    (int)$grade < 1 ||
    !in_array($status, ['0', '1'], true)
) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => '入力内容を確認してください。']);
    exit;
}

// 学科の修業年限を取得し、学年を検証
    $stmtYears = $pdo->prepare("
        SELECT MAX(grade)
        FROM course_requirements
        WHERE department_id = ?
    ");
    $stmtYears->execute([$departmentId]);
    $years = (int)$stmtYears->fetchColumn();

    if ((int)$grade > $years || $years === 0) {
        http_response_code(400);
        throw new RuntimeException('学年が正しくありません。');
    }


$studentUsertypeId = 3;

// 初期パスワード（仮）：生徒ごとにランダムに生成
$initialPassword = bin2hex(random_bytes(8));
$passwordHash = password_hash($initialPassword, PASSWORD_DEFAULT);

//usersとstudentsへの登録をまとめる
$pdo->beginTransaction();

//users.user_idはAUTO_INCREMENTで自動採番
$stmtUser = $pdo->prepare("
    INSERT into users
        (usertype_id, login_id, password)
    VALUES
        (:usertype_id, :login_id, :password)
");

$stmtUser->execute([
    ':usertype_id'=> $studentUsertypeId,
    ':login_id'=> $studentId,
     ':password' => $passwordHash
]);

//自動採番されたuser_idを取得
$userId = $pdo->lastInsertId();

//studentsへ登録
$stmtStudent = $pdo->prepare("
    INSERT into students
        (student_id,  department_id, user_id, student_name, grade, student_group, status)
    VALUES
        (:student_id, :department_id, :user_id, :student_name, :grade, :student_group, :status)
");

$stmtStudent->execute([
    ':student_id'=> $studentId,
    ':department_id' => $departmentId,
    ':user_id' => $userId,
    ':student_name'=> $studentName,
    ':grade'=> (int)$grade,
    ':student_group'=> $studentGroup === '' ? null : $studentGroup,
    ':status' => (int)$status
]);

$pdo->commit();

echo json_encode([
        'success' => true,
        'message' => '生徒情報を新規登録しました。'
    ]);

} catch (PDOException $e) {
    if($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('生徒登録エラー：' . $e->getMessage());

    if($e->getCode() === '23000') {
        http_response_code(409);
        $message = '学籍番号が重複しているか、登録内容に規約違反があります。';
    } else {
        http_response_code(500);
        $message = 'データベースへの登録に失敗しました。';
    }

    echo json_encode([
        'success' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);

}catch (Throwable   $e) {
    if($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('生徒登録エラー：' . $e->getMessage());


    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '生徒登録処理中にエラーが発生しました。'
    ], JSON_UNESCAPED_UNICODE);
}
