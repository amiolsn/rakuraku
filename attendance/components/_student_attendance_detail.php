<?php

/**
 * 個人別勤怠推移表(教員詳細画面、生徒閲覧画面 共通)
 *
 * @var array $student_detail   getStudentTotalSummary()の戻り値
 */

$st                      = $student_detail['student'];
$grade                   = $student_detail['grade'];
$absence_limit           = $student_detail['absence_limit'];
$total_elapsed_periods   = $student_detail['total_elapsed_periods'];
$total_scheduled_periods = $student_detail['total_scheduled_periods'];
$has_prev_year           = ($grade >= 2);

$is_low_rate      = ($st['total_rate'] < 80.0);
$is_danger_remain = ($st['remaining_absent_h'] <= 20);
?>

<div class="student-detail-card" style="max-width: 820px; margin: 0 auto; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: baseline; border-bottom: 2px solid #334155; padding-bottom: 8px; margin-bottom: 20px;">
        <h3 style="margin: 0; font-size: 1.2rem;">
            <?= htmlspecialchars(($st['student_name']), ENT_QUOTES, 'UTF-8') ?>
            <span style="font-size: 0.95rem; font-weight: normal; color: #475569; margin-left: 8px;">(<?= $grade ?>年生)</span>
        </h3>
        <span style="font-size: 0.85rem; color: #64748b;">
            修了要件：欠席上限 <?= $absence_limit ?>時間（出席率80%以上）
        </span>
    </div>

    <!-- 【全体の勤怠関係】サマリー -->
    <div class="table-box" style="margin-bottom: 24px;">
        <table class="summary-table">
            <thead>
                <tr>
                    <th colspan="5">【全体の勤怠関係】</th>
                </tr>
                <tr>
                    <th>出席時間</th>
                    <th>欠席時間</th>
                    <th>累計総時間（予定）</th>
                    <th>通算出席率</th>
                    <th>残猶予時間</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center"><strong><?= $st['total_present_h'] ?></strong> 時間</td>
                    <td class="text-center <?= $st['total_absent_h'] > 0 ? 'text-absent' : 0; ?>">
                        <strong><?= $st['total_absent_h'] ?> 時間</strong>
                    </td>
                    <td class="text-center">
                        <strong><?= number_format($st['total_elapsed_periods']) ?></strong> 時間
                        <span style="font-size: 0.85rem; color: #64748b;">（全<?= $st['total_scheduled_periods'] ?>）</span>
                    </td>
                    <td class="text-center rate-cell <?= $is_low_rate ? 'alert_rate' : '' ?>">
                        <strong><?= number_format($st['total_rate'], 1) ?>%</strong>
                    </td>
                    <td class="center-text remain-cell <?= $is_danger_remain ? 'alert-remain' : '' ?>">
                        <strong><?= $st['remaining_absent_h'] ?></strong> 時間
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="table-box">
        <table class="summary-table">
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
                    <tr style="background-color: #f1f5f9; font-weight: bold;">
                        <td class="text-center">1年生（前年度累計）</td>
                        <td class="text-center"><?= $st['prev_scheduled_h'] ?></td>
                        <td class="text-center"><?= $st['prev_present_h'] ?></td>
                        <td class="text-center <?= $st['prev_absent_h'] > 0 ? 'text-absent' : '' ?>"><?= $st['prev_absent_h'] ?></td>
                        <td class="text-center"><?= number_format($st['prev_rate'], 1) ?></td>
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
                        <td class="text-center"><?= number_format($st['prev_rate'], 1) ?></td>
                        <td class="text-center <?= $mb['remaining_absent_h'] <= 20 ? 'text-absent' : '' ?>">
                            <strong><?= $mb['remaining_absent_h'] ?></strong>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
