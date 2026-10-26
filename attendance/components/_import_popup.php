<!-- _import_popup.php -->
<div id="import-popup-wrapper">
    <div id="import-popup-inside">
        <div id="import-close">x</div>

        <div id="message">
            <h2>一括登録</h2>

            <form id="import-form">
                <label for="csv-file">CSVファイル</label>
                <input
                    type="file"
                    id="csv-file"
                    name="csv_file"
                    accept=".csv"
                    required>

                <button type="submit" id="importing-btn">
                    登録
                </button>

                <button type="button" id="import-cancel">
                    キャンセル
                </button>
            </form>
        </div>
    </div>
</div>