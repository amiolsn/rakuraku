
const subjectConfig = {
    // 編集・保存の設定
    rowIdAttribute: 'subjectId',
    idKey: 'subject_id',
    updateUrl: 'update_subject.php',
    updateMessage: '科目情報を更新しました。',

    // 新規登録の設定
    addUrl: 'add_subject.php',
    addMessage: '科目情報を新規登録しました。',
    addFields: [
        {
            key: 'subject_id',
            elementId: 'subject_id',
            required: true
        },
        {
            key: 'subject_name',
            elementId: 'subject_name',
            required: true
        }
    ]
};

// 編集・保存
function toggleEdit(button) {
    return window.commonToggleEdit(button, subjectConfig);
}

// 新規登録
function addSubject(button) {
    return addData(button, subjectConfig);
}
// 検索条件のクリア
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('clear-btn')
        ?.addEventListener('click', clearSearch);
});
