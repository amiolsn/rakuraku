<div class="popup-wrapper" id="department-popup">
    <div class="popup-inside">
        <div class="popup-close">x</div>

        <div id="message">
            <table class="management-table add-table">
                <thead>
                    <tr>
                        <th>学科番号</th>
                        <th>学科名</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>
                            <input
                                type="text"
                                id="department_id"
                                name="department_id"
                                data-input-type="number"
                                inputmode="numeric"
                                required>
                        </td>

                        <td>
                            <input
                                type="text"
                                id="department_name"
                                name="department_name"
                                required>
                        </td>

                        <td>
                            <button
                                type="button"
                                class="add-btn"
                                onclick="addDepartment(this)">
                                登録
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>