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

    // manage_admin.php の場合
    if (document.body.dataset.page === 'manage-admin') {

        // PHP側で指定された初期タブを優先する
        const initialTab = document.querySelector('.tab-elem.active');

        changeTabH(
            initialTab
                ? initialTab.dataset.tabid
                : tabsH[0].dataset.tabid
        );

    } else {

        // それ以外のページは従来どおりlocalStorageを使用
        const savedTab = localStorage.getItem(storageKey);

        const isValidTab = Array.from(tabsH).some(function (tab) {
            return tab.dataset.tabid === savedTab;
        });

        changeTabH(
            isValidTab ? savedTab : tabsH[0].dataset.tabid
        );
    }
});
