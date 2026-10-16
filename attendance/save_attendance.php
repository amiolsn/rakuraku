<?php
//save_attendance.php 出欠データの保存・更新API
header('Content-Type: application/json; charset=UTF-8');

require_once 'db.php';

// JavaScriptから送られてきたJSONデータを受信
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// データの存在チェック
if (!$data || empty($data['records']) || empty($data['target_date'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '送信データが不正です。']);
    exit;
}

$target_date = $data['target_date'];
$period_setting = $data['periods']; // 時限ごとの科目・教員
$records = $data['records'];        // 出欠レコードの配列

try {
    // トランザクションの開始
    $pdo->beginTransaction();

    // 登録・更新SQL
    $sql = "
        INSERT INTO attendance (
            student_id,
            attendance_date,
            period,
            subject_id,
            teacher_id,
            attendance_status_id
        ) VALUES (
            :student_id,
            :attendance_date,
            :period,
            :subject_id,
            :teacher_id,
            :attendance_status_id
        )
        ON DUPLICATE KEY UPDATE
            subject_id = VALUES(subject_id),
            teacher_id = VALUES(teacher_id),
            attendance_status_id = VALUES(attendance_status_id)
    ";
    // ON DUPLICATE KEY UPDATE
    // 主キーが重複した場合は更新

    $stmt = $pdo->prepare($sql);

    // ループして保存を実行
    foreach ($records as $item) {
        $p = $item['period'];

        // 時限設定がとれるか確認
        if (!isset($period_setting[$p])) {
            throw new Exception("時限{$p}の科目・教員設定が見つかりません");
        }

        $subjId = $period_setting[$p]['subject_id'];
        $teachId = $period_setting[$p]['teacher_id'];

        $stmt->execute([
            ':student_id' => $item['student_id'],
            ':attendance_date' => $target_date,
            ':period' => $p,
            ':subject_id' => $subjId,
            ':teacher_id' => $teachId,
            ':attendance_status_id' => $item['status']
        ]);
    }

    // すべて成功したらコミット
    $pdo->commit();

    // ブラウザに成功メッセージを返す
    echo json_encode([
        'success' => true,
        'message' => '保存が完了しました。',
        'count' => count($records)
    ]);
} catch (Exception $e) {
    // エラーが起きた場合は処理を取り消し（ロールバック）
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'データベース保存エラー:' . $e->getMessage()
    ]);
}
