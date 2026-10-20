async function toggleEdit(button) {
    const row = button.closest('tr');
    const editableCells = row.querySelectorAll('.editable');
    const isEditing = button.textContent === '保存';

    if (!isEditing) {
    editableCells.forEach(cell => {
        const field = cell.dataset.field;

        // 編集前の値を保存する
        if (field === 'status') {
            cell.dataset.original = cell.dataset.status;
        } else {
            let original = cell.textContent.trim();

            // 班の「-」は空欄として扱う
            if (field === 'student_group' && original === '-') {
                original = '';
            }

            cell.dataset.original = original;
        }

        // 在校状況のセルの場合
        if (field === 'status') {
            const status = cell.dataset.status;

            cell.innerHTML = `
                <label>
                    <input type="radio"
                        name="status_${row.dataset.studentId}"
                        value="1"
                        ${status === '1' ? 'checked' : ''}>
                    在校
                </label>
                <label>
                    <input type="radio"
                        name="status_${row.dataset.studentId}"
                        value="0"
                        ${status === '0' ? 'checked' : ''}>
                    退校・卒業
                </label>
            `;
        } else {
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'edit-input';
            input.value = cell.dataset.original;
            cell.replaceChildren(input);
        }
    });

    button.textContent = '保存';
    return;
}

    await saveStudent(button);

}


async function saveStudent(button) {
    console.log('saveStudent が呼び出されました');
    
    const row = button.closest('tr');
    const studentId = row.dataset.studentId;
    const editableCells = row.querySelectorAll('.editable');
    const changes = {};

    editableCells.forEach(cell => {
        const field = cell.dataset.field;

        // // 編集前の値を保存
        // if (field === 'status') {
        //     cell.dataset.original = cell.dataset.status;
        // } else {
        //     let original = cell.textContent.trim();

        //     // 班の「-」は空欄として扱う
        //     if (field === 'student_group' && original === '-') {
        //         original = '';
        //     }

        //     cell.dataset.original = original;
        // }

        let newValue;

        
        if (field === 'status') {
            const checked = cell.querySelector(
                'input[type="radio"]:checked'
            );
            newValue = checked
                ? checked.value
                : cell.dataset.original;
        } else {
            newValue = cell.querySelector('.edit-input').value.trim();
        }

        console.log('項目:', field);
console.log('変更前:', JSON.stringify(cell.dataset.original));
console.log('変更後:', JSON.stringify(newValue));

        // 変更された項目だけ記録
        if (newValue !== cell.dataset.original) {
            changes[field] = newValue;
        }
    });

    if (Object.keys(changes).length === 0) {
        alert('変更された生徒情報はありません。');
        return;
    }

    button.disabled = true;

    try {
        console.log('送信する変更内容:', changes);
        const response = await fetch('update_student.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                student_id: studentId,
                changes: changes
            })
        });

        console.log('HTTPステータス:', response.status);
console.log('通信先:', response.url);

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || '保存に失敗しました。');
        }

        alert('生徒情報を更新しました。');
        location.reload();

    } catch (error) {
        console.error(error);
        alert(error.message || '通信エラーが発生しました。');
    } finally {
        button.disabled = false;
    }
}


async function toggleAdd(button) {
    
}