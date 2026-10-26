<?php

/**
 * 必要な変数:
 * @var String         $departmentName          学科名
 */
?>


<div class="popup-wrapper" id="subject-popup">
    <div class="popup-inside">
        <div class="popup-close">x</div>
        <div id="message">
            <table class="management-table add-table">
                <thead>
                    <tr>
                        <th>科目番号</th>
                        <th>科目名</th>
                        <th>学科名</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td><input type="text" id="subject_id" name="subject_id" required></td>
                        <td><input type="text" id="subject_name" name="subject_name" required></td>
                        <td><span id="department_name">
                                <?= htmlspecialchars($departmentName, ENT_QUOTES, 'UTF-8') ?>
                            </span></td>


                        <td>
                            <button
                                type="button"
                                class="add-btn"
                                onclick="addSubject(this)">
                                登録
                            </button>
                        </td>

                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>