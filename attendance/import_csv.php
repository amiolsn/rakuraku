<?php
// import_csv.php
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json; charset=UTF-8');

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
            // 生徒情報の登録処理

            //CSVを読み込む
            //列名やデータの形式を確認
            //データベースに登録する(usersから)
            $handle = fopen($tmpPath, 'r');
            if ($handle === false) {
                throw new RuntimeException('CSVファイルを開けません。');
            }

            // 1行目の見出しを読み飛ばす
            try {
                // 1行目の見出しを読み込む
                $header = fgetcsv($handle, 0, ',', '"', '');

                // UTF-8 BOMを除去
                if (isset($header[0])) {
                    $header[0] = preg_replace(
                        '/^\xEF\xBB\xBF/',
                        '',
                        $header[0]
                    );
                }

                // 見出しのチェック
                $expectedHeader = [
                    'student_id',
                    'student_name',
                    'grade',
                    'student_group',
                    'status'
                ];

                if ($header !== $expectedHeader) {
                    throw new RuntimeException(
                        'CSVの列名または列順が正しくありません。'
                    );
                }

                // 学科の修業年限を取得
                $stmtYears = $pdo->prepare("
                    SELECT MAX(grade)
                    FROM course_requirements
                    WHERE department_id = ?
                ");
                $stmtYears->execute([$departmentId]);
                $years = (int)$stmtYears->fetchColumn();

                if ($years === 0) {
                    throw new RuntimeException(
                        '学科の修業年限を取得できません。'
                    );
                }

                // CSV全行を読み込み、先に入力チェックする
                $students = [];
                $studentIds = [];
                $lineNumber = 1;


                // 2行目以降を1行ずつ読み込む
                while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                    $lineNumber++;

                    // 空行を読み飛ばす
                    if ($row === [null] || $row === []) {
                        continue;
                    }

                    if (count($row) !== 5) {
                        throw new RuntimeException(
                            "{$lineNumber}行目：列数が正しくありません。"
                        );
                    }
                    $studentId = trim($row[0] ?? '');
                    $studentName = trim($row[1] ?? '');
                    $grade = trim($row[2] ?? '');
                    $studentGroup = trim($row[3] ?? '');
                    $status = trim($row[4] ?? '');

                    // ここで入力チェックを行う
                    // 問題がなければ登録処理を行う
                    //必要項目の確認
                    if (
                        $studentId === '' ||
                        $studentName === '' ||
                        !ctype_digit($grade) ||
                        (int)$grade < 1 ||
                        !in_array($status, ['0', '1'], true)
                    ) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => '入力内容を確認してください。'
                        ]);
                        exit;
                    }

                    // CSV内で学籍番号が重複していないか確認
                    if (isset($studentIds[$studentId])) {
                        throw new RuntimeException(
                            "{$lineNumber}行目：学籍番号が重複しています。"
                        );
                    }

                    $studentIds[$studentId] = true;

                    $students[] = [
                        'student_id' => $studentId,
                        'student_name' => $studentName,
                        'grade' => (int)$grade,
                        'student_group' => $studentGroup === ''
                            ? null
                            : $studentGroup,
                        'status' => (int)$status
                    ];
                }

                if (count($students) === 0) {
                    throw new RuntimeException(
                        '登録する生徒データがありません。'
                    );
                }
            } finally {
                fclose($handle);
            }

            // 全行のチェックが終わってからDB登録を開始
            $pdo->beginTransaction();

            try {
                $stmtUser = $pdo->prepare("
                    INSERT INTO users
                        (usertype_id, login_id, password)
                    VALUES
                        (:usertype_id, :login_id, :password)
                ");

                $stmtStudent = $pdo->prepare("
                    INSERT INTO students
                        (student_id, department_id, user_id,
                         student_name, grade, student_group, status)
                    VALUES
                        (:student_id, :department_id, :user_id,
                         :student_name, :grade, :student_group, :status)
                ");

                foreach ($students as $student) {
                    // 初期パスワードを生成してハッシュ化
                    $initialPassword = bin2hex(random_bytes(8));
                    $passwordHash = password_hash(
                        $initialPassword,
                        PASSWORD_DEFAULT
                    );

                    // usersに登録
                    $stmtUser->execute([
                        ':usertype_id' => 3,
                        ':login_id' => $student['student_id'],
                        ':password' => $passwordHash
                    ]);

                    // 自動採番されたuser_idを取得
                    $userId = $pdo->lastInsertId();

                    // studentsに登録
                    $stmtStudent->execute([
                        ':student_id' => $student['student_id'],
                        ':department_id' => $departmentId,
                        ':user_id' => $userId,
                        ':student_name' => $student['student_name'],
                        ':grade' => $student['grade'],
                        ':student_group' => $student['student_group'],
                        ':status' => $student['status']
                    ]);
                }

                // 全員の登録に成功したら確定
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw $e;
            }

            echo json_encode([
                'success' => true,
                'message' => count($students) . '人の生徒を登録しました。'
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'teacher':
            // 教員情報の登録処理は後で実装
            throw new RuntimeException(
                '教員の一括登録はまだ実装されていません。'
            );

        default:
            throw new RuntimeException('登録対象が不正です。');
    }
} catch (Throwable $e) {
    http_response_code(400);

    // 開発中はエラー内容を確認できるようにする。
    // 本番環境では詳細な例外情報を利用者に返さない。
    echo json_encode([
        'success' => false,
        'message' => $e instanceof PDOException
            ? '登録に失敗しました。学籍番号の重複などを確認してください。'
            : $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
