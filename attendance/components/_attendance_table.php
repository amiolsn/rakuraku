<?php

/**
 * components/_attendance_table.php
 * 
 * 呼び出し元で必要な変数:
 * @var bool  $is_holiday          休校フラグ
 * @var int   $max_period          最大時限数
 * @var array $existing_periods    時限ごとの科目・教員設定
 * @var array $subjects            科目マスタ配列[cite: 6]
 * @var array $teachers            教員マスタ配列[cite: 6]
 * @var array $students            生徒一覧配列[cite: 6]
 * @var array $existing_attendance 登録済み出欠データ配列[cite: 6]
 */
?>

<!-- 出欠入力テーブル -->
<fieldset <?= $is_holiday ? 'disabled' : '' ?> class="attendance-fieldset">
    <table>
        <thead>
            <tr>
                <th>学籍番号</th>
                <th>氏名</th>
                <th>班</th>
                <!-- 1～7限のヘッダーをループで生成 -->
                <?php for ($p = 1; $p <= $max_period; $p++): ?>

                    <?php
                    // 既存の科目。教員選択があれば初期値にする
                    $saved_subj = $existing_periods[$p]['subject_id'] ?? '';
                    $saved_teach = $existing_periods[$p]['teacher_id'] ?? '';
                    ?>

                    <th class=" period-header">
                        <div><?= $p ?>限</div>

                        <!-- 科目選択プルダウン -->
                        <select id="subject_<?= $p ?>" name="subject_<?= $p ?>">
                            <option value="">- 科目選択 -</option>
                            <?php foreach ($subjects as $sb): ?>
                                <option value="<?= $sb['subject_id'] ?>" <?= $saved_subj == $sb['subject_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sb['subject_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- 教員選択プルダウン -->
                        <select id="teacher_<?= $p ?>" name="teacher_<?= $p ?>">
                            <option value="">- 教員選択 -</option>
                            <?php foreach ($teachers as $tc): ?>
                                <option value="<?= $tc['teacher_id'] ?>" <?= $saved_teach == $tc['teacher_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tc['teacher_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- 時限ごとの一括出席ボタン -->
                        <button type="button" class="btn-all-present" onclick="setAllPresent(<?= $p ?>)">全員出席</button>

                    </th>
                <?php endfor ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $st): ?>
                <tr>
                    <td><?= htmlspecialchars($st['student_id']) ?></td>
                    <td class="name-col"><?= htmlspecialchars($st['student_name']) ?></td>
                    <td><?= !empty($st['student_group']) ?  htmlspecialchars($st['student_group']) : "-" ?></td>

                    <!-- 生徒ごとに各時限のマス目を作成 -->
                    <?php for ($p = 1; $p <= $max_period; $p++): ?>
                        <?php
                        $key = "{$st['student_id']}_{$p}";
                        // 既存レコードがあればそのステータス、なければnull
                        $curr_status = $existing_attendance[$key] ?? null;

                        ?>
                        <!-- セル自体にも目印IDを付与 -->
                        <td id="cell_<?= $key ?>">
                            <div class="radio-group">
                                <!-- name属性を「att_学籍番号_時限」にすることでマス目ごとに独立した1つの選択肢グループになる -->
                                <!-- クリックされたらmarkAsChanged()を呼び出す -->
                                <label>
                                    <input type="radio" name="att_<?= $key ?>" value="1" <?= $curr_status == 1 ? 'checked' : '' ?> onchange="markAsChanged(<?= $st['student_id'] ?>, <?= $p ?>)">出
                                </label>
                                <label>
                                    <input type="radio" name="att_<?= $key ?>" value="2" <?= $curr_status == 2 ? 'checked' : '' ?> onchange="markAsChanged(<?= $st['student_id'] ?>, <?= $p ?>)">欠
                                </label>
                                <label>
                                    <input type="radio" name="att_<?= $key ?>" value="3" <?= $curr_status == 3 ? 'checked' : '' ?> onchange="markAsChanged(<?= $st['student_id'] ?>, <?= $p ?>)">遅
                                </label>
                                <label>
                                    <input type="radio" name="att_<?= $key ?>" value="4" <?= $curr_status == 4 ? 'checked' : '' ?> onchange="markAsChanged(<?= $st['student_id'] ?>, <?= $p ?>)">公
                                </label>
                            </div>
                        </td>
                    <?php endfor; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- 画面右下に固定配置された確定ボタン -->
    <div class="floating-footer">
        <button type="button" class="submit-btn" onclick="submitAttendance()">確定して保存</button>
    </div>
</fieldset>