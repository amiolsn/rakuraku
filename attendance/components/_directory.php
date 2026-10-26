<?php
$folder = 'C:\\test'; // 開きたいフォルダのパス

// ダブルクォートでパスを囲んでエクスプローラーを起動
exec('explorer ' . escapeshellarg($folder));
