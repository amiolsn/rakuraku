// 削除確認ポップアップ
let deleteSelectedType = '';
let deleteSelectedId = '';

const deleteBtns = document.querySelectorAll('.delete-btn');
const deletePopupWrapper = document.getElementById('delete-popup-wrapper');
const deleteClose = document.getElementById('delete-close');

// 各削除ボタンにクリックイベントを設定
deleteBtns.forEach(deleteBtn => {
    deleteBtn.addEventListener('click', () => {
        //押された削除ボタンの情報を保存
        deleteSelectedType = deleteBtn.dataset.type;
        deleteSelectedId = deleteBtn.dataset.id;

        //確認ポップアップを表示
        deletePopupWrapper.style.display = 'block';
    });
});

// 外側または「x」をクリックしたら閉じる
deletePopupWrapper.addEventListener('click', e => {
    if (e.target === deletePopupWrapper || e.target === deleteClose) {
        deletePopupWrapper.style.display = 'none';
    }
});

//確認画面の削除ボタン
document.getElementById('delete-yes').addEventListener('click', () => {
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
