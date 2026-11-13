const passwordBtn = document.querySelector('.password-btn');
const newPasswordInput = document.querySelector('.new-password');
const passwordForm = document.querySelector('.update-password');

// 変更ボタンをクリックしたとき
passwordBtn.addEventListener('click', (event) => {
    // フォームの送信を防ぐ
    //preventDefault()  ブラウザが標準で行う動作をキャンセルするメソッド
    event.preventDefault();

    // 新しいパスワード入力欄を表示
    newPasswordInput.style.display = 'block';

    console.log('表示設定:', newPasswordInput.style.display);

    passwordBtn.remove();//パスワード変更ボタンを削除

    //新しいパスワード変更確定ボタンを作成
    const submitBtn = document.createElement('input');
    submitBtn.type = 'submit';
    submitBtn.value = '変更確定';
    submitBtn.className = 'password-submit';

    //passwordFormの最後に追加
    passwordForm.appendChild(submitBtn);
});



//入力中1文字ずつチェック
const newPassword = document.querySelector('#new-password');
const confirmPassword = document.querySelector('#confirm-password');
const passwordMessage = document.querySelector('#password-message');


//パスワード入力時にチェック
function checkPassword() {
    const password = newPassword.value;//新しいパスワードを代入
    const confirm = confirmPassword.value;//確認用のパスワードを代入

    // どちらかが未入力ならメッセージを消す
    if (password === '' || confirm === '') {
        passwordMessage.textContent = '';
        passwordMessage.className = '';
        return;
    }

    // パスワードの一致確認
    if (password === confirm) {
        passwordMessage.textContent = 'パスワードが一致しています。';
        passwordMessage.className = 'password-success';//クラスを付与して文字を緑にする
    } else {
        passwordMessage.textContent = 'パスワードが一致しません。';
        passwordMessage.className = 'password-error';//クラスを付与して文字を赤くする
    }
}

//1文字ずつ入力するたびに実行
//inputイベントは入力内容が変更されるたびに発生する
newPassword.addEventListener('input', checkPassword);
confirmPassword.addEventListener('input', checkPassword);