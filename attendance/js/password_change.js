const passwordBtn = document.querySelector('.password-btn');
const newPasswordInput = document.querySelector('.new-password');
const passwordForm = document.querySelector('.update-password');

// 変更ボタンをクリックしたとき
passwordBtn.addEventListener('click', (event) => {
    // フォームの送信を防ぐ
    event.preventDefault();

    // 新しいパスワード入力欄を表示
    newPasswordInput.style.display = 'block';

    console.log('表示設定:', newPasswordInput.style.display);

    passwordBtn.remove();

    const submitBtn = document.createElement('input');
    submitBtn.type = 'submit';
    submitBtn.value = '変更確定';
    submitBtn.className = 'password-submit';

    passwordForm.appendChild(submitBtn);
});



//入力中1文字ずつチェック
const newPassword = document.querySelector('#new-password');
const confirmPassword = document.querySelector('#confirm-password');
const passwordMessage = document.querySelector('#password-message');


//パスワード入力時にチェック
function checkPassword() {
    const password = newPassword.value;
    const confirm = confirmPassword.value;

    // どちらかが未入力ならメッセージを消す
    if (password === '' || confirm === '') {
        passwordMessage.textContent = '';
        passwordMessage.className = '';
        return;
    }

    // パスワードの一致確認
    if (password === confirm) {
        passwordMessage.textContent = 'パスワードが一致しています。';
        passwordMessage.className = 'password-success';
    } else {
        passwordMessage.textContent = 'パスワードが一致しません。';
        passwordMessage.className = 'password-error';
    }
}

// 入力するたびに実行
newPassword.addEventListener('input', checkPassword);
confirmPassword.addEventListener('input', checkPassword);