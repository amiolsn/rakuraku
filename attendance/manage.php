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
        <div class="tabbody-elem" id="tab-page1">タブ1内容</div>
        <div class="tabbody-elem" id="tab-page2">タブ2内容タブ2内容</div>
        <div class="tabbody-elem" id="tab-page3">タブ3内容タブ3内容タブ3内容</div></div>
    </div>
</div>
<script src="js/tab.js"></script>
</body>
</html>