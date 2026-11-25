<?php

/**
 * 必要な変数:
 * @var array $teachers
 */
?>

<div id="admin-teacher-list" class="admin-popup-content" style="display: none;">
    <h3>教員一覧から管理者を追加</h3>

    <p>管理者権限を付与する教員を選択してください。</p>

    <!-- 検索フォーム -->
    <form action="" method="GET" class="period-header select">

        <input type="hidden" name="tab" value="admin">

        <table class="management-table search-table">
            <tr>
                <td>
                    <div class="form-group">
                        <label for="admin_teacher_id">教員番号</label>
                        <input
                            type="text"
                            id="admin_teacher_id"
                            name="teacher_id"
                            data-input-type="number"
                            inputmode="numeric"
                            value="<?= htmlspecialchars($_GET['teacher_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </td>

                <td>
                    <div class="form-group">
                        <label for="admin_teacher_name">教員氏名</label>
                        <input
                            type="text"
                            id="admin_teacher_name"
                            name="teacher_name"
                            value="<?= htmlspecialchars($_GET['teacher_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </td>

                <td>
                    <div class="table-actions">
                        <button type="submit" class="search-btn">
                            検索
                        </button>

                        <button
                            type="button"
                            class="clear-btn"
                            id="admin-teacher-clear">
                            クリア
                        </button>
                    </div>
                </td>
            </tr>
        </table>
    </form>

    <!-- 検索結果 -->
    <div class="result-count">
        検索結果：<?= count($teachers) ?>件
    </div>

    <div class="table_box">
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
                    <tr>
                        <td><?= $i++ ?></td>

                        <td>
                            <?= htmlspecialchars($teacher['teacher_id'], ENT_QUOTES, 'UTF-8') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($teacher['teacher_name'], ENT_QUOTES, 'UTF-8') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($teacher['department_name'], ENT_QUOTES, 'UTF-8') ?>
                        </td>

                        <td>
                            <button
                                type="button"
                                class="add-btn"
                                data-teacher-id="<?= htmlspecialchars($teacher['teacher_id'], ENT_QUOTES, 'UTF-8') ?>"
                                onclick="addAdmin(this)">
                                管理者に追加
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($teachers)): ?>
                    <tr>
                        <td colspan="5">該当する教員はいません。</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <button
        type="button"
        class="clear-btn"
        onclick="showAdminMenu()">
        戻る
    </button>

</div>