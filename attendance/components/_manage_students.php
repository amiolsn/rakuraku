<?php

/**
 * 必要な変数:
 * @var array $students 
 */
?>

<div class="table-actions-top">
    <!-- 新規追加 -->
    <button id="add-btn" class="add-btn">新規追加</button>
    <?php include 'components/_add_student_popup.php'; ?>
    <button
        type="button"
        class="bulkAdd-btn"
        data-type="student">
        生徒一括登録
    </button>
    <?php include 'components/_import_popup.php'; ?>
</div>

<div class="table-actions-top">
    <!-- 検索フィルター -->
    <!-- 学籍番号
                            生徒氏名
                            学年
                            班
                            在校状況 -->
    <table class="management-table">
        <tr>
            <form action="" method="GET" class="period-header select">
                <td></td>
                <td>
                    <label for="student_id">学籍番号</label>
                    <input type="text" name="student_id"
                        value="<?= htmlspecialchars($_GET['student_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                </td>
                <td>
                    <label for="student_name">生徒氏名</label>
                    <input type="text" name="student_name"
                        value="<?= htmlspecialchars($_GET['student_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </td>
                <td></td>
                <td>
                    <label for="grade">学年</label>
                    <select id="grade" name="grade">
                        <?php
                        $selected_grade = $_GET['grade'] ?? '';
                        include 'components/_grade_options.php';
                        ?>
                    </select>
                </td>
                <td>
                    <label for="student_group">班</label>
                    <select id="student_group" name="student_group">
                        <?php
                        $selected_group = $_GET['student_group'] ?? '';
                        include 'components/_group_options.php';
                        ?>
                    </select>
                </td>
                <td>
                    <label for="status">在校状況</label>
                    <select id="status" name="status">
                        <?php
                        $selected_grade = $_GET['status'] ?? '';
                        include 'components/_status_options.php';
                        ?>
                    </select>
                </td>
                <td>
                    <button type="submit" id="search-btn" class="search-btn">検索</button>
                    <button type="button" id="clear-btn" class="clear-btn">クリア</button>
                </td>
            </form>
        </tr>
    </table>





</div>

<table class="management-table">
    <thead>
        <tr>
            <th>No</th>
            <th>学籍番号</th>
            <th>生徒氏名</th>
            <th>学科名</th>
            <th>学年</th>
            <th>班</th>
            <th>在校状況</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php $i = 1; ?>
        <?php foreach ($students as $st): ?>
            <tr data-student-id="<?= htmlspecialchars($st['student_id']) ?>">
                <td><?= $i++ ?></td>
                <!-- 学籍番号 -->
                <td><?= htmlspecialchars($st['student_id']) ?></td>
                <!-- 生徒氏名 -->
                <td class="editable" data-field="student_name"><?= htmlspecialchars($st['student_name']) ?></td>
                <!-- 学科名 -->
                <td><?= htmlspecialchars($st['department_name']) ?></td>
                <!-- 学年 -->
                <td><?= htmlspecialchars($st['grade']) ?></td>
                <!-- 班 -->
                <td class="editable" data-field="student_group"><?= !empty($st['student_group']) ?  htmlspecialchars($st['student_group']) : "-" ?></td>
                <!-- 在校状況 -->
                <td class="editable" data-field="status" data-status="<?= (int)$st['status'] ?>"><?= $st['status'] == 1 ? '在校' : '退校・卒業' ?></td>
                <!-- 編集・削除ボタン -->
                <td class="table-actions">
                    <button class="edit-btn" onclick="toggleEdit(this)">編集</button>
                    <button
                        type="button"
                        class="delete-btn"
                        data-type="student"
                        data-id="<?= htmlspecialchars($st['student_id'], ENT_QUOTES, 'UTF-8') ?>">
                        削除
                    </button>
                </td>

            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include 'components/_delete_popup.php'; ?>
</div>