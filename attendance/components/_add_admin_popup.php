<div class="popup-wrapper" id="admin-popup">
    <div class="popup-inside">
        <div class="popup-close">x</div>

        <!-- 追加方法の選択画面 -->
        <div id="admin-menu">
            <h3>管理者の新規追加</h3>

            <p>管理者の追加方法を選択してください。</p>

            <div class="admin-add-options">

                <div class="admin-add-option">
                    <h4>教員一覧から追加</h4>
                    <p>
                        登録済みの教員を一覧から選択し、
                        管理者権限を付与します。
                    </p>

                    <button
                        type="button"
                        class="add-btn"
                        onclick="showTeacherList()">
                        教員一覧から選択
                    </button>
                </div>

                <div class="admin-add-option">
                    <h4>新規登録</h4>
                    <p>
                        新しい教員情報とログインアカウントを
                        登録します。
                    </p>

                    <button
                        type="button"
                        class="add-btn"
                        onclick="showAdminRegister()">
                        新規登録へ進む
                    </button>
                </div>

            </div>
        </div>

        <!-- 教員一覧画面 -->
        <?php include 'components/_add_admin_teacher_list.php'; ?>

        <!-- 新規登録画面（後で実装） -->
        <div id="admin-register" style="display: none;">
            <h3>新しい管理者を登録</h3>
            <p>新規登録フォームは次の段階で作成します。</p>

            <button
                type="button"
                class="clear-btn"
                onclick="showAdminMenu()">
                戻る
            </button>
        </div>

    </div>
</div>