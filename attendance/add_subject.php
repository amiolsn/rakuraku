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
        echo json_encode(['success' => false, 'message' => '送信データが不正です。']);
        exit;
    }

    //入力値を取得
    $subjectId = trim($data['subject_id'] ?? '');
    $subjectName = trim($data['subject_name'] ?? '');



    //必要項目の確認
    if (
        $subjectId === '' ||
        $subjectName === ''
    ) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '入力内容を確認してください。'
        ]);
        exit;
    }


    $departmentId = $_SESSION['user']['department_id'] ?? null;



    if (
        $departmentId === null ||
        !is_numeric($departmentId) ||
        (int)$departmentId <= 0
    ) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '学科情報を取得できません。'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }


    //studentsへ登録
    $stmtSubject = $pdo->prepare("
    INSERT INTO subjects
        (subject_id, subject_name, department_id)
    VALUES
        (:subject_id, :subject_name, :department_id)
");

    $stmtSubject->execute([
        ':subject_id' => $subjectId,
        ':subject_name' => $subjectName,
        ':department_id' => (int)$departmentId
    ]);




    echo json_encode([
        'success' => true,
        'message' => '科目情報を新規登録しました。'
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {

    error_log('科目登録エラー：' . $e->getMessage());

    if ($e->getCode() === '23000') {
        http_response_code(409);
        $message = '科目番号が重複しているか、登録内容に規約違反があります。';
    } else {
        http_response_code(500);
        $message = 'データベースへの登録に失敗しました。';
    }

    echo json_encode([
        'success' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable   $e) {

    error_log('科目登録エラー：' . $e->getMessage());


    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '科目登録処理中にエラーが発生しました。'
    ], JSON_UNESCAPED_UNICODE);
}
