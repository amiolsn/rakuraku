<?php
/**
 * 訓練全期間集計（クラス通算一覧 ＆ 個人別月別推移ビュー）
 *
 * @var array $total_summary
 * @var int   $selected_grade
 */

$students                = $total_summary['students'] ?? [];
$absent_limit            = $total_summary['absent_limit'] ?? 280;
$total_elapsed_periods   = $total_summary['total_elapsed_periods'] ?? 0;
$total_scheduled_periods = $total_summary['total_scheduled_periods'] ?? 1400;
$class_avg_total         = $total_summary['class_avg_total'] ?? 0.0;
$danger_count            = $total_summary['danger_count'] ?? 0;
$has_prev_year           = ($selected_grade >= 2);
?>

<div class="total-summary-wrapper">
    <!-- クラス全体サマリーバー -->
    <div class="summary-info-bar">
        <span>消化済み総訓練時間: <strong><?= number_format($total_elapsed_periods) ?></strong> / <?= number_format($total_scheduled_periods) ?>時間</span>
        <span>修了要件(欠席上限): <strong><?= $absent_limit ?></strong>時間(出席率80％以上)</span>
        <span>クラス平均通算出席率: <strong><?= number_format($class_avg_total, 1) ?></strong>%</span>
        <span>要注意生徒数: <strong class="<?= $danger_count > 0 ? 'text-absent' : '' ?>"><?= $danger_count ?></strong>名</span>
    </div>
    
    <!-- クラス全員の通算一覧テーブル -->
    <div class="table-box">
        <table class="summary-table monthly-table">
            <thead>
                <tr>
                    <th rowspan="2" class="col-num">出席番号</th>
                    <th rowspan="2" class="col-name border-col-end">氏名</th>
                    <?php if ($has_prev_year): ?>
                        <th colspan="3" class="border-day-end">1年次実績(前年度)</th>
                    <?php endif; ?>
                    <th colspan="3" class="border-day-end"><?= $selected_grade ?>年次実績(今年度)</th>
                    <th colspan="4" class="border-day-end">全期間通算</th>
                    <th rowspan="2">個人帳票</th>
                </tr>
                <tr>
                    <?php if ($has_prev_year): ?>
                        <th>出席(h)</th>
                        <th>欠席(h)</th>
                        <th class="border-day-end">出席率</th>
                    <?php endif; ?>
                    <th>出席(h)</th>
                    <th>欠席(h)</th>
                    <th class="border-day-end">出席率</th>
                    <th>出席(h)</th>
                    <th>欠席(h)</th>
                    <th>通算出席率</th>
                    <th class="border-day-end">残猶予時間</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="13" class="text-center py-4">対象の生徒データが存在しません。</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; ?>
                    <?php foreach ($students as $st): ?>
                        <?php
                            $is_low_rate  = ($st['total_rate'] < 80.0);
                        $is_danger_remain = ($st['remaining_absent_h'] <= 20);
                        ?>
                        <tr>
                            <td class="text-center col-num"><?= $no++ ?></td>
                            <td class="name-cell border-day-end"><?= htmlspecialchars($st['student_name'], ENT_QUOTES, 'UTF-8') ?></td>

                            <?php if ($has_prev_year): ?>
                                <td class="text-center"><?= $st['prev_present_h'] ?></td>
                                <td class="text-center <?= $st['prev_absent_h'] > 0 ? 'text-absent' : '' ?>"><?= $st['prev_absent_h'] ?></td>
                                <td class="text-center border-day-end"><?= number_format($st['prev_rate'], 1) ?>%</td>
                            <?php endif; ?>

                            <td class="text-center"><?= $st['curr_present_h'] ?></td>
                            <td class="text-center <?= $st['curr_absent_h'] > 0 ? 'text-absent' : '' ?>"><?= $st['curr_absent_h'] ?></td>
                            <td class="text-center border-day-end"><?= number_format($st['curr_rate'], 1) ?>%</td>

                            <td class="text-center"><strong><?= $st['total_present_h'] ?></strong></td>
                            <td class="text-center <?= $st['total_absent_h'] > 0 ? 'text-absent' : '' ?>"><strong><?= $st['total_absent_h'] ?></strong></td>
                            <td class="text-center rate-cell <?= $is_low_rate ? 'alert-rate' : '' ?>">
                                <strong><?= number_format($st['total_rate'], 1) ?>%</strong>
                            </td>
                            <td class="text-center border-day-end remain-cell <?= $is_danger_remain ? 'alert-remain' : '' ?>">
                                <strong><?= $st['remaining_absent_h'] ?></strong>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn-nav" onclick="toggleStudentSheet(<?= $st['student_id'] ?>)">
                                    月別推移
                                </button>
                            </td>
                        </tr>

                        <!-- 個人別ビュー -->
                        <tr id="sheet-row-<?= $st['student_id'] ?>" class="student-sheet-row" style="display: none;">
                            <td colspan="<?= $has_prev_year ? 13 : 10 ?>" style="background-color: #f8fafc; padding: 16px;">
                                <div style="max-width: 760px; margin: 0 auto; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 16px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 2px solid #334155; padding-bottom: 6px;">
                                        <h4 style="margin: 0; font-size: 1rem;">
                                            <?= htmlspecialchars($st['student_name'], ENT_QUOTES, 'UTF-8') ?> - 個人別勤怠推移表 (<?= $selected_grade ?>年生)
                                        </h4>
                                        <button type="button" class="btn-nav" onclick="toggleStudentSheet(<?= $st['student_id'] ?>)">閉じる</button>
                                    </div>

                                    <!-- 全体勤怠のサマリー -->
                                    <table class="summary-table" style="margin-bottom: 14px; font-size: 0.9rem">
                                        <thead>
                                            <tr>
                                                <th colspan="4">【全体の勤怠関係】</th>
                                            </tr>
                                            <tr>
                                                <th>出席時間</th>
                                                <th>欠席時間</th>
                                                <th>累計総時間</th>
                                                <th>出席率 (残猶予: <?= $st['remaining_absent_h'] ?>)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="text-center"><strong><?= $st['total_present_h'] ?></strong> 時間</td>
                                                <td class="text-center <?= $st['total_absent_h'] > 0 ? 'text-absent' : '' ?>"><strong><?= $st['total_absent_h'] ?></strong> 時間</td>
                                                <td class="text-center"><strong><?= $total_elapsed_periods ?></strong> 時間</td>
                                                <td class="text-center <?= $is_low_rate ? 'alert-rate' : '' ?>"><strong><?= number_format($st['total_rate'], 1) ?>%</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <!-- 各月の勤怠時間テーブル -->
                                    <table class="summary-table" style="font-size: 0.9rem">
                                        <thead>
                                            <tr>
                                                <th colspan="6">【各月の勤怠時間】</th>
                                            </tr>
                                            <tr>
                                                <th>期間 / 月</th>
                                                <th>訓練時数</th>
                                                <th>出席時間</th>
                                                <th>欠席時間</th>
                                                <th>出席率</th>
                                                <th>残猶予時間</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($has_prev_year): ?>
                                                <tr style="background-color: #f1f5f9; font-weight:bold;">
                                                    <td class="text-center">1年生(前年度累計)</td>
                                                    <td class="text-center"><?= $st['prev_scheduled_h'] ?></td>
                                                    <td class="text-center"><?= $st['prev_present_h'] ?></td>
                                                    <td class="text-center"><?= $st['prev_absent_h'] ?></td>
                                                    <td class="text-center"><?= number_format($st['prev_rate'], 1) ?>%</td>
                                                    <td class="text-center <?= $st['prev_remaining_absent_h'] <= 20 ? 'text-absent' : '' ?>">
                                                        <?= $st['prev_remaining_absent_h'] ?>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>

                                            <?php foreach ($st['monthly_breakdown'] as $mb): ?>
                                                <tr>
                                                    <td class="text-center"><?= htmlspecialchars($mb['label'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td class="text-center"><?= $mb['scheduled_h'] ?></td>
                                                    <td class="text-center"><?= $mb['present_h'] ?></td>
                                                    <td class="text-center <?= $mb['absent_h'] > 0 ? 'text-absent' : '' ?>"><?= $mb['absent_h'] ?></td>
                                                    <td class="text-center"><?= number_format($mb['rate'], 1) ?>%</td>
                                                    <td class="text-center <?= $mb['remaining_absent_h'] <= 20 ? 'text-absent' : '' ?>">
                                                        <strong><?= $mb['remaining_absent_h'] ?></strong>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function toggleStudentSheet(studentId) {
        const row = document.getElementById(`sheet-row-${studentId}`);
        if (!row) return;
        row.style.display = (row.style.display === 'none') ? 'table-row' : 'none';
    }
</script>