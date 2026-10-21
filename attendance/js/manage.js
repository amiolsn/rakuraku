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


async function addStudent(button) {
    alert('登録ボタンが押されました！');
    const studentId = document.getElementById('student_id').value.trim();
    const studentName = document.getElementById('student_name').value.trim();
    const grade = document.getElementById('grade').value;
    const studentGroup = document.getElementById('student_group').value.trim();

    // 選択された在校状況を取得
    const statusElement = document.querySelector(
        'input[name="status"]:checked'
    );

    // 入力チェック
    if (!studentId || !studentName || !grade || !statusElement) {
        alert('必要項目を入力・選択してください');
        return;
    }

    // PHPへ送信するデータをまとめる
    const data = {
        student_id: studentId,
        student_name: studentName,
        grade: grade,
        student_group: studentGroup || null,
        status: statusElement.value
    };

    try {
        //PHPへ送信する
        const response = await fetch('add_student.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(
                data
            )
        }); console.log('HTTPステータス:', response.status);

        const result = await response.json();
        console.log('PHPの応答:', result);



        if (!response.ok || !result.success) {
            throw new Error(result.message || '保存に失敗しました。');
        }

        alert('生徒情報を新規登録しました。');

        location.reload();

    } catch (error) {
        console.error(error);
        alert(error.message || '通信エラーが発生しました。');
    } finally {
        button.disabled = false;
    }
}

function toggledelete(button) {
    const row = button.closest('tr');



    // 削除が押されたらdeleteするもしくは退避させる
    // キャンセルが押されたらポップアップを閉じる
}

document.addEventListener('DOMContentLoaded', function () {
    const clearBtn = document.getElementById('clear-btn');

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            window.location.href = window.location.pathname;
        });
    }
});

// 削除確認ポップアップ
const deleteBtns = document.querySelectorAll('.delete-btn');
const deletePopupWrapper = document.getElementById('delete-popup-wrapper');
const deleteClose = document.getElementById('delete-close');

// 各削除ボタンにクリックイベントを設定
deleteBtns.forEach(deleteBtn => {
    deleteBtn.addEventListener('click', () => {
        deletePopupWrapper.style.display = 'block';
    });
});

// 外側または「x」をクリックしたら閉じる
deletePopupWrapper.addEventListener('click', e => {
    if (e.target === deletePopupWrapper || e.target === deleteClose) {
        deletePopupWrapper.style.display = 'none';
    }
});