<?php

/**
 * 必要な変数:
 * @var array $teachers 
 */
?>

<div class="table-actions-top">
    <!-- 新規追加 -->
    <button
        type="button"
        class="add-btn"
        data-popup-target="teacher-popup">
        新規追加
    </button>
    <?php include 'components/_add_teacher_popup.php'; ?>
    <!-- <button
        type="button"
        class="bulkAdd-btn"
        data-type="teacher">
        教員一括登録
    </button> -->
</div>

<div class="table-actions-top">
    <!-- 検索フィルター -->
    <!-- まだ検索フィルターは作成していない -->
    <form action="" method="GET" class="period-header select">
        <table class="management-table search-table">
            <tr>

                <td></td>
                <td>
                    <div class="form-group">
                        <label for="teacher_id">教員番号</label>
                        <input type="text" id="teacher_id" name="teacher_id"
                            value="<?= htmlspecialchars($_GET['teacher_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </td>

                <td>
                    <div class="form-group">
                        <label for="teacher_name">教員氏名</label>
                        <input type="text" id="teacher_name" name="teacher_name"
                            value="<?= htmlspecialchars($_GET['teacher_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </td>
                <td></td>

                </td>
                <td>
                    <div class="table-actions">
                        <button type="submit" id="search-btn" class="search-btn">検索</button>
                        <button type="button" id="clear-btn" class="clear-btn">クリア</button>
                    </div>
                </td>

            </tr>
        </table>
    </form>





</div>

<table class="management-table">
    <thead>
        <tr>
            <th>No</th>
            <th>教員番号</th>
            <th>教員氏名</th>
            <th>学科名</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php $i = 1; ?>
        <?php foreach ($teachers as $teacher): ?>
            <tr data-teacher-id="<?= htmlspecialchars($teacher['teacher_id']) ?>">
                <td><?= $i++ ?></td>
                <!-- 学籍番号 -->
                <td><?= htmlspecialchars($teacher['teacher_id']) ?></td>
                <!-- 生徒氏名 -->
                <td class="editable" data-field="teacher_name"><?= htmlspecialchars($teacher['teacher_name']) ?></td>
                <!-- 学科名 -->
                <td><?= htmlspecialchars($teacher['department_name']) ?></td>

                <!-- 編集・削除ボタン -->
                <td class="table-actions">
                    <button class="edit-btn" onclick="toggleEdit(this)">編集</button>
                    <button
                        type="button"
                        class="delete-btn"
                        data-type="teacher"
                        data-id="<?= htmlspecialchars($teacher['teacher_id'], ENT_QUOTES, 'UTF-8') ?>">
                        削除
                    </button>
                </td>

            </tr>
        <?php endforeach; ?>
    </tbody>
</table>