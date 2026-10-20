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





//INSERT
$sql = "
    INSERT into students
        (student_id, student_name, department_id, grade, student_group, status)
    VALUES
        (:student_id, :student_name, :department_id, :grade, :student_group, :status)
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':student_id'=> $studentId,
    ':student_name'=> $studentName,
     ':department_id' => $departmentId,
    ':grade'=> (int)$grade,
    ':student_group'=> $studentGroup === '' ? null : $studentGroup,
    ':status' => (int)$status
]);

echo json_encode([
        'success' => true,
        'message' => '生徒情報を新規登録しました。'
    ]);
} catch (PDOException $e) {
    // 詳しいエラーをPHPのエラーログに出力
    error_log('生徒登録エラー: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'データベースへの登録に失敗しました。'
    ], JSON_UNESCAPED_UNICODE);
}catch (RuntimeException  $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
