
const departmentConfig = {
    // 編集・保存の設定
    rowIdAttribute: 'departmentId',
    idKey: 'department_id',
    updateUrl: 'update_department.php',
    updateMessage: '学科情報を更新しました。',


    // 新規登録の設定
    addUrl: 'add_department.php',
    addMessage: '学科情報を新規登録しました。',
    addFields: [
        {
            key: 'department_id',
            elementId: 'department_id',
            required: true
        },
        {
            key: 'department_name',
            elementId: 'department_name',
            required: true
        }
    ]
};


// HTMLから呼び出す関数名は今までどおりにする
function toggleDepartmentEdit(button) {
    return window.commonToggleEdit(button, departmentConfig);
}

function addDepartment(button) {
    return addData(button, departmentConfig);
}
