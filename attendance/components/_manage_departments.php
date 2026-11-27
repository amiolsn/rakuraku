<?php

/**
 * 必要な変数:
 * @var array $department_results 
 * @var array $department_options
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

        <input type="hidden" name="tab" value="department">

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
                        <label for="department_filter_id">学科名</label> <!--変更-->
                        <select id="department_filter_id" name="department_filter_id" onchange="this.form.submit()"> <!--変更-->
                            <?php
                            //変更：GETで選択された学科IDを取得
                            $selected_department_id = (isset($_GET['department_filter_id']) && $_GET['department_filter_id'] !== '')
                                ? (int)$_GET['department_filter_id']
                                : null;

                            $allDepartments = $department_options;
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
<?php $departmentCount = count($department_results); ?>
<div class="result-count">
    検索結果：<?= $departmentCount ?>件
</div>

<div class="table_box">

    <?php if ($departmentCount === 0): ?>
        <p>該当する学科は存在しません。</p>

    <?php else: ?>
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
                <?php foreach ($department_results as $dp): ?>
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
    <?php endif; ?>
</div>