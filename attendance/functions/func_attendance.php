<?php

/**
 * 出欠データ取得・登録関数
 */

/**
 * 対象日の出欠データおよび時限ごとの初期設定（科目・教員）を取得・構築する
 *
 * @param  PDO    $pdo
 * @param  string $target_date
 * @param  int    $max_period
 * @return array  ['existing_attendance' => array
 *                ['studentId_period' => statusId, ... ],
 *                'existing_periods'    => array
 *                [ 'period' => ['subject_id' => ..., teacher_id' => ...], ... ]
 *                ]
 */

function getAttendanceData(PDO $pdo, string $target_date, int $max_period): array
{
    // その日の出欠データDBから探す
    $stmt = $pdo->prepare("
        SELECT student_id, period, subject_id, teacher_id, attendance_status_id
        FROM attendance
        WHERE attendance_date = :att_date
    ");
    $stmt->execute(['att_date' => $target_date]);
    $saved_records = $stmt->fetchAll();

    // 整理用の箱を用意
    $existing_attendance = [];
    $period_subjects     = [];
    $period_teachers     = [];

    // DBからとれたデータを箱に入れる
    foreach ($saved_records as $rec) {
        // 学籍番号_時限をキーにしてステータスを記憶
        $p                         = $rec['period'];
        $key                       = "{$rec['student_id']}_{$p}";
        $existing_attendance[$key] = $rec['attendance_status_id'];

        // 科目IDと教員IDを時限ごとに集める（連想配列のキーにして重複を削除）
        if (!empty($rec['subject_id'])) {
            $period_subjects[$p][$rec['subject_id']] = true;
        }
        if (!empty($rec['teacher_id'])) {
            $period_teachers[$p][$rec['teacher_id']] = true;
        }
    }

    // 時限ごとに全員一致しているかを確認
    $existing_periods = [];
    for ($p = 1; $p <= $max_period; $p++) {
        // 科目の判定
        // 登録されている科目が1つだけなら採用
        $subj_keys  = isset($period_subjects[$p]) ? array_keys($period_subjects[$p]) : [];
        $match_subj = (count($subj_keys) === 1) ? $subj_keys[0] : '';

        // 教員の判定
        $teach_keys  = isset($period_teachers[$p]) ? array_keys($period_teachers[$p]) : [];
        $match_teach = (count($teach_keys) === 1) ? $teach_keys[0] : '';

        $existing_periods[$p] = [
            'subject_id' => $match_subj,
            'teacher_id' => $match_teach,
        ];
    }

    return [
        'existing_attendance' => $existing_attendance,
        'existing_periods'    => $existing_periods,
    ];
}

/**
 * 指定された日付リスト（週間）の出欠データを一括取得する
 *
 * @param  PDO   $pdo
 * @param  array $date_list
 * @return array ["student_id_YYYY-MM-DD_period" => attendance_status_id, ...]
 */
function getWeeklyAttendance(PDO $pdo, array $date_list): array
{
    if (empty($date_list)) {
        return [];
    }

    // IN句のプレイスホルダー
    $place_holders = implode(',', array_fill(0, count($date_list), '?'));

    $stmt = $pdo->prepare("
        SELECT student_id, attendance_date, period, attendance_status_id
        FROM attendance
        WHERE attendance_date IN ({$place_holders})
    ");
    $stmt->execute($date_list);
    $records = $stmt->fetchAll();

    $weekly_attendance = [];
    foreach ($records as $rec) {
        $key                     = "{$rec['student_id']}_{$rec['attendance_date']}_{$rec['period']}";
        $weekly_attendance[$key] = (int)$rec['attendance_status_id'];
    }

    return $weekly_attendance;
}
