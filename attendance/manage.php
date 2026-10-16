<?php
// データベース接続
require_once 'db.php';
require_once 'auth.php';


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
    die('データ取得エラー: ' .htmlspecialchars($e->getMessage(), ENT_QUOTES,'UTF-8')) ;
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>らくらく出席簿 - 出欠入力</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/tab.css">
    
</head>

<body>
<?php require_once 'header-top.php'; ?>
<a href="input.php">TOP</a>
    <nav><a href="#">欠席登録・届け出管理</a></nav>
    <nav><a href="#">集計</a></nav>
    <nav><a href="manage.php">登録情報編集</a></nav>
    <a href="login-input.php">ログイン</a>

<?php require_once 'header-bottom.php'; ?>

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
        <div class="tabbody-elem" id="tab-page1">
        <button>新規追加</button>
            <table>
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
                    <?php foreach($students as $st): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <!-- 学籍番号 -->
                        <td><?= htmlspecialchars($st['student_id']) ?></td>
                        <!-- 生徒氏名 -->
                        <td class="editable" ><?= htmlspecialchars($st['student_name']) ?></td>
                        <!-- 学科名 -->
                        <td><?= htmlspecialchars($st['department_name']) ?></td>
                        <!-- 学年 -->
                        <td><?= htmlspecialchars($st['grade']) ?></td>
                        <!-- 班 -->
                        <td class="editable"><?= !empty($st['student_group']) ?  htmlspecialchars($st['student_group']) : "-" ?></td>
                        <!-- 在校状況 -->
                        <td class="editable" data-status="<?= (int)$st['status'] ?>"><?= $st['status'] == 1 ? '在校' : '退校・卒業' ?></td>
                        <!-- 編集・削除ボタン -->
                        <td><button class="edit-btn" onclick="toggleEdit(this)">編集</button><button class="delete-btn" onclick="toggledelete(this)">削除</button></td>
                        
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <script>
                function toggleEdit(button) {
                    const row = button.closest('tr');
                    const editableCells = row.querySelectorAll('.editable');
                    const isEditing = button.textContent === '保存';

                    if (!isEditing) {
                       // 編集モードにする
                        editableCells.forEach(cell => {
                            // 在校状況のセルの場合
                            if (cell.hasAttribute('data-status')) {
                                const status = cell.dataset.status;

                                cell.innerHTML = `
                                    <label>
                                        <input type="radio" name="status" value="1"
                                            ${status === '1' ? 'checked' : ''}>
                                        在校
                                    </label>
                                    <label>
                                        <input type="radio" name="status" value="0"
                                            ${status === '0' ? 'checked' : ''}>
                                        退校・卒業
                                    </label>
                                `;
                            } else {
                                // それ以外はテキスト入力
                                const currentText = cell.textContent.trim();
                                cell.innerHTML =
                                    `<input type="text" value="${currentText}">`;
                            }
                        });

                        button.textContent = '保存';
                    } else {
                        // 保存モードにする（inputの内容をテキストに戻す）
                        editableCells.forEach(cell => {
                            const input = cell.querySelector('input');
                            cell.textContent = input.value;
                        });
                        button.textContent = '編集';
                    }
                    
                }

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
        <div class="tabbody-elem" id="tab-page3">タブ3内容タブ3内容タブ3内容</div></div>
    </div>
</div>
<script src="js/tab.js"></script>
</body>
</html>