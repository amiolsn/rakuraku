/**
 * 週を移動する
 * @param {number} days 移動する日数
 */
function moveWeek(days) {
    const input = document.getElementById('target-date-input');

    const baseDate = input.value
        ? new Date(input.value)
        : new Date();

    baseDate.setDate(baseDate.getDate() + days);

    const y = baseDate.getFullYear();
    const m = String(baseDate.getMonth() + 1).padStart(2, '0');
    const d = String(baseDate.getDate()).padStart(2, '0');

    input.value = `${y}-${m}-${d}`;
    input.form.submit(); b
}

/**
 * 今週に戻る
 */
function goToday() {
    const input = document.getElementById('target-date-input');

    const today = new Date();

    const y = today.getFullYear();
    const m = String(today.getMonth() + 1).padStart(2, '0');
    const d = String(today.getDate()).padStart(2, '0');

    input.value = `${y}-${m}-${d}`;
    input.form.submit();
}