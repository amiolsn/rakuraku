const passwordBtn = document.querySelector('.passowrd-btn');
const newPasswordInput = document.querySelector('.new-password');

// 変更ボタンをクリックしたとき
passwordBtn.addEventListener('click', (event) => {
    // フォームの送信を防ぐ
    event.preventDefault();

    // 新しいパスワード入力欄を表示
    newPasswordInput.style.display = 'block';

    console.log('表示設定:', newPasswordInput.style.display);
});