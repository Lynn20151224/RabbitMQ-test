<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

header('Content-Type: application/json'); // 設置返回JSON
session_start();
// DB連接
$dbHost = '192.168.2.128';
$dbName = 'keywords_test_server';
$dbUser = 'root';
$dbPass = 'imagedj';

// 建力PDO
$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);


$targetDir = "uploads/";// 假設所有圖片都上傳到這個資料夾
$userId = $_SESSION['user_id']; // 這裡使用登入的的userId
$userDir = $targetDir .  '/'. $userId . '/'; // 建立userId的資料夾

if (!file_exists($userDir)) {
    mkdir($userDir, 0777, true);
}


// 檢柴user_id資料夾裡的資料夾B
$backupDir = $userDir . 'B/';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// 生成唯一的requestId
$requestId = uniqid();

$fileNames = [];
$imageCount = count($_FILES['images']['name']);



try {
    // 處理每一個上傳的檔案
    for ($i = 0; $i < $imageCount; $i++) {
        $fileName = $_FILES["images"]["name"][$i];
        $targetFilePath = $userDir . basename($fileName);
        $backupFilePath = $backupDir . basename($fileName);

        // 移圖片到user_id資料夾
        if (!move_uploaded_file($_FILES["images"]["tmp_name"][$i], $targetFilePath)) {
            throw new Exception('圖片上傳失败');
        }

        // 移圖片到資料夾B
        if (!copy($targetFilePath, $backupFilePath)) {
            throw new Exception('圖片複製到資料夾B失败');
        }

        $fileNames[] = $fileName;
    }

    // 將檔案名稱轉換為JSON格式
    $fileNamesJson = json_encode($fileNames);

    // 開始
    $pdo->beginTransaction();


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
        // "session" => $_SESSION['user_id']
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
