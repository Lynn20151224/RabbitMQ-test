<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;

// 嘗試創建一個 Spreadsheet 對象
$spreadsheet = new Spreadsheet();

// 檢查對象是否被創建
if ($spreadsheet instanceof Spreadsheet) {
    echo "PhpSpreadsheet 已成功安裝。";
} else {
    echo "PhpSpreadsheet 安裝失敗。";
}


?>