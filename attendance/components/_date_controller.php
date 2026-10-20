<?php

/**
 * components/_date_controller.php
 * 
 * 必要な変数:
 * @var string $target_date 日付 (Y-m-d)
 * @var string $target_w    曜日 ('月', '火' など)
 * @var bool   $is_today    本日フラグ
 * @var bool   $is_holiday  休校フラグ
 * @var string $description 休校理由
 */
?>
<div class="date-controller">
    <span class="data-display-label">対象日</span>
    <div class="date-picker-box" onclick="document.getElementById('targetDateInput').showPicker()">
        <span class="date-text"><?= date('Y/m/d', strtotime($target_date)) ?> (<?= $target_w ?>)</span>

        <!-- カレンダーSVGアイコン -->
        <svg class="calendar-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>

        <!-- 日付入力欄/非表示 -->
        <input type="date" id="targetDateInput" class="hidden-date-input" value="<?= htmlspecialchars($target_date) ?>" onchange="changeDate(this.value)">
    </div>

    <?php if ($is_today): ?>
        <span class="today-badge">本日</span>
    <?php endif; ?>

    <?php if ($is_holiday): ?>
        <span class="holiday-badge">休校日(<?= htmlspecialchars($description) ?>)</span>
    <?php endif; ?>
</div>