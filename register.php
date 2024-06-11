<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Kultura";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!empty($full_name) && !empty($username) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (full_name, username, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $full_name, $username, $hashed_password);

        if ($stmt->execute()) {
            $message = "Регистрация прошла успешно!";
            $type = "success";
        } else {
            $message = "Ошибка при регистрации.";
            $type = "error";
        }

        $stmt->close();
    } else {
        $message = "Пожалуйста, заполните все поля.";
        $type = "error";
    }

    header("Location: register.html?message=" . urlencode($message) . "&type=" . urlencode($type));
    exit;
}

$conn->close();
?>
