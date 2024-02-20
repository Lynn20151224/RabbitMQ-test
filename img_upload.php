<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

header('Content-Type: application/json'); // 設置返回類型為JSON

// DB連接
$dbHost = '192.168.2.128';
$dbName = 'keywords_test_server';
$dbUser = 'root';
$dbPass = 'imagedj';

// 建力PDO
$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);

// 假設所有圖片都上傳到這個資料夾
$targetDir = "uploads/";
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// 生成唯一的requestId
$requestId = uniqid();

$fileNames = [];
$imageCount = count($_FILES['images']['name']);


try {
    // 處理每一個上傳的檔案
    for ($i = 0; $i < $imageCount; $i++) {
        $fileName = $_FILES["images"]["name"][$i];
        $targetFilePath = $targetDir . basename($fileName);
        if (!move_uploaded_file($_FILES["images"]["tmp_name"][$i], $targetFilePath)) {
            throw new Exception('文件上傳失敗');
        }
        $fileNames[] = $fileName;
    }

    // 將檔案名稱轉換為JSON格式
    $fileNamesJson = json_encode($fileNames);

    // 開始
    $pdo->beginTransaction();

    $userId = "1"; // 這裡使用實際的userId

    // 插入一條新的請求記錄到requests表中，包括JSON格式的檔案名稱
    $stmt = $pdo->prepare("INSERT INTO requests (userId, imageCount, filePath, fileName) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $imageCount, $targetDir, $fileNamesJson]);
    $requestId = $pdo->lastInsertId(); // 獲取自動生成的requestId
    $pdo->commit();


    // 發送到RabbitMQ
    // 組裝要發送的資料
    $data = [
        "requestId" => $requestId,
        "userId" => $userId,
        "imageCount" => $imageCount,
        "filePath" => $targetDir,
        "fileName" => $fileNames,
    ];

    // 轉JSON
    $jsonData = json_encode($data);

    // 發送到RabbitMQ...
    $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
    $channel = $connection->channel();
    $channel->queue_declare('json_queue', false, false, false, false);
    $msg = new AMQPMessage($jsonData);
    $channel->basic_publish($msg, '', 'json_queue');

    // 關閉連結
    $channel->close();
    $connection->close();

    // 成功完成，返回上傳訊息
    echo json_encode(['error' => false, 'message' => '圖片上傳成功', 'data' => $data]);
} catch (Exception $e) {
    // 回滾事務
    $pdo->rollBack();
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
