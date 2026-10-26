
const teacherConfig = {
    // 編集・保存の設定
    rowIdAttribute: 'teacherId',
    idKey: 'teacher_id',
    updateUrl: 'update_teacher.php',
    updateMessage: '教員情報を更新しました。',

    // 新規登録の設定
    addUrl: 'add_teacher.php',
    addMessage: '教員情報を新規登録しました。',
    addFields: [
        {
            key: 'teacher_id',
            elementId: 'teacher_id',
            required: true
        },
        {
            key: 'teacher_name',
            elementId: 'teacher_name',
            required: true
        }
    ]
};

// 編集・保存
function toggleEdit(button) {
    return window.commonToggleEdit(button, teacherConfig);
}

// 新規登録
function addTeacher(button) {
    return addData(button, teacherConfig);
}

// 検索条件のクリア
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('clear-btn')
        ?.addEventListener('click', clearSearch);
});
