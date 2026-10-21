<?php
// データベース接続
require_once 'db.php';
require_once 'auth.php';

try {
    $departmentId = $_SESSION['user']['department_id'];

    // 生徒一覧の取得（学科名も結合して取得）
    $sql = "
        SELECT
            s.student_id,
            s.student_name,
            s.grade,
            s.student_group,
            s.status,
            d.department_name
        FROM students s
        JOIN department d
            ON s.department_id = d.department_id
        WHERE d.department_id = ?
    ";

    $params = [$departmentId];

    // 学籍番号が検索欄に入力されている場合だけ条件を追加
    if (isset($_GET['student_id']) && $_GET['student_id'] !== '') {
        $sql .= " AND s.student_id = ?";
        $params[] = (int)$_GET['student_id'];
    }

    // 生徒氏名が検索欄に入力されている場合だけ条件を追加
    if (isset($_GET['student_name']) && $_GET['student_name'] !== '') {
        $sql .= " AND s.student_name LIKE ?";
        $params[] = '%' . $_GET['student_name'] . '%';
    }

    // 学年が選択されている場合だけ条件を追加
    if (isset($_GET['grade']) && $_GET['grade'] !== '') {
        $sql .= " AND grade = ?";
        $params[] = (int)$_GET['grade'];
    }

    // 班が選択されている場合だけ条件を追加
    if (isset($_GET['student_group']) && $_GET['student_group'] !== '') {
        $sql .= " AND s.student_group = ?";
        $params[] = $_GET['student_group'];
    }

    $sql .= " ORDER BY s.student_id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $students = $stmt->fetchAll();




    // 科目一覧の取得
    $stmtSubjects = $pdo->prepare("
        SELECT subject_id, subject_name
        FROM subjects s
        JOIN department d ON s.department_id = d.department_id
        WHERE d.department_id = ?
        ORDER BY subject_id ASC
    ");

    $stmtSubjects->execute([$departmentId]);

    $subjects = $stmtSubjects->fetchAll();

    // 教員一覧の取得
    $stmtTeachers = $pdo->prepare("
        SELECT teacher_id, teacher_name
        FROM teachers t
        JOIN department d ON t.department_id = d.department_id
        WHERE d.department_id = ?
        ORDER BY teacher_id ASC
    ");

    $stmtTeachers->execute([$departmentId]);

    $teachers = $stmtTeachers->fetchAll();

    // 修業年限の取得
    $stmtRequirement = $pdo->prepare("
        SELECT MAX(grade) AS term
        FROM course_requirements
        WHERE department_id = ?
    ");

    $stmtRequirement->execute([$departmentId]);

    $requirement = $stmtRequirement->fetch();

    $years = (int)($requirement['term'] ?? 0);

    // ログイン中の教員の学科名を取得
    $stmtDepartment = $pdo->prepare("
        SELECT department_name
        FROM department
        WHERE department_id = ?
    ");

    $stmtDepartment->execute([$departmentId]);

    $departmentName = $stmtDepartment->fetchColumn();

    // 修業年限の取得
    $stmtRequirement = $pdo->prepare("
        SELECT MAX(grade) AS term
        FROM course_requirements
        WHERE department_id = ?
    ");

    $stmtRequirement->execute([$departmentId]);

    $requirement = $stmtRequirement->fetch();


    $years = (int)($requirement['term'] ?? 0);

    // 班一覧の取得（重複なし）
    $stmtGroups = $pdo->prepare("
        SELECT DISTINCT student_group
        FROM students
        WHERE department_id = ?
        AND status = 1
        AND student_group IS NOT NULL
        AND student_group <> ''
        ORDER BY student_group ASC
    ");

    $stmtGroups->execute([$departmentId]);
    $groups = $stmtGroups->fetchAll();
} catch (PDOException $e) {
    die('データ取得エラー: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>らくらく出席簿 - 出欠入力</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/tab.css">
    <link rel="stylesheet" href="css/popup.css">
    <link rel="stylesheet" href="css/delete-popup.css">

</head>

<body>
    <?php require_once 'components/header.php'; ?>


    <!-- タブグループ -->
    <div class="tab-h" id="tab-h">
        <!-- タブメニュー -->
        <ul class="tab-contena">
            <li class="tab-elem" data-tabid="tab-page1">生徒管理</li>
            <li class="tab-elem" data-tabid="tab-page2">教員管理</li>
            <li class="tab-elem" data-tabid="tab-page3">科目管理</li>
        </ul>
        <!-- タブ内コンテンツ  ※コンテンツは自由に編集可能 -->
        <div class="tabbody-h" id="tabbody-h">
            <div class="table-container">
                <div class="tabbody-elem" id="tab-page1">
                    <div class="table-actions-top">
                        <!-- 新規追加 -->
                        <button id="add-btn" class="add-btn">新規追加</button>
                        <?php include 'components/_add_student_popup.php'; ?>
                        <button id="bulkAdd-btn" class="bulkAdd-btn">一括登録</button>
                    </div>

                    <div class="table-actions-top">
                        <!-- 検索フィルター -->
                        <!-- 学籍番号
                            生徒氏名
                            学年
                            班
                            在校状況 -->
                        <table class="management-table">
                            <tr>
                                <form action="" method="GET" class="period-header select">
                                    <td></td>
                                    <td>
                                        <label for="student_id">学籍番号</label>
                                        <input type="text" name="student_id"
                                            value="<?= htmlspecialchars($_GET['student_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                                    </td>
                                    <td>
                                        <label for="student_name">生徒氏名</label>
                                        <input type="text" name="student_name"
                                            value="<?= htmlspecialchars($_GET['student_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    </td>
                                    <td></td>
                                    <td>
                                        <label for="grade">学年</label>
                                        <select id="grade" name="grade">
                                            <?php
                                            $selected_grade = $_GET['grade'] ?? '';
                                            include 'components/_grade_options.php';
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <label for="student_group">班</label>
                                        <select id="student_group" name="student_group">
                                            <?php
                                            $selected_group = $_GET['student_group'] ?? '';
                                            include 'components/_group_options.php';
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <label for="status">在校状況</label>
                                        <select id="status" name="status">
                                            <?php
                                            $selected_grade = $_GET['status'] ?? '';
                                            include 'components/_status_options.php';
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="submit" id="search-btn" class="search-btn">検索</button>
                                        <button type="button" id="clear-btn" class="clear-btn">クリア</button>
                                    </td>
                                </form>
                            </tr>
                        </table>





                    </div>

                    <table class="management-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>学籍番号</th>
                                <th>生徒氏名</th>
                                <th>学科名</th>
                                <th>学年</th>
                                <th>班</th>
                                <th>在校状況</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; ?>
                            <?php foreach ($students as $st): ?>
                                <tr data-student-id="<?= htmlspecialchars($st['student_id']) ?>">
                                    <td><?= $i++ ?></td>
                                    <!-- 学籍番号 -->
                                    <td><?= htmlspecialchars($st['student_id']) ?></td>
                                    <!-- 生徒氏名 -->
                                    <td class="editable" data-field="student_name"><?= htmlspecialchars($st['student_name']) ?></td>
                                    <!-- 学科名 -->
                                    <td><?= htmlspecialchars($st['department_name']) ?></td>
                                    <!-- 学年 -->
                                    <td><?= htmlspecialchars($st['grade']) ?></td>
                                    <!-- 班 -->
                                    <td class="editable" data-field="student_group"><?= !empty($st['student_group']) ?  htmlspecialchars($st['student_group']) : "-" ?></td>
                                    <!-- 在校状況 -->
                                    <td class="editable" data-field="status" data-status="<?= (int)$st['status'] ?>"><?= $st['status'] == 1 ? '在校' : '退校・卒業' ?></td>
                                    <!-- 編集・削除ボタン -->
                                    <td class="table-actions">
                                        <button class="edit-btn" onclick="toggleEdit(this)">編集</button>
                                        <button class="delete-btn">削除</button>
                                        <?php include 'components/_delete_popup.php'; ?>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </div>

            </div>
            <div class="tabbody-elem" id="tab-page2">タブ2内容タブ2内容</div>
            <div class="tabbody-elem" id="tab-page3">タブ3内容タブ3内容タブ3内容</div>
        </div>
    </div>
    </div>
    <script src="js/tab.js"></script>
    <script src="js/manage.js"></script>
    <script src="js/popup.js"></script>
</body>

</html>