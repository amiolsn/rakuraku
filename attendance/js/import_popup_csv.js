//  import.js
let importSelectedType = '';
let importSelectedId = '';

const bulkAddBtns = document.querySelectorAll('.bulkAdd-btn');
const importPopupWrapper = document.getElementById('import-popup-wrapper');
const importClose = document.getElementById('import-close');
const importCancel = document.getElementById('import-cancel');
const importForm = document.getElementById('import-form');

console.log('取得したボタン数:', bulkAddBtns.length);

bulkAddBtns.forEach(bulkAddBtn => {
    bulkAddBtn.addEventListener('click', () => {
        console.log('一括登録ボタンがクリックされました');

        importSelectedType = bulkAddBtn.dataset.type;
        importSelectedId = bulkAddBtn.dataset.id;

        importPopupWrapper.style.display = 'block';
    });
});

// 外側・x・キャンセルを押したら閉じる
importPopupWrapper.addEventListener('click', e => {
    if (
        e.target === importPopupWrapper ||
        e.target === importClose ||
        e.target === importCancel
    ) {
        importPopupWrapper.style.display = 'none';
    }
});


//エクスプローラー参照ボタン押したらエクスプローラーが起動して選択したファイルを入力欄に表示させて、入力欄に入力されたファイルを取り込みボタンを押したら取り込む

// フォーム送信時にCSVを取り込む
importForm.addEventListener('submit', async e => {
    e.preventDefault();


    alert('取り込みボタンが押されました。');
    const fileInput = document.getElementById('csv-file');
    const file = fileInput.files[0];

    if (!file) {
        alert('ファイルを入力・選択してください');
        return;
    }

    if (!file.name.toLowerCase().endsWith('.csv')) {
        alert('CSVファイルを選択してください。');
        return;
    }

    const text = await file.text();
    const data = text.trim().split('\n')
        .map(line => line.split(',').map(x => x.trim()));
    let recipient = '';
    let alertMsg = '';

    if (importSelectedType === 'student') {
        alertMsg = '生徒';
    } else if (importSelectedType === 'teacher') {
        alertMsg = '教員';
    } else if (importSelectedType === 'subject') {
        alertMsg = '科目';
    } else {
        alert('登録対象が不正です。');
        return;
    }


    // CSVファイルと登録対象を送信
    const formData = new FormData();
    formData.append('csv_file', file);
    formData.append('target', importSelectedType);

    try {
        console.log('一括登録対象:', importSelectedType);

        const response = await fetch('import_csv.php', {
            method: 'POST',
            body: formData
        });

        console.log('HTTPステータス:', response.status);
        console.log('通信先:', response.url);

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || '登録に失敗しました。');
        }

        alert(result.message || `${alertMsg}情報を登録しました。`);

        importPopupWrapper.style.display = 'none';
        location.reload();


    } catch (error) {
        console.error(error);
        alert(error.message || '通信エラーが発生しました。');
    }
});
