
const studentConfig = {
    // 編集・保存の設定
    rowIdAttribute: 'studentId',
    idKey: 'student_id',
    updateUrl: 'update_student.php',
    updateMessage: '生徒情報を更新しました。',

    // 在校状況の設定
    statusField: 'status',
    statusOptions: [
        { value: '1', label: '在校' },
        { value: '0', label: '退校・卒業' }
    ],

    // 「-」を空欄として扱う項目
    emptyAsBlankFields: ['student_group'],

    // 新規登録の設定
    addUrl: 'add_student.php',
    addMessage: '生徒情報を新規登録しました。',
    addFields: [
        {
            key: 'student_id',
            elementId: 'student_id',
            required: true
        },
        {
            key: 'student_name',
            elementId: 'student_name',
            required: true
        },
        {
            key: 'grade',
            elementId: 'grade',
            required: true
        },
        {
            key: 'student_group',
            elementId: 'student_group',
            nullable: true
        },
        {
            key: 'status',
            radioName: 'status',
            required: true
        }
    ]
};


// HTMLから呼び出す関数名は今までどおりにする
function toggleEdit(button) {
    return window.commonToggleEdit(button, studentConfig);
}

function addStudent(button) {
    return addData(button, studentConfig);
}


// 検索条件のクリア
document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('clear-btn')
        ?.addEventListener('click', clearSearch);
});
