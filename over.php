<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

// 設置 RabbitMQ 的連接
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

// 定義隊列名稱
$queueName = 'tesk_queue';

$channel->queue_declare($queueName, false, true, false, false);

echo " [*] 等待消息。要退出請按 CTRL+C\n";

$callback = function ($msg) {
    echo " [x] 收到 ", $msg->body, "\n";
    $data = json_decode($msg->body, true);

    try {
        // DB 連接
        $dbHost = '192.168.2.128';
        $dbName = 'keywords_test_server';
        $dbUser = 'root';
        $dbPass = 'imagedj';

        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);

        //到DB
        $stmt = $pdo->prepare("INSERT INTO task_summary (requestId, userId, successCount, failedCount, csvPath, csvName) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['requestId'],
            $data['userId'],
            $data['successCount'],
            $data['failedCount'],
            $data['csvPath'],
            $data['csvName']
        ]);

        echo " [O] 資料已存入資料庫\n";
    } catch (PDOException $e) {
        echo " [X] DB錯誤：" . $e->getMessage() . "\n";
    }
    
};

$channel->basic_consume($queueName, '', false, true, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}


$channel->close();
$connection->close();
?>
