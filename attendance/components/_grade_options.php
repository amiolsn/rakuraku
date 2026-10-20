<?php

/**
 * 必要な変数:
 * @var int         $years          修業年限（未定義ならデフォルト値でフォールバック）
 * @var string|int  $selected_grade 選択状態にする学年値（任意）
 */

// $selected_grade が呼び出し元で指定されていない場合の安全対策
$current_selected = $selected_grade ?? '';
$max_grade = $years ?? 2;
?>

<option value="">- 学年選択 -</option>
<?php for ($i = 1; $i <= $max_grade; $i++): ?>
    <option value="<?= $i ?>"
        <?=
        (string)$current_selected === (string)$i ? 'selected' : '' ?>>
        <?= $i ?>年生
    </option>
<?php endfor; ?>