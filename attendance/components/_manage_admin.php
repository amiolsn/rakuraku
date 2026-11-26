<?php

/**
 * 必要な変数:
 * @var array $admins
 */

?>

<div class="table-actions-top">

    <button
        type="button"
        class="add-btn"
        data-popup-target="admin-popup">
        新規追加
    </button>
    <?php include 'components/_add_admin_popup.php'; ?>

    <!-- <button
        type="button"
        class="bulkAdd-btn"
        data-type="admin">
        CSV一括登録
    </button> -->
    <?php include 'components/_import_popup.php'; ?>

</div>

<div class="table-actions-top">
    <!-- 検索フィルター -->

    <form action="" method="GET"
        class="period-header select"
        id="admin-search-form">
        <input type="hidden" name="tab" value="admin">

        <table class="management-table search-table" id="department-table">
            <tr>

                <td></td>
                <td>
                    <div class="form-group">
                        <label for="teacher_id">教員番号</label>
                        <input type="text" name="teacher_id" data-input-type="number"
                            inputmode="numeric"
                            value="<?= htmlspecialchars($_GET['teacher_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </td>

                <td>
                    <div class="form-group">
                        <label for="teacher_name">氏名</label>
                        <input type="text" name="teacher_name"
                            value="<?= htmlspecialchars($_GET['teacher_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </td>

                <td>
                    <div class="form-group">
                        <label for="department_name">学科名</label>
                        <select id="department_name" name="department_name" onchange="this.form.submit()">
                            <?php
                            $selected_department_name = $_GET['department_name'] ?? '';
                            include 'components/_department_options.php';
                            ?>
                        </select>
                    </div>
                </td>

                <td>
                    <div class="form-group">
                        <label for="login_id">ログインID</label>
                        <input type="text" name="login_id"
                            value="<?= htmlspecialchars($_GET['login_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
<?php $adminCount = count($admins); ?>
<div class="result-count">
    検索結果：<?= $adminCount ?>件
</div>
<div class="table_box">
    <table class="management-table">
        <thead>
            <tr>
                <th class="sticky">No</th>
                <th class="sticky">教員番号</th>
                <th class="sticky">氏名</th>
                <th class="sticky">所属学科</th>
                <th class="sticky">ログインID</th>
                <th class="sticky">操作</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; ?>

            <?php foreach ($admins as $ad): ?>
                <tr data-department-id="<?= htmlspecialchars($ad['teacher_id']) ?>">
                    <td><?= $i++ ?></td>
                    <!-- 教員番号 -->
                    <td><?= htmlspecialchars($ad['teacher_id']) ?></td>
                    <!-- 氏名 -->
                    <td><?= htmlspecialchars($ad['teacher_name']) ?></td>

                    <!-- 学科名 -->
                    <td class="editable" data-field="department_name"><?= htmlspecialchars($ad['department_name']) ?></td>

                    <td><?= htmlspecialchars($ad['login_id']) ?></td>

                    <!-- 編集・削除ボタン -->
                    <td class="table-actions">
                        <button class="edit-btn" onclick="toggleAdminEdit(this)">編集</button>
                        <button
                            type="button"
                            class="delete-btn"
                            data-type="admin"
                            data-id="<?= htmlspecialchars($ad['teacher_id'], ENT_QUOTES, 'UTF-8') ?>">
                            削除
                        </button>
                    </td>

                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>