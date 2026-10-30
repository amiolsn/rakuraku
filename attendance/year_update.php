<?php


try {
    // ログイン情報から学科IDを取得する
    // 実際のセッション名・権限に合わせて調整してください
    $departmentId = $_SESSION['user']['department_id'];

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


    $selectStudent = $pdo->prepare("
        SELECT
                s.student_id,
                s.student_name,
                s.grade,
                s.student_group,
                d.department_name
        FROM students s
        LEFT OUTER JOIN department d
            ON s.department_id = d.department_id
        WHERE s.status = 1
        AND d.department_id = ?
        ORDER BY s.student_id ASC
    ");
    $params = [$departmentId];


    $selectStudent = $pdo->prepare($selectStudent);
    $selectStudent->execute($params);

    return $selectStudent->fetchAll();

    foreach ($selectStudent as $st) {
        if ($st[grade] === 1) {
            $sql = "
        UPDATE students
        SET grade = 2
        WHERE student_id = :student_id
        AND   grade = 1;
     ";

            $stmt  = $pdo->prepare($sql);
            $stmt->bindValue(':student_id', $st['student_id']);
            $stmt->execute();
        } else {
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

    echo json_encode([
        'success' => true,
        'message' => '生徒情報を新規登録しました。'
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('生徒登録エラー：' . $e->getMessage());

    if ($e->getCode() === '23000') {
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
} catch (Throwable   $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('生徒登録エラー：' . $e->getMessage());


    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '生徒登録処理中にエラーが発生しました。'
    ], JSON_UNESCAPED_UNICODE);
}
