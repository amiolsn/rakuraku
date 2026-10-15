// 「変更・入力されたマス目」だけを記録しておく箱（キー:"学籍番号_時限")
const changedItems = new Map();

// ラジオボタンがクリック（変更）されたときに動く処理
function markAsChanged(studentId, period) {
  const key = `${studentId}_${period}`;
  const checkedRadio = document.querySelector(
    `input[name="att_${key}"]:checked`,
  );

  if (checkedRadio) {
    // 変更リストに記録する
    changedItems.set(key, {
      student_id: studentId,
      period: period,
      status: parseInt(checkedRadio.value),
    });

    // 入力されたマス目の背景に色付け
    const cell = document.getElementById(`cell_${key}`);
    if (cell) {
      cell.classList.remove("is-missing"); // 未入力クラスを取り外す
      cell.classList.add("is-changed"); // 入力済みクラスを追加
    }
  }
}

// 「確定して保存」ボタンが押されたときの処理
function submitAttendance() {
  // 何も変更されてない場合は送信しない
  if (changedItems.size === 0) {
    alert("変更または入力された出欠データがありません");
    return;
  }

  // 変更があった時限だけを洗い出す
  const affectedPeriods = new Set();
  changedItems.forEach((item) => {
    affectedPeriods.add(item.period);
  });

  // 変更があった時限について、科目と教員が選択されているか確認
  const periodSetting = {};
  for (const p of affectedPeriods) {
    const subjVal = document.getElementById(`subject_${p}`).value;
    const teachVal = document.getElementById(`teacher_${p}`).value;

    if (!subjVal || !teachVal) {
      alert(
        `【未選択エラー】入力があった${p}限の科目と担当教員をプルダウンから選択してください。`,
      );
      return;
    }

    periodSetting[p] = {
      subject_id: parseInt(subjVal),
      teacher_id: parseInt(teachVal),
    };
  }

  // 未入力チェックロジック
  // 対象時限について、生徒全員のラジオボタンが選ばれているか点検
  const missingList = [];

  for (const p of affectedPeriods) {
    students.forEach((st) => {
      const key = `${st.student_id}_${p}`;
      const checkedRadio = document.querySelector(
        `input[name="att_${key}"]:checked`,
      );
      const cell = document.getElementById(`cell_${key}`);

      if (!checkedRadio) {
        // 未入力の生徒を記録
        missingList.push({
          period: p,
          studentName: st.student_name,
        });
        // 該当マス目を赤枠で協調
        if (cell) {
          cell.classList.add("is-missing");
        }
      }
    });
  }

  // 未入力が一件でもあれば保存を中断して警告
  if (missingList.length > 0) {
    // 警告メッセージ作成
    alert(
      `【入力漏れ警告】\n入力中の時限に未入力の生徒が ${missingList.length}件あります。\n赤枠のマス目を入力してください。`,
    );
    return; // 保存処理はストップ
  }

  // 送信するデータ（変更分のみ）を配列にまとめる
  const sendData = Array.from(changedItems.values());

  console.log("対象日時:", targetDate);
  console.log("時限設定:", periodSetting);
  console.log("送信データ:", sendData);

  alert(
    `【保存準備OK】\n` +
      `・対象日時: ${targetDate}` +
      `・対象時限: ${Array.from(affectedPeriods).join(", ")} 限\n` +
      `・更新件数: ${sendData.length} 件\n\n` +
      `変更したデータのみを抽出しました。`,
  );
}

// 日付切り替え関数
function changeDate(newDate) {
  if (newDate) {
    // 未保存のマス目があれば確認を出す
    if (changedItems.size > 0) {
      if (
        !confirm(
          "保存されていない変更があります。破棄して別の日付へ移動しますか？",
        )
      ) {
        location.reload();
        return;
      }
    }
    // 選ばれた日付のURLへジャンプする
    location.href = `input.php?date=${newDate}`;
  }
}
