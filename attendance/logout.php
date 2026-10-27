<?php
session_start();

// ログインしていない場合
if (!isset($_SESSION['user'])) {
    header('Location: login-input.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>らくらく出席簿 - ログアウト確認</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <?php require_once 'components/header.php'; ?>

    <div id="wrapper">
        <main>
            <div id="title-flex">
                <h2>ログアウトしますか？</h2>
            </div>

            <form action="logout-process.php" method="post">
                <input
                    type="submit"
                    value="ログアウト"
                    class="submit-btn"
                    id="logout-btn">

                <button
                    type="button"
                    class="cancel-btn"
                    onclick="history.back()">
                    キャンセル
                </button>
            </form>
        </main>
    </div>
    <script>
        document.getElementById('logout-btn').addEventListener('click', function() {
            localStorage.removeItem('active');
        });
    </script>
</body>

</html>