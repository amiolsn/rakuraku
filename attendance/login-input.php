<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //ログイン認証成功後を想定
    unset($_SESSION['user']);

    // 共通設定の読込
    require_once 'db.php';

    $sql = $pdo->prepare('
     SELECT 
        users.*, 
        usertype.usertype_name, 
        COALESCE(teachers.department_id, department.department_id) AS department_id,
        COALESCE(teachers.teacher_name, department.department_name, students.student_name) AS user_name,
        teachers.is_admin
     FROM users 
     JOIN usertype 
        ON users.usertype_id = usertype.usertype_id
     LEFT JOIN department
        ON users.user_id = department.user_id
     LEFT JOIN teachers
        ON users.user_id = teachers.user_id
     LEFT JOIN students
        ON users.user_id = students.user_id
     WHERE users.login_id = ? 
     AND users.password = ? 
     AND users.is_active = 1
     ');

    $sql->execute([
        $_POST['login'] ?? '',
        $_POST['password'] ?? ''
    ]);

    $row = $sql->fetch();


    if ($row) {
        $_SESSION['user'] = [
            'user_id' => $row['user_id'],
            'password' => $row['password'],
            'usertype_id' => $row['usertype_id'],
            'usertype_name' => $row['usertype_name'],
            'login_id' => $row['login_id'],
            'department_id' => $row['department_id'],
            'user_name' => $row['user_name'],
            'is_admin' => (int)($row['is_admin'] ?? '0'),
        ];

        if ($row['usertype_id'] == 1 || $row['usertype_id'] == 2) {
            header('Location: input.php');
            exit;
        } elseif ($row['usertype_id'] == 3) {
            echo '<script>
            alert("生徒TOP画面はまだ作成されていません。");
            location.href = "login-input.php";
        </script>';
            exit;
        }
    } else {
        $error = 'ログインIDまたはパスワードが正しくありません。';
    }
}

?>


<?php
// データベース接続
require_once 'db.php';

?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>らくらく出席簿 - 出欠入力</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/login.css">

</head>

<body>
    <?php require_once 'components/header.php'; ?>
    <div id="wrapper">

        <div id="today">
            <span>
                <?= date('Y/m/d') ?>
                （<?= ['日', '月', '火', '水', '木', '金', '土'][date('w')] ?>）
            </span>


        </div>

        <main>
            <div id="title-flex">
                <h1>ログイン</h1>

            </div>
            <?php if (isset($error)): ?>
                <p class="error">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>
            <form action="login-input.php" method="post">
                <p>ログインID<br><input type="text" name="login"></p><br>
                <p>パスワード<br><input type="password" name="password"></p><br>
                <input type="submit" value="ログイン" class="submit-btn">
            </form>
        </main>
    </div>
</body>

</html>