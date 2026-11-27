<?php

/**
 * 必要な変数:
 * @var array  $allDepartments
 * @var string $selected_department_name
 */
?>

<!-- <option value="">- 学科選択 -</option> -->

<?php foreach ($allDepartments as $department): ?>
    <!-- <option
        value="<?= htmlspecialchars($department['department_name'], ENT_QUOTES, 'UTF-8') ?>"
        <?= ($selected_department_name ?? '') === $department['department_name'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($department['department_name'], ENT_QUOTES, 'UTF-8') ?>
    </option> -->
<?php endforeach; ?>



<?php
//11/27変更
/**
 * 必要な変数:
 * @var array $allDepartments
 * @var int|null $selected_department_id
 */
?>

<option value="">全学科</option> <!--変更-->

<?php foreach ($allDepartments as $department): ?>
    <option
        value="<?= (int)$department['department_id'] ?>"
        <?= $selected_department_id === (int)$department['department_id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($department['department_name'], ENT_QUOTES, 'UTF-8') ?>
    </option>
<?php endforeach; ?>