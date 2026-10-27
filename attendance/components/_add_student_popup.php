<?php

/**
 * 必要な変数:
 * @var String         $department_name          学科名
 */
?>


<div class="popup-wrapper" id="student-popup">
    <div class="popup-inside">
        <div class="popup-close">x</div>
        <div id="message">
            <table class="management-table add-table">
                <thead>
                    <tr>
                        <th>学籍番号</th>
                        <th>生徒氏名</th>
                        <th>学科名</th>
                        <th>学年</th>
                        <th>班</th>
                        <th>在校状況</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td><input type="text" id="student_id" name="student_id" required></td>
                        <td><input type="text" id="student_name" name="student_name" required></td>
                        <td><span id="department_name">
                                <?= htmlspecialchars($department_name, ENT_QUOTES, 'UTF-8') ?>
                            </span></td>
                        <!-- 学年プルダウン -->
                        <td><select id="grade" name="grade">
                                <?php
                                $selected_grade = $_GET['grade'] ?? '';
                                include 'components/_grade_options.php';
                                ?>
                            </select></td>
                        <td><input type="text" id="student_group" name="student_group"></td>
                        <!-- 在校状況ラジオボタン -->
                        <td><label>在校</label>
                            <input type="radio" name="status" value="1" checked>
                            <label>退校・卒業</label>
                            <input type="radio" name="status" value="0">
                        </td>

                        <td>
                            <button
                                type="button"
                                class="add-btn"
                                onclick="addStudent(this)">
                                登録
                            </button>
                        </td>

                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>