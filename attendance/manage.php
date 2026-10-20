<?php
// データベース接続
require_once 'db.php';
require_once 'auth.php';

// // URLからの直接アクセスを防ぐ
// // 管理者以外からのアクセスを拒否して出欠入力画面に戻す
// if (empty($_SESSION['user']['is_admin']) || $_SESSION['user']['is_admin'] !== 1) {
//     header('Location: input.php');
//     exit;
// }


try {
    $departmentId = $_SESSION['user']['department_id'];

    // 生徒一覧の取得（学科名も結合して取得）
    $stmtStudents = $pdo->prepare("
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
        WHERE s.status = 1
        AND d.department_id = ?
        ORDER BY s.student_id ASC
    ");

    $stmtStudents->execute([$departmentId]);

    $students = $stmtStudents->fetchAll();

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
                        <button id="click-btn" class="add-btn" onclick="toggleAdd(this)">新規追加</button>
                        <div id="popup-wrapper">
                            <div id="popup-inside">
                                <div id="close">x</div>
                                <div id="message">
                                    <table class="management-table">
                                        <thead>
                                            <tr>
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
                                            <tr>
                                                <td><input type="text" id="student_id" name="student_id" required></td>
                                                <td><input type="text" id="name" student_name="student_name" required></td>
                                                <td>情報システム科</td>
                                                <!-- 学年プルダウン -->
                                                <td><select id="grade" name="grade" onchange="this.form.submit()">
                                                        <option value="">- 学年選択 -</option>
                                                        <?php for ($i = 1; $i <= $years; $i++): ?>
                                                            <option value="<?= $i ?>"
                                                                <?= (isset($_GET['grade']) && $_GET['grade'] == $i) ? 'selected' : '' ?>>
                                                                <?= $i ?>年生
                                                            </option>
                                                        <?php endfor; ?>

                            </select></td>
                            <td><input type="text" id="student_group" name="student_group"></td>
                            <!-- 在校状況ラジオボタン -->
                             <td><label>在校</label>
                                        <input type="radio" name="status" value="1">
                                    <label>退校・卒業</label>
                                        <input type="radio" name="status" value="0"></td>

                            <td><button>登録</button></td>
                                    
                        </tr>
                    </tbody>
</table>
                        <a href="#">ゲットする</a>
                        </div>
                    </div>
                </div>


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
                                    <td class="table-actions"><button class="edit-btn" onclick="toggleEdit(this)">編集</button><button class="delete-btn" onclick="toggledelete(this)">削除</button></td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <script>
                    function toggledelete(button) {
                        const row = button.closest('tr');
                        const editableCells = row.querySelectorAll('.editable');

                        // 削除しますか？ポップアップ表示
                        // 削除が押されたらdeleteするもしくは退避させる
                        // キャンセルが押されたらポップアップを閉じる
                    }
                </script>

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