
/**
 * PHPへのJSON送信
 */
// async：非同期処理を行う関数であることを示す。
// url：送信先のPHPファイルのURL
// data：PHPに送信するデータ
async function postJson(url, data) {
    //PHPへデータを送信する
    // fetch(url)：指定したURLにリクエストを送信する。
    // await：通信が完了するまで待つ。
    // method: 'POST'：POST方式でデータを送信する。
    // headers：送信するデータの形式を指定する。
    // Content - Type: application / json：JSON形式のデータを送信することを示す。
    // JSON.stringify(data)：JavaScriptのデータをJSON形式の文字列に変換する。
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    //PHPから返されたJSONを取得
    const result = await response.json();

    if (!response.ok || !result.success) {
        throw new Error(result.message || '処理に失敗しました。');
    }

    return result;
}


/**
 * 編集モード切替・保存
 */
//config：編集対象の項目や設定情報をまとめたオブジェクト。
async function commonToggleEdit(button, config) {
    console.log('渡されたconfig:', config);
    console.log('configのキー:', Object.keys(config));
    console.log('statusField:', config.statusField);

    //編集対象の行とセルを取得
    const row = button.closest('tr');//行
    const cells = row.querySelectorAll('.editable');//セル

    //現在の表示（押したときの表示）が保存ではないつまり「編集」のとき
    if (button.textContent !== '保存') {
        //各セルを編集可能な状態にする
        cells.forEach(cell => {
            const field = cell.dataset.field;//HTMLの data-field 属性から項目名を取得
            let original = cell.textContent.trim();//セル内の文字列を取得し、前後の空白を取り除く

            //在校状況の値を取得する
            //現在のセルが在校状況の項目だった場合、data-status 属性から値を取得
            if (config.statusField === field) {
                original = cell.dataset.status;
            } else if (
                config.emptyAsBlankFields?.includes(field) && original === '-') {
                //空欄を「-」として表示している項目に対応する
                original = '';
            }

            //編集前の値を保存する
            //編集をキャンセルしたときに元の表示へ戻すために使用
            cell.dataset.original = original;

            //現在のセルが在校状況の項目だった場合、在校状況をラジオボタンに切り替える
            if (config.statusField === field) {

                //map()：選択肢ごとにHTMLを生成する。
                cell.innerHTML = config.statusOptions.map(option => `
                    <label>
                        <input type="radio"
                            name="${config.statusField}_${row.dataset[config.rowIdAttribute]}"
                            value="${option.value}"
                            ${original === option.value ? 'checked' : ''}>
                        ${option.label}
                    </label>
                `).join('');
            } else {
                //通常の項目をテキスト入力欄に切り替える
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'edit-input';
                input.value = original;
                cell.replaceChildren(input);
            }
        });

        //ボタンの表示を「保存」に変更する
        button.textContent = '保存';
        return;
    }

    //保存処理を実行する
    await saveData(button, config);
}

/**
 * 編集モードを終了する
 */
//config：編集対象の項目や設定情報をまとめたオブジェクト。
function cancelEdit(button, config) {
    //編集対象の行を取得
    const row = button.closest('tr');

    //編集可能なセルを取得して繰り返し処理する
    row.querySelectorAll('.editable').forEach(cell => {
        const original = cell.dataset.original;//cell.dataset.original：編集前に保存しておいた値を取得する。
        const field = cell.dataset.field;//cell.dataset.field：セルの項目名を取得する。

        if (field === config.statusField) {
            // 在校状況を元の表示文字列に戻す
            //find()：条件に一致する要素を1つ取得する。
            const option = config.statusOptions.find(
                option => option.value === original//option.value === original：選択肢の値と編集前の値が一致するか確認する。
            );

            //option?.label：該当する選択肢の表示名を取得する。
            cell.textContent = option?.label ?? original;

        } else {
            // 通常の入力項目を元の値に戻す
            cell.textContent =
                config.emptyAsBlankFields?.includes(field) && original === ''
                    ? '-'
                    : original;
        }

        // 保存していた元の値を削除
        delete cell.dataset.original;
    });

    // ボタンの文字を編集に戻す
    button.textContent = '編集';
}

/**
 * 編集内容を保存
 */
