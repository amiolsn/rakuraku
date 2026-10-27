<?php

/**
 * 必要な変数:
 * @var array $subjects 
 */
?>

<div class="table-actions-top">
    <!-- 新規追加 -->
    <button
        type="button"
        class="add-btn"
        data-popup-target="subject-popup">
        新規追加
    </button>
    <?php include 'components/_add_subject_popup.php'; ?>
    <button
        type="button"
        class="bulkAdd-btn"
        data-type="subject">
        科目一括登録
    </button>
</div>

<div class="table-actions-top">
    <!-- 検索フィルター -->
    <form action="" method="GET" class="period-header select">
        <table class="management-table">
            <tr>

                <td></td>
                <td>
                    <label for="student_id">科目番号</label>
                    <input type="text" name="subject_id"
                        value="<?= htmlspecialchars($_GET['subject_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                </td>
                <td>
                    <label for="subject_name">科目名</label>
                    <input type="text" name="subject_name"
                        value="<?= htmlspecialchars($_GET['subject_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </td>
                <td></td>

                <td>
                    <button type="submit" id="search-btn" class="search-btn">検索</button>
                    <button type="button" id="clear-btn" class="clear-btn">クリア</button>
                </td>

            </tr>
        </table>
    </form>





</div>

<table class="management-table">
    <thead>
        <tr>
            <th>No</th>
            <th>科目番号</th>
            <th>科目名</th>
            <th>学科名</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php $i = 1; ?>
        <?php foreach ($subjects as $subject): ?>
            <tr data-subject-id="<?= htmlspecialchars($subject['subject_id']) ?>">
                <td><?= $i++ ?></td>
                <!-- 学籍番号 -->
                <td><?= htmlspecialchars($subject['subject_id']) ?></td>
                <!-- 生徒氏名 -->
                <td class="editable" data-field="subject_name"><?= htmlspecialchars($subject['subject_name']) ?></td>
                <!-- 学科名 -->
                <td><?= htmlspecialchars($subject['department_name']) ?></td>
                <!-- 編集・削除ボタン -->
                <td class="table-actions">
                    <button class="edit-btn" onclick="toggleEdit(this)">編集</button>
                    <button
                        type="button"
                        class="delete-btn"
                        data-type="subject"
                        data-id="<?= htmlspecialchars($subject['subject_id'], ENT_QUOTES, 'UTF-8') ?>">
                        削除
                    </button>
                </td>

            </tr>
        <?php endforeach; ?>
    </tbody>
</table>