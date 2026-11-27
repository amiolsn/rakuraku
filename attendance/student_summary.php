<?php

// 共通設定・認証・関数
require_once 'db.php';
require_once 'auth.php';
require_once 'functions.php';

try {
    $user_type       = (int)($_SESSION['user']['usertype_id'] ?? 0);
    $is_student_user = ($user_type === 3);

    if ($is_student_user) {
        // 生徒の場合、自身のstudent_idを取得
        $student_id = (int)($_SESSION['user']['student_id'] ?? 0);
    } else {
        // 教員・管理者の場合はURLパラメータから取得
        $student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
    }

    $student_detail = ($student_id > 0) ? getStudentTotalSummary($pdo, $student_id) : null;

    // 教員アクセス時、自学科以外の生徒は閲覧不可にする
    $is_admin = (!empty($_SESSION['user']['is_admin']) && (int)$_SESSION['user']['is_admin'] === 1);
    if (!$is_student_user && !$is_admin && !$student_detail !== null) {
        $user_dept_id = (int)($_SESSION['user']['department_id'] ?? 0);
        if ($student_detail['department_id'] !== $user_dept_id) {
            $student_detail = null;
        }
    }

} catch (PDOException $e) {
    die('データ取得エラー: ' . htmlspecialchars($e -> getMessage(), ENT_QUOTES, 'UTF-8'));
}
