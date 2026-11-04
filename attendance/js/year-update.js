// 年度更新確認ポップアップ
const yearUpdateBtns = document.querySelectorAll('.year-update-btn');
const yearUpdatePopupWrapper = document.getElementById('year-update-popup-wrapper');
const yearUpdateClose = document.getElementById('year-update-close');
const yearUpdateCancel = document.getElementById('year-update-cancel');

console.log('ボタンの数:', yearUpdateBtns.length);

// 年度更新ボタンにクリックイベントを設定
yearUpdateBtns.forEach(yearUpdateBtn => {
    yearUpdateBtn.addEventListener('click', () => {

        console.log('ポップアップ要素:', yearUpdatePopupWrapper);

        yearUpdatePopupWrapper.style.display = 'block';

        console.log('表示設定:', yearUpdatePopupWrapper.style.display);
    });
});

// 外側または「x」をクリックしたら閉じる
yearUpdatePopupWrapper.addEventListener('click', e => {
    if (e.target === yearUpdatePopupWrapper || e.target === yearUpdateClose || e.target === yearUpdateCancel) {
        yearUpdatePopupWrapper.style.display = 'none';
    }
});

//確認画面の更新ボタン
const yearUpdateYes = document.getElementById('year-update-yes');
yearUpdateYes.addEventListener('click', () => {
    yearUpdate();
});

async function yearUpdate() {
    alert('年度更新ボタンが押されました！');
    yearUpdateYes.disabled = true;
    try {

        const response = await fetch('year_update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        console.log('HTTPステータス:', response.status);
        console.log('通信先:', response.url);

        const data = await response.json();
        console.log('PHPからの応答:', data);
        if (!response.ok || !data.success) {
            throw new Error(data.message || '保存に失敗しました。');
        }
        alert(data.message);
        alert('年度を更新しました。');
        location.reload();

    } catch (error) {
        console.error(error);
        alert(error.message || '通信エラーが発生しました。');
    } finally {

        // 通信終了後に再び有効化
        yearUpdateYes.disabled = false;

    }
}