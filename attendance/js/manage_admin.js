
// const departmentConfig = {
//     // 編集・保存の設定
//     rowIdAttribute: 'departmentId',
//     idKey: 'department_id',
//     updateUrl: 'update_department.php',
//     updateMessage: '学科情報を更新しました。',


//     // 新規登録の設定
//     addUrl: 'add_department.php',
//     addMessage: '学科情報を新規登録しました。',
//     addFields: [
//         {
//             key: 'department_id',
//             elementId: 'department_id',
//             required: true
//         },
//         {
//             key: 'department_name',
//             elementId: 'department_name',
//             required: true
//         }
//     ]
// };


// HTMLから呼び出す関数名は今までどおりにする
function toggleAdminEdit(button) {
    return window.commonToggleEdit(button, departmentConfig);
}



// 管理者追加：教員一覧を表示
function showTeacherList() {
    document.getElementById('admin-menu').style.display = 'none';
    document.getElementById('admin-teacher-list').style.display = 'block';
    document.getElementById('admin-register').style.display = 'none';
}

// 管理者追加：新規登録画面を表示
function showAdminRegister() {
    document.getElementById('admin-menu').style.display = 'none';
    document.getElementById('admin-teacher-list').style.display = 'none';
    document.getElementById('admin-register').style.display = 'block';
}

// 管理者追加：選択画面に戻る
function showAdminMenu() {
    document.getElementById('admin-menu').style.display = 'block';
    document.getElementById('admin-teacher-list').style.display = 'none';
    document.getElementById('admin-register').style.display = 'none';
}
