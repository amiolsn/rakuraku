// HTMLから呼び出す関数名は今までどおりにする
function toggleAdminEdit(button) {
    return window.commonToggleEdit(button, departmentConfig);
}


// 管理者追加：教員一覧を表示
function showTeacherList() {
    document.getElementById('admin-menu').style.display = 'none';
    document.getElementById('admin-teacher-list').style.display = 'block';
    document.getElementById('admin-register').style.display = 'none';
}

// 管理者追加：新規登録画面を表示
function showAdminRegister() {
    document.getElementById('admin-menu').style.display = 'none';
    document.getElementById('admin-teacher-list').style.display = 'none';
    document.getElementById('admin-register').style.display = 'block';
}

// 管理者追加：選択画面に戻る
function showAdminMenu() {
    const popup = document.getElementById('admin-popup');


    document.getElementById('admin-menu').style.display = 'block';
    document.getElementById('admin-teacher-list').style.display = 'none';
    document.getElementById('admin-register').style.display = 'none';

}


// 教員一覧の検索条件をクリア
document.addEventListener('click', (event) => {

    const button = event.target.closest('#admin-teacher-clear');

    if (!button) return;

    // 他のクリック処理やフォーム送信を防ぐ
    //ブラウザ標準の動作をキャンセルする
    event.preventDefault();
    //親要素へのイベント伝播を止める
    event.stopPropagation();
    //同じ要素に登録された後続のイベント処理も止める
    event.stopImmediatePropagation();

    console.log('クリア処理を開始');

    const url = new URL(window.location.href);

    url.searchParams.delete('add_teacher_id');
    url.searchParams.delete('add_teacher_name');

    url.searchParams.set('tab', 'admin');
    url.searchParams.set('popup', 'teacher-list');

    console.log('遷移先:', url.toString());

    window.location.href = url.toString();

}, true);


// 管理者追加ポップアップの初期化
document.addEventListener('DOMContentLoaded', () => {
    const popup = document.getElementById('admin-popup');

    if (!popup) return;

    // ポップアップを閉じるときの初期化処理
    // function resetAdminPopup() {
    //     const url = new URL(window.location.href);

    //     // URLのpopupパラメータを削除
    //     url.searchParams.delete('popup');
    //     window.history.replaceState({}, '', url);

    //     // 選択画面に戻す
    //     showAdminMenu();
    // }

    // URLにpopup指定がある場合だけ教員一覧を表示
    const params = new URLSearchParams(window.location.search);

    console.log('popupパラメータ:', params.get('popup'));

    if (params.get('popup') === 'teacher-list') {
        console.log('ポップアップを開きます');

        popup.style.display = 'flex';
        showTeacherList();

        console.log('表示状態:', popup.style.display);
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const backButton = document.getElementById('admin-teacher-back');

    if (!backButton) return;

    backButton.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        showAdminMenu();
    });
});

//戻るボタン確認用ログ
// document.addEventListener('DOMContentLoaded', () => {
//     const backButton = document.getElementById('admin-teacher-back');

//     console.log('戻るボタン:', backButton);

//     if (backButton) {
//         backButton.addEventListener('click', () => {
//             console.log('戻るボタンがクリックされました');
//         });
//     }
// });

async function addAdmin(button) {
    //ボタンから教員IDを取得
    const teacherId = button.dataset.teacherId;
    console.log('取得した教員ID:', teacherId);
    console.log('教員IDの型:', typeof teacherId);
    if (!teacherId) {
        alert('教員IDを取得できませんでした');
        return;
    }

    //登録確認
    if (!confirm('この教員を管理者に追加しますか？')) {
        return;
    }

    button.disabled = true;

    try {
        //PHPへJSON送信
        await postJson('add_admin.php', {
            teacher_id: teacherId
        });

        alert('管理者に追加しました');
        location.reload();
    } catch (error) {
        console.error(error);
        alert(error.message || '通信エラーが発生しました');
    } finally {
        button.disabled = false;
    }
}