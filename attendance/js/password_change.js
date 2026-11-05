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