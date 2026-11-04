<?php
error_log('① PHP開始');
session_start();

//save_attendance.php 出欠データの保存・更新API
header('Content-Type: application/json; charset=UTF-8');

require_once 'db.php';

// JavaScriptから送られてきたJSONデータを受信
$rawInput = file_get_contents('php://input');

error_log('year_update.php に到達しました');
error_log('受信データ: ' . $rawInput);

$data = json_decode($rawInput, true);
try {

    error_log('② トランザクション開始前');
    // ログイン情報から学科IDを取得する
    // 実際のセッション名・権限に合わせて調整してください
    $departmentId = $_SESSION['user']['department_id'] ?? null;

    if (!$departmentId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '学科情報を取得できません。'
        ]);
        exit;
    }




    //usersとstudentsへの登録をまとめる
    $pdo->beginTransaction();
    error_log('③ トランザクション開始成功');

    $selectStudent = $pdo->prepare("
         SELECT
            student_id,
            student_name,
            grade
        FROM students
        WHERE status = 1
        AND department_id = ?
        ORDER BY student_id ASC
    ");
    $params = [$departmentId];

    $selectStudent->execute($params);

    $students = $selectStudent->fetchAll();

    foreach ($students as $st) {
        if ((int)$st['grade'] === 1) {
            $sql = "
        UPDATE students
        SET grade = 2
        WHERE student_id = :student_id
        AND   grade = 1;
     ";

            $stmt  = $pdo->prepare($sql);
            $stmt->bindValue(':student_id', $st['student_id']);
            $stmt->execute();
        } elseif ((int)$st['grade'] === 2) {
            $sql = "
        UPDATE students
        SET status = false
        WHERE student_id = :student_id
        AND   grade = 2;
     ";

            $stmt  = $pdo->prepare($sql);
            $stmt->bindValue(':student_id', $st['student_id']);
            $stmt->execute();
        }
    }




    $pdo->commit();
    error_log('④ コミット成功');

    echo json_encode([
        'success' => true,
        'message' => '年度更新が完了しました。'
    ]);
} catch (PDOException $e) {
    error_log('エラー発生: ' . $e->getMessage());

    // エラー発生時はロールバック
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => '年度更新に失敗しました。'
    ]);
}