async function saveData(button, config) {
    const row = button.closest('tr');
    const id = row.dataset[config.rowIdAttribute];
    const changes = {};

    row.querySelectorAll('.editable').forEach(cell => {
        const field = cell.dataset.field;
        let value;

        if (field === config.statusField) {
            value = cell.querySelector('input:checked')?.value
                ?? cell.dataset.original;
        } else {
            value = cell.querySelector('.edit-input').value.trim();
        }

        if (value !== cell.dataset.original) {
            changes[field] = value;
        }
    });

    if (Object.keys(changes).length === 0) {
        alert('変更された情報はありません。');
        //編集モードを終了したい
        cancelEdit(button, config);
        return;
    }

    button.disabled = true;

    try {
        const data = {
            [config.idKey]: id,
            changes: changes
        };

        console.log('更新URL:', config.updateUrl);
        console.log('送信データ:', data);

        await postJson(config.updateUrl, {
            [config.idKey]: id,
            changes: changes
        });

        alert(config.updateMessage);
        location.reload();
    } catch (error) {
        console.error(error);
        alert(error.message || '通信エラーが発生しました。');
    } finally {
        button.disabled = false;
    }
}


/**
 * 新規登録:新規登録フォームに入力された情報を取得し、入力チェックを行ったうえで、PHPにJSON形式で送信してデータベースに登録する処理
 */
async function addData(button, config) {
    //data = {}：PHPに送信するデータを格納する空のオブジェクト。
    const data = {};

    //登録項目を1つずつ取得する
    //for...of：配列の要素を1つずつ取り出して繰り返す。
    for (const field of config.addFields) {
        //入力欄を取得する
        //入力項目がラジオボタンかどうかによって処理を分岐
        const element = field.radioName
            ? null
            : document.getElementById(field.elementId);

        //入力欄が存在するか確認する
        if (!field.radioName && !element) {
            console.error(`入力欄が見つかりません: ${field.elementId}`);
            alert('入力フォームの設定に誤りがあります。');
            return;
        }

        let value;

        if (field.radioName) {
            value = document.querySelector(
                `input[name="${field.radioName}"]:checked`
            )?.value ?? '';
        } else {
            value = element.value.trim();
        }

        if (field.required && !value) {
            console.log('必須項目が未入力:', {
                key: field.key,
                elementId: field.elementId,
                radioName: field.radioName,
                value: value
            });

            alert('必要項目を入力・選択してください。');
            return;
        }

        data[field.key] = field.nullable && !value
            ? null
            : value;
    }

    button.disabled = true;

    try {
        await postJson(config.addUrl, data);

        alert(config.addMessage);
        location.reload();
    } catch (error) {
        console.error(error);
        alert(error.message || '通信エラーが発生しました。');
    } finally {
        button.disabled = false;
    }
}


/**
 * 検索条件のクリア
 */
function clearSearch() {
    window.location.href = window.location.pathname;
}

// クリアボタンのイベント登録
document.addEventListener('click', (event) => {
    if (event.target.closest('.clear-btn')) {
        clearSearch();
    }
});


/**
 * 数字入力欄の入力制御
 *
 * ・全角数字を半角数字に変換する
 * ・半角数字以外の文字を削除する
 * ・IME（日本語入力）の変換中は処理を行わない
 * ・入力後もカーソル位置を維持する
 */
document.addEventListener('DOMContentLoaded', function () {


    //  全角数字を半角数字に変換する
    //  @param {string} str 変換対象の文字列
    //  @return {string} 半角数字に変換した文字列
    function convertToHalfWidth(str) {
        return str.replace(/[０-９]/g, function (s) {
            return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
        });
    }


    //  数字入力欄の入力内容を制御する
    //  @param {Event} e inputイベント
    function handleNumberInput(e) {

        const el = e.target;

        // IMEによる日本語変換中は処理を行わない
        if (e.isComposing || el.dataset.composing === 'true') {
            return;
        }

        // 現在のカーソル位置を取得
        const start = el.selectionStart;
        const end = el.selectionEnd;

        // 全角数字を半角数字に変換し、数字以外の文字を削除
        const converted = convertToHalfWidth(el.value)
            .replace(/[^0-9]/g, '');

        // 入力内容に変更がある場合だけ書き換える
        //el.valueは変換前の値convertedは変換後の値
        if (el.value !== converted) {
            //el.valueを変換後のconvertedに更新
            el.value = converted;

            // 書き換え前のカーソル位置を復元
            el.setSelectionRange(start, end);
        }
    }

    // 入力制限の対象となる要素にイベントを設定
    document.querySelectorAll('[data-input-type="number"]').forEach(function (el) {

        // IMEによる文字変換の開始を検知
        el.addEventListener('compositionstart', function () {
            el.dataset.composing = 'true';//trueは変換中であることを記録
        });

        // IMEによる文字変換の確定を検知
        el.addEventListener('compositionend', function (e) {
            el.dataset.composing = 'false';//変換中の状態を解除

            // 変換確定後に入力内容を制御
            handleNumberInput(e);
        });

        // 通常入力・コピー＆ペーストなどを検知
        el.addEventListener('input', handleNumberInput);

    });

});