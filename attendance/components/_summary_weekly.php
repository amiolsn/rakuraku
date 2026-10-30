<?php

/**
 * components/_summary_weekly.php
 *
 * @var array $week_dates
 * @var array $students
 * @var array $weekly_attendance ["studentId_date_period" => status_id]
 */

$max_period = 7;
?>

<div class="weekly-table-wrapper">
    <table class="summary-table weekly-table">
        <thead>
            <!-- １段目ヘッダー：出席番号・氏名・各日付 -->
            <tr>
                <th rowspan="2" class="col-num">出席番号</th>
                <th rowspan="2" class="col-name">氏名</th>
                <?php foreach ($week_dates as $wd): ?>
                    <?php
                    // 休校日なら1列、授業日ならmax_period 分結合
                    $colspan = ($wd['is_holiday'] || $wd['max_period'] === 0) ? 1 : $wd['max_period'] ;
                    ?>
                    <th colspan="<?= $colspan ?>" class="day-group-header <?= $wd['is_holiday'] ? 'holiday-header' : '' ?>">
                        <?= $wd['month'] ?>月<?= $wd['day'] ?>日 (<?= $wd['week_day'] ?>)
                    </th>
                <?php endforeach; ?>
            </tr>
            <!-- 2段目ヘッダー：時限数(休校日は理由を表示) -->
            <tr>
                <?php foreach ($week_dates as $wd): ?>
                    <?php if ($wd['is_holiday'] || $wd['max_period'] === 0): ?>
                        <th class="period-th holiday-th">
                            <?= htmlspecialchars($wd['description'] ?: '休校', ENT_QUOTES, 'UTF-8') ?>
                        </th>
                    <?php else: ?>
                        <?php for ($p = 1; $p <= $wd['max_period']; $p++): ?>
                            <th class="period-th"><?= $p ?></th>
                        <?php endfor; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
                <tr>
                    <td colspan="<?= 2 + (count($week_dates) * $max_period) ?>" class="text-center py-4">
                        対象の生徒データが存在しません
                    </td>
                </tr>
            <?php else: ?>
                <?php $no = 1; ?>
                <?php foreach ($students as $st): ?>
                    <tr>
                        <td class="text-center col-num"><?= $no++ ?></td>
                        <td class="name-cell"><?= htmlspecialchars($st['student_name'], ENT_QUOTES, 'UTF-8') ?></td>

                        <!-- 月～金 × 1～7限セルの展開 -->
                        <?php foreach ($week_dates as $wd): ?>
                            <?php if ($wd['is_holiday'] || $wd['max_period'] === 0): ?>
                                <!-- 休校日のセル -->
                                <td class="cell-period holiday-cell">-</td>
                            <?php else: ?>
                                <!-- 授業日のコマ 1～max_period -->
                                <?php for ($p = 1; $p <= $wd['max_period']; $p++): ?>
                                    <?php
                                    $cell_key = "{$st['student_id']}_{$wd['date']}_{$p}";
                                    $status   = $weekly_attendance[$cell_key] ?? null;

                                    $status_class = '';
                                    $status_text  = '';

                                    if ($status === 1) {
                                        $status_class = 'status_present';
                                    } elseif ($status === 2) {
                                        $status_class = 'status-absent';
                                        $status_text  = '1';
                                    } elseif ($status === 3) {
                                        $status_class = 'status-late';
                                        $status_text  = '1';
                                    } elseif ($status === 4) {
                                        $status_class = 'status-leave';
                                        $status_text  = '1';
                                    }
                                    ?>
                                    <td class="cell-period <?= $status_class ?>">
                                        <?= $status_text ?>
                                    </td>
                                <?php endfor; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>