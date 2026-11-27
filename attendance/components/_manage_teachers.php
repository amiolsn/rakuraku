<?php

/**
 * 必要な変数:
 * @var array $teachers 
 * @var bool $is_admin
 * @var array $departments
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
    <button
        type="button"
        class="bulkAdd-btn"
        data-type="teacher">
        教員一括登録
    </button>
</div>

<div class="table-actions-top">
    <!-- 検索フィルター -->
    <!-- まだ検索フィルターは作成していない -->
    <form action="" method="GET" class="period-header select">
        <table class="management-table search-table">
            <tr>

                <td>
                    <div class="form-group">
                        <label for="teacher_status">表示状態</label>
                        <select name="teacher_status" id="teacher_status" onchange="this.form.submit()">
                            <option value="1"
                                <?= ($_GET['teacher_status'] ?? '1') === '1' ? 'selected' : '' ?>>
                                有効
                            </option>
                            <option value="0"
                                <?= ($_GET['teacher_status'] ?? '1') === '0' ? 'selected' : '' ?>>
                                無効
                            </option>
                        </select>
                    </div>
                </td>
                <td>
                    <div class="form-group">
                        <label for="teacher_id">教員番号</label>
                        <input type="text" id="teacher_id" name="teacher_id" data-input-type="number"
                            inputmode="numeric"
                            value="<?= htmlspecialchars($_GET['teacher_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="半角数字で入力してください">
                    </div>
                </td>

                <td>
                    <div class="form-group">
                        <label for="teacher_name">教員氏名</label>
                        <input type="text" id="teacher_name" name="teacher_name"
                            value="<?= htmlspecialchars($_GET['teacher_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </td>
                <?php if ($is_admin): ?>
                    <td>
                        <div class="form-group">
                            <label for="department_id">学科</label>
                            <select id="department_id" name="department_id" onchange="this.form.submit()">
                                <?php
                                $allDepartments = $departments;
                                include 'components/_department_options.php';
                                ?>
                            </select>
                        </div>
                    </td>
                <?php else: ?>
                    <td></td>
                <?php endif; ?>

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
<?php $teacherCount = count($teachers); ?>
<div class="result-count">
    検索結果：<?= $teacherCount ?>件
</div>

<div class="table_box">

    <?php if ($teacherCount === 0): ?>
        <p>該当する教員は存在しません。</p>

    <?php else: ?>
        <table class="management-table">
            <thead>
                <tr>
                    <th class="sticky">No</th>
                    <th class="sticky">教員番号</th>
                    <th class="sticky">教員氏名</th>
                    <th class="sticky">学科名</th>
                    <th class="sticky"></th>
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
                            <button class="edit-btn" onclick="toggleTeacherEdit(this)">編集</button>

                            <?php if ((int)$teacher['is_active'] === 1): ?>
                                <!-- 有効な教員 -->
                                <button
                                    type="button"
                                    class="delete-btn"
                                    data-type="teacher"
                                    data-id="<?= htmlspecialchars($teacher['teacher_id'], ENT_QUOTES, 'UTF-8') ?>">
                                    無効化
                                </button>
                            <?php else: ?>
                                <!-- 変更：無効な教員には再有効化ボタンを表示 -->
                                <button
                                    type="button"
                                    class="restore-btn"
                                    data-type="teacher"
                                    data-id="<?= htmlspecialchars($teacher['teacher_id'], ENT_QUOTES, 'UTF-8') ?>"
                                    onclick="restoreTeacher(this.dataset.id)"> <!-- 変更 -->
                                    再有効化
                                </button>
                            <?php endif; ?>
                        </td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>