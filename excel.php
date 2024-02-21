<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$inputFileName = 'path/to/your/excel/file.xlsx';

// 讀取檔案
$spreadsheet = IOFactory::load($inputFileName);
$sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

// PDO
$dbHost = '192.168.2.128';
$dbName = 'keywords_test_server';
$dbUser = 'root';
$dbPass = 'imagedj';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}


// Excel有 A、B 和 C 三列要插資料庫
$query = "INSERT INTO your_table_name (column1, column2, column3) VALUES (?, ?, ?)";

$stmt = $pdo->prepare($query);

foreach ($sheetData as $row) {
    // 跳過標題行，如果有的話啦
    if ($row['A'] == 'TitleOfColumnA') {
        continue;
    }

    $stmt->execute([$row['A'], $row['B'], $row['C']]);
}

?>