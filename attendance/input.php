<?php
// データベース接続
require_once 'db.php';
require_once 'auth.php';

// 対象日付の決定
// URLから日付を取り出し、日付の形式が正しいかチェック
$target_date = isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])
    ? $_GET['date']     // 正しい日付ならその日付を
    : date('Y-m-d');    // なければサーバーの今日の日付

// 今日の日付かを判定
$is_today = ($target_date === date('Y-m-d'));

// 曜日の計算
$week_days = ['日', '月', '火', '水', '木', '金', '土'];
// 該当日の日付から曜日番号を取得（日=0, 月=1, …)
$target_w = $week_days[date('w', strtotime($target_date))];

try {
    $departmentId = $_SESSION['user']['department_id'];

    // 班一覧の取得（重複なし）
    $stmtGroups = $pdo->prepare("
        SELECT DISTINCT student_group
        FROM students
        WHERE department_id = ?
        AND status = 1
        AND student_group IS NOT NULL
        AND student_group <> ''
        ORDER BY student_group ASC
    ");

    $stmtGroups->execute([$departmentId]);
    $groups = $stmtGroups->fetchAll();

    // 生徒一覧の取得（学科名も結合して取得）
    $sql = "
        SELECT
            s.student_id,
            s.student_name,
            s.grade,
            s.student_group,
            d.department_name
        FROM students s
        LEFT OUTER JOIN department d
            ON s.department_id = d.department_id
        WHERE s.status = 1
        AND d.department_id = ?
    ";

    $params = [$departmentId];

    // 学年が選択されている場合だけ条件を追加
    if (isset($_GET['grade']) && $_GET['grade'] !== '') {
        $sql .= " AND grade = ?";
        $params[] = (int)$_GET['grade'];
    }

    // 班が選択されている場合だけ条件を追加
    if (isset($_GET['student_group']) && $_GET['student_group'] !== '') {
        $sql .= " AND s.student_group = ?";
        $params[] = $_GET['student_group'];
    }

    $sql .= " ORDER BY s.student_id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $students = $stmt->fetchAll();


    // 科目一覧の取得
    $stmtSubjects = $pdo->prepare("
        SELECT subject_id, subject_name
        FROM subjects s
        JOIN department d ON s.department_id = d.department_id
        WHERE d.department_id = ?
        ORDER BY subject_id ASC
    ");

    $stmtSubjects->execute([$departmentId]);

    $subjects = $stmtSubjects->fetchAll();

    // 教員一覧の取得
    $stmtTeachers = $pdo->prepare("
        SELECT teacher_id, teacher_name
        FROM teachers t
        JOIN department d ON t.department_id = d.department_id
        WHERE d.department_id = ?
        ORDER BY teacher_id ASC
    ");

    $stmtTeachers->execute([$departmentId]);

    $teachers = $stmtTeachers->fetchAll();

    // カレンダーテーブルから対象日の情報を取得
    $stmtCalendar = $pdo->prepare("
        SELECT
            calendar_date,
            is_holiday,
            max_period,
            description
        FROM school_calendar
        WHERE calendar_date = :att_date
    ");
    $stmtCalendar->execute(['att_date' => $target_date]);
    $calendar_info = $stmtCalendar->fetch();

    // 休校フラグと休校理由の判定
    $is_holiday = false;
    $description = '';
    if ($calendar_info && !empty($calendar_info['is_holiday'])) {
        $is_holiday = true;
        $description = $calendar_info['description'] ?? '休校日';
    }

    // 時限数の動的決定
    $default_max_period = 7;
    $max_period = (!empty($calendar_info['max_period']) && $calendar_info['max_period'] > 0)
        ? (int)$calendar_info['max_period']
        : $default_max_period;

    // 修業年限の取得
    $stmtRequirement = $pdo->prepare("
        SELECT MAX(grade) AS term
        FROM course_requirements
        WHERE department_id = ?
    ");

    $stmtRequirement->execute([$departmentId]);

    $requirement = $stmtRequirement->fetch();


    $years = (int)($requirement['term'] ?? 0);

    // その日の出欠データDBから探す
    $stmtAttendance = $pdo->prepare("
        SELECT student_id, period, subject_id, teacher_id, attendance_status_id
        FROM attendance
        WHERE attendance_date = :att_date
    ");
    $stmtAttendance->execute(['att_date' => $target_date]);
    $saved_records = $stmtAttendance->fetchAll();

    // 整理用の箱を用意
    $existing_attendance = [];  // 生徒の出欠を入れる箱
    $period_subjects = [];      // 時限ごとの科目IDを追跡する箱
    $period_teachers = [];      // 時限ごとの教員IDを追跡する箱

    // DBからとれたデータを箱に入れる
    foreach ($saved_records as $rec) {
        // ”学籍番号_時限”をキーにして、ステータスを記憶
        $p = $rec['period'];
        $key = "{$rec['student_id']}_{$p}";
        $existing_attendance[$key] = $rec['attendance_status_id'];

        // 科目IDと教員IDを時限ごとに集める：連想配列のキーにして重複を削除
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
        $subj_keys = isset($period_subjects[$p]) ? array_keys($period_subjects[$p]) : [];
        $match_subj = (count($subj_keys) === 1) ? $subj_keys[0] : "";

        // 教員の判定
        $teach_keys = isset($period_teachers[$p]) ? array_keys($period_teachers[$p]) : [];
        $match_teach = (count($teach_keys) === 1) ? $teach_keys[0] : "";

        $existing_periods[$p] = [
            'subject_id' => $match_subj,
            'teacher_id' => $match_teach
        ];
    }
} catch (PDOException $e) {
    die('データ取得エラー: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>らくらく出席簿 - 出欠入力</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- ヘッダー -->
    <?php require_once 'components/header.php'; ?>

    <div class="filter-controller">
        <!-- 日付選択コントローラー -->
        <?php require_once 'components/_date_controller.php' ?>

        <!-- 学年・班選択プルダウン -->
        <form action="" method="GET" class="period-header select">
            <select id="grade" name="grade" onchange="this.form.submit()">
                <?php
                $selected_grade = $_GET['grade'] ?? '';
                include 'components/_grade_options.php';
                ?>
            </select>

            <select id="student_group" name="student_group" onchange="this.form.submit()">
                <?php
                $selected_group = $_GET['student_group'] ?? '';
                include 'components/_group_options.php';
                ?>
            </select>
        </form>
    </div>

    <?php if ($is_holiday): ?>
        <div class="holiday-alert-banner">
            本日は「<?= $description ?>」のため休校日です。出欠の新規入力。保存はできません。
        </div>
    <?php else: ?>
        <p class="header-lead">入力または変更を行った時限の出欠のみが、右下の「確定して保存」で送信されます。</p>
    <?php endif; ?>

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



    <!-- PHPデータをJavaScriptで使えるように定義 -->
    <script>
        const targetDate = <?= json_encode($target_date) ?>;
        const students = <?= json_encode($students) ?>;
        const max_period = <?= $max_period ?>;
    </script>

    <!-- 分割したJSファイルの読込 -->
    <script src="js/attendance.js"></script>

</body>

</html>