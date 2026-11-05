<?php
/**
 * @var array $monthly_summary
 * @var int   $selected_grade
 */

$students            = $monthly_summary['students'] ?? [];
$month_total_periods = $monthly_summary['month_total_periods'] ?? 0;
$cumul_total_periods = $monthly_summary['cumulative_total_periods'] ?? 0;
$class_avg_month     = $monthly_summary['class_avg_month'];
$class_avg_cumul     = $monthly_summary['class_avg_cumul'];
?>

<div class="monthly-summary-wrapper">
    <!-- クラス全体情報のサマリーバー -->
     <div class="summary-info-bar">
        <span>今月総訓練時間数: <strong><?= $month_total_periods ?></strong>時間</span>
        <span>累計総訓練時間数: <strong><?= $cumul_total_periods ?></strong>時間</span>
        <span>クラス平均出席率（今月）: <strong><?= number_format($class_avg_month, 1) ?></strong>%</span>
        <span>クラス平均出席率（累計）: <strong><?= number_format($class_avg_cumul, 1) ?></strong>%</span>
     </div>

     <table class="summary-table monthly-table">
        <thead>
            <tr>
                <th rowspan="2" class="col-num">出席番号</th>
                <th rowspan="2" class="col-name border-col-end">氏名</th>
                <th colspan="2" class="border-day-end">日数</th>
                <th colspan="2" class="border-day-end">回数</th>
                <th colspan="3" class="border-day-end">今月時間数</th>
                <th colspan="3">年度累計（4月～当月）</th>
            </tr>
            <tr>
                <!-- 日数 -->
                <th>出席</th>
                <th class="border-day-end">欠席</th>
                <!-- 回数 -->
                <th>遅刻</th>
                <th class="border-day-end">早退</th>
                <!-- 今月時間数 -->
                <th>出席(h)</th>
                <th>欠席(h)</th>
                <th class="border-day-end">出席率</th>
                <!-- 年度累計 -->
                <th>欠席(h)</th>
                <th>累計出席率</th>
                <th>残猶予時間</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
                <tr>
                    <td colspan="12" class="text-center py-4">対象の生徒データが存在しません。</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; ?>
                <?php foreach ($students as $st): ?>
                    <?php
                        // 警告判定：出席率80％未満、残時間20h以下
                        $is_low_rate_month = ($st['month_rate'] < 80.0);
                    $is_low_rate_cumul     = ($st['cumulative_rate'] < 80.0);
                    $is_danger_remain      = ($st['remaining_absent_h'] <= 20);
                    ?>
                    <tr>
                        <td class="text-center col-num"><?= $no++ ?></td>
                        <td class="name-cell border-day-end"><?= htmlspecialchars($st['student_name'], ENT_QUOTES, 'UTF-8') ?></td>

                        <!-- 日数 -->
                        <td class="text-center"><?= $st['present_days'] ?></td>
                        <td class="text-center border-day-end <?= ($st['absent_days'] > 0) ? 'text-absent' : '' ?>">
                            <?= $st['absent_days'] ?>
                        </td>
                        
                        <!-- 回数 -->
                        <td class="text-center <?= ($st['late_count'] > 0) ? 'text-late' : '' ?>">
                            <?= $st['late_count'] ?>
                        </td>
                        <td class="text-center border-day-end <?= ($st['early_count'] > 0) ? 'text-early' : '' ?>">
                            <?= $st['early_count'] ?>
                        </td>

                        <!-- 今月時間数 -->
                        <td class="text-center"><?= $st['month_present_h'] ?></td>
                        <td class="text-center <?= ($st['month_absent_h'] > 0) ? 'text-absent' : '' ?>">
                            <?= $st['month_absent_h'] ?>
                        </td>
                        <td class="text-center border-day-end rate-cell<?= $is_low_rate_month ? 'alert-rate' : '' ?>">
                            <?= number_format($st['month_rate'], 1) ?>%
                        </td>

                        <!-- 年度累計 -->
                        <td class="text-center <?= ($st['cumulative_absent_h'] > 0) ? 'text-absent' : ''?>">
                            <?= $st['cumulative_absent_h'] ?>
                            <td class="text-center rate-cell <?= $is_low_rate_cumul ? 'alert-rate' : '' ?>">
                                <?= number_format($st['cumulative_rate'], 1) ?>%
                            </td>
                            <td class="text-center remain-cell <?= $is_danger_remain ? 'alert-remain' : '' ?>">
                                <?= $st['remaining_absent_h'] ?>
                            </td>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
     </table>
</div>