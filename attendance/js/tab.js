// ---------------------------
// 対象要素の取得
// ---------------------------
let tabsH = document.getElementById('tab-h').getElementsByClassName('tab-elem');
let pagesH = document.getElementById('tabbody-h').getElementsByClassName('tabbody-elem');

// ---------------------------
// タブ切り替え
// ---------------------------
function changeTabH() {
    // href属性値から対象のid名を抜き出す
    let targetIdH = this.dataset.tabid;

    // クリックされたタブページを表示
    for (let i = 0; i < pagesH.length; i++) {
        if (pagesH[i].id != targetIdH) {
            pagesH[i].style.display = "none";
        } else {
            pagesH[i].style.display = "block";
        }
    }

    // クリックされたタブを前面に表示する
    for (let i = 0; i < tabsH.length; i++) {
        tabsH[i].classList.remove('active');
    }
    this.classList.add('active');
}

// ---------------------------
// すべてのタブに対して、タブ切り替えのクリックイベントを設定
// ---------------------------
for (let i = 0; i < tabsH.length; i++) {
    tabsH[i].onclick = changeTabH;
}

// ---------------------------
// 初期表示は先頭のタブ
// ---------------------------
tabsH[0].onclick();