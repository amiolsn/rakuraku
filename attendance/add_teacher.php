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
    $teacherId = trim($data['teacher_id'] ?? '');
    $teacherName = trim($data['teacher_name'] ?? '');
    $isAdmin = (int)($data['is_admin'] ?? 0);


    // 管理者フラグの値をチェック
    if (!in_array($isAdmin, [0, 1], true)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '管理者フラグが不正です。'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    //必要項目の確認
    if (
        $teacherId === '' ||
        $teacherName === ''
    ) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '入力内容を確認してください。'
        ]);
        exit;
    }

    // ※ 実際の管理者判定用セッション項目に置き換える
    $isLoggedInAdmin =
        (int)($_SESSION['user']['is_admin'] ?? 0) === 1;

    // 学科アカウントから管理者は作成できない
    if (!$isLoggedInAdmin && $isAdmin === 1) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => '管理者を登録する権限がありません。'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }


    // 学科アカウントはログイン中の学科IDを使用する
    // 管理者は登録対象の学科IDを指定する
    if ($isLoggedInAdmin) {
        $departmentId = $data['department_id'] ?? null;
    } else {
        $departmentId = $_SESSION['user']['department_id'] ?? null;
    }


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



    $pdo->beginTransaction();


    // 一般教員はusersに登録しない
    // 管理者教員のみusersにも登録する
    $userId = null;

    //管理者フラグが1なら管理者として登録するためusersにもusertype=2として登録しなければならないが、学科アカウントでは管理者を作成することはできない。このコードを部品として管理者アカウントで教員を作る場合に管理者フラグについてが必要になる

    if ($isAdmin === 1) {

        // 初期パスワード（仮）：生徒ごとにランダムに生成
        $initialPassword = bin2hex(random_bytes(8));
        $passwordHash = password_hash($initialPassword, PASSWORD_DEFAULT);

        //users.user_idはAUTO_INCREMENTで自動採番
        $stmtUser = $pdo->prepare("
    INSERT into users
        (usertype_id, login_id, password)
    VALUES
        (:usertype_id, :login_id, :password)
");

        $stmtUser->execute([
            ':usertype_id' => $userId,
            ':login_id' => $teacherId,
            ':password' => $passwordHash
        ]);

        //自動採番されたuser_idを取得
        $userId = $pdo->lastInsertId();
    }



    //studentsへ登録
    $stmtTeacher  = $pdo->prepare("
    INSERT into teachers
        (teacher_id,  teacher_name, is_admin, user_id, department_id)
    VALUES
        (:teacher_id, :teacher_name, :is_admin, :user_id, :department_id)
");

    $stmtTeacher->execute([
        ':teacher_id' => $teacherId,
        ':teacher_name' => $teacherName,
        ':is_admin' => $isAdmin,
        ':user_id' => $userId,
        ':department_id' => $departmentId
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => '教員情報を新規登録しました。'
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('教員登録エラー：' . $e->getMessage());

    if ($e->getCode() === '23000') {
        http_response_code(409);
        $message = '教員番号が重複しているか、登録内容に規約違反があります。';
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
    error_log('教員登録エラー：' . $e->getMessage());


    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '教員登録処理中にエラーが発生しました。'
    ], JSON_UNESCAPED_UNICODE);
}
