// ---------------------------
// 対象要素の取得
// ---------------------------
document.addEventListener('DOMContentLoaded', function () {
    const tabContainer = document.getElementById('tab-h');
    const pageContainer = document.getElementById('tabbody-h');

    const tabsH = tabContainer.getElementsByClassName('tab-elem');
    const pagesH = pageContainer.getElementsByClassName('tabbody-elem');
    // ---------------------------
    // タブ切り替え
    // ---------------------------

    const storageKey = 'active';

    // タブの切り替えと保存
    function changeTabH(targetId) {
        // コンテンツを切り替える
        for (let i = 0; i < pagesH.length; i++) {
            pagesH[i].style.display =
                pagesH[i].id === targetId ? 'block' : 'none';
        }

        // タブの選択状態を切り替える
        for (let i = 0; i < tabsH.length; i++) {
            tabsH[i].classList.toggle(
                'active',
                tabsH[i].dataset.tabid === targetId
            );
        }

        // 選択中のタブを保存
        localStorage.setItem(storageKey, targetId);
    }

    // タブのクリックイベントを設定
    for (let i = 0; i < tabsH.length; i++) {
        tabsH[i].addEventListener('click', function () {
            changeTabH(this.dataset.tabid);
        });
    }

    // 保存されたタブを復元
    const savedTab = localStorage.getItem(storageKey);

    // 保存されたIDが実在するタブか確認
    const isValidTab = Array.from(tabsH).some(function (tab) {
        return tab.dataset.tabid === savedTab;
    });

    // 保存値があれば復元し、なければ先頭のタブを表示
    changeTabH(
        isValidTab ? savedTab : tabsH[0].dataset.tabid
    );
});
