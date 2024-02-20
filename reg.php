<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['username']) && !empty($_POST['password'])) {
    // DB連接
    $dbHost = '192.168.2.128';
    $dbName = 'keywords_test_server';
    $dbUser = 'root';
    $dbPass = 'imagedj';

    // 創建PDO
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);

    $username = $_POST['username'];
    $password = $_POST['password'];

    // 創建密碼的hash值
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // 存入DB
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
    if ($stmt->execute([$username, $passwordHash])) {
        echo '註冊成功！';
        header('Location: login.html');
        exit;
    } else {
        echo '註冊失敗，請稍後再試。';
    }
}
