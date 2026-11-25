<?php

/**
 * 必要な変数:
 * @var array  $allDepartments
 * @var string $selected_department_name
 */
?>

<option value="">- 学科選択 -</option>

<?php foreach ($allDepartments as $department): ?>
    <option
        value="<?= htmlspecialchars($department['department_name'], ENT_QUOTES, 'UTF-8') ?>"
        <?= ($selected_department_name ?? '') === $department['department_name'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($department['department_name'], ENT_QUOTES, 'UTF-8') ?>
    </option>
<?php endforeach; ?>