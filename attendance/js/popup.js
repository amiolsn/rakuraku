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
    console.log('クリックされた要素:', event.target);
    console.log('ポップアップ背景か:', event.target === popup);
    console.log('閉じるボタンか:', event.target.classList.contains('popup-close'));
    // ポップアップの背景をクリックした場合だけ閉じる
    if (event.target === popup) {
      popup.style.display = 'none';

      if (popup.id === 'admin-popup') {
        showAdminMenu();
      }
    }

    // 閉じるボタンをクリックした場合
    if (event.target.classList.contains('popup-close')) {
      popup.style.display = 'none';

      if (popup.id === 'admin-popup') {
        showAdminMenu();
      }
    }
  });
});

const adminPopup = document.getElementById('admin-popup');

console.log('管理者ポップアップ:', adminPopup);
console.log('ポップアップ一覧:', document.querySelectorAll('.popup-wrapper'));

if (adminPopup) {
  console.log(
    '管理者ポップアップのクリックイベント確認用'
  );

  adminPopup.addEventListener('click', event => {
    console.log('admin-popupでクリック:', event.target);
  });
}