// 削除確認ポップアップ
let deleteSelectedType = '';
let deleteSelectedId = '';

const deleteBtns = document.querySelectorAll('.delete-btn');
const deletePopupWrapper = document.getElementById('delete-popup-wrapper');
const deleteClose = document.getElementById('delete-close');
const deleteCancel = document.getElementById('delete-cancel');

console.log('削除ボタンの数:', deleteBtns.length);

// 各削除ボタンにクリックイベントを設定
deleteBtns.forEach(deleteBtn => {
    deleteBtn.addEventListener('click', () => {
        deleteSelectedType = deleteBtn.dataset.type;
        deleteSelectedId = deleteBtn.dataset.id;

        console.log('削除対象:', deleteSelectedType);
        console.log('削除ID:', deleteSelectedId);
        console.log('ポップアップ要素:', deletePopupWrapper);

        deletePopupWrapper.style.display = 'block';

        console.log('表示設定:', deletePopupWrapper.style.display);
    });
});

// 外側または「x」をクリックしたら閉じる
deletePopupWrapper.addEventListener('click', e => {
    if (e.target === deletePopupWrapper || e.target === deleteClose || e.target === deleteCancel) {
        deletePopupWrapper.style.display = 'none';
    }
});

//確認画面の削除ボタン
document.getElementById('delete-yes').addEventListener('click', () => {
    console.log('削除確認ボタンがクリックされました');
    console.log('選択された種類:', deleteSelectedType);
    console.log('選択されたID:', deleteSelectedId);
    deleteInfo(deleteSelectedType, deleteSelectedId);
});

async function deleteInfo(type, id) {
    alert('削除ボタンが押されました！');
    let recipient = '';
    let alertMsg = '';

    if (type === 'student') {
        alert('生徒削除ボタンが押されました！');
        console.log('生徒を削除：', id);
        //生徒情報の削除処理
        recipient = 'delete_student.php';
        alertMsg = '生徒';

    } else if (type === 'teacher') {
        console.log('教員を削除：', id);
        //教員情報の削除処理
        recipient = 'delete_teacher.php';
        alertMsg = '教員';

    } else if (type === 'subject') {
        console.log('科目を削除：', id);
        //科目情報の削除処理
        recipient = 'delete_subject.php';
        alertMsg = '科目';
    } else if (type === 'department') {
        recipient = 'delete_department.php';
        alertMsg = '学科';
    } else if (type === 'admin') {
        recipient = 'delete_admin.php';
        alertMsg = '教員';
    } else {
        alert('削除対象が正しくありません。');
        return;
    }

    // IDが取得できているか確認
    if (!id) {
        alert('削除対象のIDを取得できませんでした。');
        return;
    }

    try {
        console.log('削除対象:', type);
        console.log('送信するID:', id);

        const response = await fetch(recipient, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id
            })
        });

        console.log('HTTPステータス:', response.status);
        console.log('通信先:', response.url);

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || '保存に失敗しました。');
        }

        alert(`${alertMsg}情報を削除しました。`);
        location.reload();

    } catch (error) {
        console.error(error);
        alert(error.message || '通信エラーが発生しました。');
    }
}



//変更：教員の再有効化処理
async function restoreTeacher(teacherId) {

    if (!teacherId) {
        alert('教員IDを取得できませんでした。');
        return;
    }

    // 再有効化の確認
    if (!confirm('この教員を再有効化しますか？')) {
        return;
    }

    try {
        // PHPに教員IDを送信
        const response = await fetch('restore_teacher.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: teacherId
            })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || '再有効化に失敗しました。');
        }

        alert('教員を再有効化しました。');

        // 一覧を再読み込み
        location.reload();

    } catch (error) {
        console.error(error);
        alert(error.message || '通信エラーが発生しました。');
    }
}


//変更：科目の再有効化処理
async function restoreSubject(subjectId) {

    if (!subjectId) {
        alert('科目番号を取得できませんでした。');
        return;
    }

    // 再有効化の確認
    if (!confirm('この科目を再有効化しますか？')) {
        return;
    }

    try {
        // PHPに科目番号を送信
        const response = await fetch('restore_subject.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: subjectId
            })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || '再有効化に失敗しました。');
        }

        alert('科目を再有効化しました。');

        // 一覧を再読み込み
        location.reload();

    } catch (error) {
        console.error(error);
        alert(error.message || '通信エラーが発生しました。');
    }
}
