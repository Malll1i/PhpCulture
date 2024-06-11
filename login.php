<?php
session_start();

$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "Kultura"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['username']) && isset($_POST['password'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $_SESSION['username'] = $username;
        if ($username == 'admin' && $password == '12345') {
            header("Location: admin.php");
            exit();
        } else {
            header("Location: events.php");
            exit();
        }
    } else {
        echo "Неверный логин или пароль";
    }
} else {
    echo "Пожалуйста, заполните оба поля.";
}

$conn->close();
?>
