
/**
 * PHPへのJSON送信
 */
async function postJson(url, data) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
        throw new Error(result.message || '処理に失敗しました。');
    }

    return result;
}


/**
 * 編集モード切替・保存
 */
async function commonToggleEdit(button, config) {
    const row = button.closest('tr');
    const cells = row.querySelectorAll('.editable');

    if (button.textContent !== '保存') {
        cells.forEach(cell => {
            const field = cell.dataset.field;
            let original = cell.textContent.trim();

            if (config.statusField === field) {
                original = cell.dataset.status;
            } else if (
                config.emptyAsBlankFields?.includes(field) &&
                original === '-'
            ) {
                original = '';
            }

            cell.dataset.original = original;

            if (config.statusField === field) {
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
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'edit-input';
                input.value = original;
                cell.replaceChildren(input);
            }
        });

        button.textContent = '保存';
        return;
    }

    await saveData(button, config);
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
 * 新規登録
 */
async function addData(button, config) {
    const data = {};

    for (const field of config.addFields) {
        const element = field.radioName
            ? null
            : document.getElementById(field.elementId);

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
