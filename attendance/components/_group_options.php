<?php

/**
 * 必要な変数:
 * @var array  $groups         班一覧配列 (student_group カラムを持つ連想配列)
 * @var string $selected_group 選択状態にする班名（任意）
 */

$current_selected = $selected_group ?? '';
$group_list = $groups ?? [];
?>

<option value="">- 班選択 -</option>
<?php foreach ($group_list as $group): ?>
    <?php $group_name = $group['student_group']; ?>
    <option value="<?= htmlspecialchars($group_name, ENT_QUOTES, 'UTF-8') ?>"
        <?=
        (string)$current_selected === (string)$group_name ? 'selected' : '' ?>>
        <?= htmlspecialchars($group_name, ENT_QUOTES, 'UTF-8') ?>班
    </option>
<?php endforeach; ?>