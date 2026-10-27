<?php
// 未ログイン時の戻り先
$home_url = 'login-input.php';

if (isset($_SESSION['user'])) {
    $userType = (int)($_SESSION['user']['usertype_id'] ?? 0);

    if ($userType === 3) {
        // 生徒の場合
        // 今後TOPを作成したら変更する
        $home_url = "#";
    } else {
        // 学科・教員の場合
        $home_url = 'input.php';
    }
}
?>

<!-- アプリヘッダー＆ロゴマーク -->
<header class="app-header">
    <a href="<?= $home_url ?>" class="logo-wrapper">
        <!-- SVGロゴ：チャコールグレーのバインダー + 淡いピンクのチェックマーク -->
        <svg width="44" height="44" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- バインダークリップ -->
            <rect x="18" y="4" width="12" height="6" rx="2" fill="#343a40" />
            <!-- バインダー本体（チャコールグレー） -->
            <rect x="8" y="8" width="32" height="36" rx="5" fill="#343a40" />
            <!-- 中の用紙（白） -->
            <rect x="12" y="14" width="24" height="26" rx="2" fill="#ffffff" />
            <!-- ノートの罫線 -->
            <line x1="16" y1="20" x2="24" y2="20" stroke="#dee2e6" stroke-width="2" stroke-linecap="round" />
            <line x1="16" y1="26" x2="28" y2="26" stroke="#dee2e6" stroke-width="2" stroke-linecap="round" />
            <!-- スムースチェックマーク（淡いピンク #F7CAD0、バインダーから心地よく飛び出す躍動感） -->
            <path d="M16 29L24 37L41 15" stroke="#F7CAD0" stroke-width="5" stroke-linecap="round"
                stroke-linejoin="round" />
        </svg>

        <!-- ロゴタイプ -->
        <div>
            <div class="logo-text-title"><span>らくらく</span>出席簿</div>
            <div class="logo-text-subtitle">Smart Attendance System</div>
        </div>
    </a>

    <!-- ナビゲーション -->
    <!-- ログインしているときのみナビゲーションを表示する -->
    <?php if (isset($_SESSION['user'])): ?>
        <!-- 中央：ナビゲーション -->
        <nav class="app-nav">
            <a href="#">欠席登録・届け出管理</a>
            <a href="summary.php">集計</a>
            <a href="manage.php">登録情報編集</a>

            <!-- 管理者のみに表示 -->
            <?php if (!empty($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === 1): ?>
                <a href="#" class="admin-link">管理者メニュー</a>
            <?php endif; ?>
        </nav>

        <!-- 右側：アカウント名表示＆ログアウト -->
        <div class="header-user-menu">
            <span class="user-display-badge">
                <?= htmlspecialchars($_SESSION['user']['user_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </span>
            <a href="logout.php" class="header-logout-btn">ログアウト</a>
        </div>
    <?php endif; ?>
</header>