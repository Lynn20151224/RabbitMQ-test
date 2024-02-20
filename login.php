<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

// DB連接
$dbHost = '192.168.2.128';
$dbName = 'keywords_test_server';
$dbUser = 'root';
$dbPass = 'imagedj';

// 創建PDO
$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);

// 檢查是否登入
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['username']) && !empty($_POST['password'])) {
    $username = $_POST['username'];
    $provided_password = $_POST['password'];

    // 從資料庫中找用戶
    $stmt = $pdo->prepare("SELECT userId, password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($provided_password, $user['password_hash'])) {
        // 密碼正確
        $_SESSION['user_id'] = $user['userId'];
        echo '登入成功';
        header('Location: img_front.html');
        exit;
    } else {
        echo '登入失敗，用戶名或密碼不正確';
    }
} else {
    header('Location: login.html');
    exit;
}
?>
