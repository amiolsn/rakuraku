<?php

/**
 * 必要な変数:
 * @var String         $departmentName          学科名
 */
?>


<div id="popup-wrapper">
    <div id="popup-inside">
        <div id="close">x</div>
        <div id="message">
            <table class="management-table add-table">
                <thead>
                    <tr>
                        <th>教員番号</th>
                        <th>教員名</th>
                        <th>学科名</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td><input type="text" id="teacher_id" name="teacher_id" required></td>
                        <td><input type="text" id="teacher_name" name="teacher_name" required></td>
                        <td><span id="department_name">
                                <?= htmlspecialchars($departmentName, ENT_QUOTES, 'UTF-8') ?>
                            </span></td>


                        <td>
                            <button type="button" class="add-btn"
                                onclick="addTeacher(this)">
                                登録
                            </button>
                        </td>

                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>