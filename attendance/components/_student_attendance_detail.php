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
    </div>
</div>
