console.log('popup.js 読み込み確認');

document.querySelectorAll('[data-popup-target]').forEach(button => {
  button.onclick = function () {
    console.log('onclick が実行されました！');

    const popupId = this.dataset.popupTarget;
    const popup = document.getElementById(popupId);

    console.log('対象ID:', popupId);
    console.log('ポップアップ:', popup);

    if (popup) {
      popup.style.display = 'flex';
    }
  };
});


// 外側または閉じるボタンをクリックしたら閉じる
document.querySelectorAll('.popup-wrapper').forEach(popup => {
  popup.addEventListener('click', event => {
    if (
      event.target === popup ||
      event.target.classList.contains('popup-close')
    ) {
      popup.style.display = 'none';
    }
  });
});
