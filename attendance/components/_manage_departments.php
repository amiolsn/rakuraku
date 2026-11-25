<?php

/**
 * 必要な変数:
 * @var array $departments 
 */

?>

<div class="table-actions-top">

    <button
        type="button"
        class="add-btn"
        data-popup-target="department-popup">
        新規追加
    </button>
    <?php include 'components/_add_department_popup.php'; ?>

    <button
        type="button"
        class="bulkAdd-btn"
        data-type="department">
        CSV一括登録
    </button>
    <?php include 'components/_import_popup.php'; ?>

</div>

<div class="table-actions-top">
    <!-- 検索フィルター -->

    <form action="" method="GET"
        class="period-header select"
        id="department-search-form">

        <table class="management-table search-table" id="department-table">
            <tr>

                <td></td>
                <td>
                    <div class="form-group">
                        <label for="department_id">学科番号</label>
                        <input type="text" name="department_id" data-input-type="number"
                            inputmode="numeric"
                            value="<?= htmlspecialchars($_GET['department_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </td>

                <td>
                    <div class="form-group">
                        <label for="department_name">学科名</label>
                        <select id="department_name" name="department_name">
                            <?php
                            $selected_department_name = $_GET['department_name'] ?? '';
                            include 'components/_department_options.php';
                            ?>
                        </select>
                    </div>
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
<div class="table_box">
    <table class="management-table">
        <thead>
            <tr>
                <th class="sticky">No</th>
                <th class="sticky">学科番号</th>

                <th class="sticky">学科名</th>

                <th class="sticky">操作</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; ?>
            <?php foreach ($departments as $dp): ?>
                <tr data-department-id="<?= htmlspecialchars($dp['department_id']) ?>">
                    <td><?= $i++ ?></td>
                    <!-- 学科番号 -->
                    <td><?= htmlspecialchars($dp['department_id']) ?></td>

                    <!-- 学科名 -->
                    <td class="editable" data-field="department_name"><?= htmlspecialchars($dp['department_name']) ?></td>

                    <!-- 編集・削除ボタン -->
                    <td class="table-actions">
                        <button class="edit-btn" onclick="toggleDepartmentEdit(this)">編集</button>
                        <button
                            type="button"
                            class="delete-btn"
                            data-type="department"
                            data-id="<?= htmlspecialchars($dp['department_id'], ENT_QUOTES, 'UTF-8') ?>">
                            削除
                        </button>
                    </td>

                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>