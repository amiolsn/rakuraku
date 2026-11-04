// あとでHTML要素に設定する CSSクラス名をまとめて管理している部分
const PASSWORD_TOGGLE_CLASSES = {
    wrapper: 'js-password-toggle__wrapper',
    input: 'js-password-toggle__input',
    button: 'js-password-toggle__button',
    passwordToggleIcon: 'js-password-toggle__password-toggle-icon',
    passwordVisible: 'js-password-toggle__password-toggle-visible',
    passwordHidden: 'js-password-toggle__password-toggle-hidden',
};

//目のアイコンを描くための SVGのパスデータ
const SVG_PATHS = {
    passwordVisible: 'M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z',
    passwordHidden: 'M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z'
};

//パスワード入力欄と目のボタンをまとめる div 要素を作る関数
function createWrapper(input, originalDisplay) {
    //div 要素を作成する
    const wrapper = document.createElement('div');
    //クラス名を設定する
    wrapper.className = PASSWORD_TOGGLE_CLASSES.wrapper;
    //元の表示形式を設定する
    wrapper.style.display = originalDisplay;
    //CSSが適用された後の入力欄のスタイルを取得
    wrapper.style.width = window.getComputedStyle(input).width;
    //幅と同様に、入力欄の高さを取得して設定
    wrapper.style.height = window.getComputedStyle(input).height;
    //作成した要素を返す
    return wrapper;
}

//目のボタンを作る
function createToggleButton() {
    const button = document.createElement('button');
    //ボタンの種類を通常のボタンに指定
    button.type = 'button';
    button.className = PASSWORD_TOGGLE_CLASSES.button;
    //ボタンの中にSVGを追加
    button.innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="${PASSWORD_TOGGLE_CLASSES.passwordToggleIcon}">
      <path class="${PASSWORD_TOGGLE_CLASSES.passwordVisible}" d="${SVG_PATHS.passwordVisible}"/>
      <path class="${PASSWORD_TOGGLE_CLASSES.passwordHidden}" d="${SVG_PATHS.passwordHidden}"/>
    </svg>
  `;
    return button;
}

//パスワードの表示・非表示を切り替える
function togglePasswordVisibility(input, passwordVisibleIcon, passwordHiddenIcon) {
    //if で入力欄の種類を判定する
    if (input.type === 'password') {
        //パスワードが隠れている場合
        input.type = 'text';//入力文字を見える状態にする
        passwordVisibleIcon.style.display = 'none';//通常の目のアイコンを隠す
        passwordHiddenIcon.style.display = 'block';//斜線入りの目のアイコンを表示する
    } else {
        //パスワードが表示されている場合
        input.type = 'password';//入力文字を隠す
        passwordVisibleIcon.style.display = 'block';//通常の目のアイコンを表示する
        passwordHiddenIcon.style.display = 'none';//斜線入りの目のアイコンを隠す
    }
}

//入力欄に機能を設定する
//1つのパスワード入力欄に表示切り替え機能を設定する関数
function setupPasswordToggle(input) {
    const originalDisplay = window.getComputedStyle(input).display;//入力欄の display の値を取得し、originalDisplay に保存
    input.classList.add(PASSWORD_TOGGLE_CLASSES.input);//入力欄にクラスを追加

    const wrapper = createWrapper(input, originalDisplay);//ラッパーを作成する
    input.parentNode.insertBefore(wrapper, input);//入力欄の前にラッパーを挿入する
    input.style.paddingRight = '40px';//入力欄を調整する
    input.style.width = wrapper.style.width;
    wrapper.appendChild(input);//入力欄をラッパーの中に移動する

    const toggleButton = createToggleButton();//目のボタンを作って追加する
    wrapper.appendChild(toggleButton);

    //アイコンを取得する
    const passwordVisibleIcon = toggleButton.querySelector(`.${PASSWORD_TOGGLE_CLASSES.passwordVisible}`);
    const passwordHiddenIcon = toggleButton.querySelector(`.${PASSWORD_TOGGLE_CLASSES.passwordHidden}`);
    passwordHiddenIcon.style.display = 'none';

    //クリックイベントを登録する
    toggleButton.addEventListener('click', () => togglePasswordVisibility(input, passwordVisibleIcon, passwordHiddenIcon));
}

//CSSを追加する
function addStyles() {
    const style = document.createElement('style');
    style.textContent = `
    .${PASSWORD_TOGGLE_CLASSES.wrapper} {
      position: relative;
    }
    .${PASSWORD_TOGGLE_CLASSES.input} {
      box-sizing: border-box;
    }
    .${PASSWORD_TOGGLE_CLASSES.button} {
      position: absolute;
      top: 50%;
      right: 10px;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      padding: 0;
      line-height: 0;
    }
    .${PASSWORD_TOGGLE_CLASSES.passwordToggleIcon} {
      width: 20px;
      height: 20px;
    }
  `;
    document.head.appendChild(style);
}

//最後に、ページ読み込み時に実行する
document.addEventListener('DOMContentLoaded', () => {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(setupPasswordToggle);
    addStyles();
});