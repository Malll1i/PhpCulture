<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header("Location: login.html");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Kultura";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $type = $_POST['type'];
    $location = $_POST['location'];
    $cost = $_POST['cost'];
    $organizer = $_POST['organizer'];
    $contact_info = $_POST['contact_info'];

    if (!empty($title) && !empty($description) && !empty($date) && !empty($time) && !empty($type) && !empty($location) && !empty($cost) && !empty($organizer) && !empty($contact_info)) {
        $stmt = $conn->prepare("INSERT INTO cultural_projects (title, description, date, time, type, location, cost, organizer, contact_info) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $title, $description, $date, $time, $type, $location, $cost, $organizer, $contact_info);

        if ($stmt->execute()) {
            $message = "Проект успешно добавлен!";
        } else {
            $message = "Ошибка при добавлении проекта.";
        }

        $stmt->close();
    } else {
        $message = "Пожалуйста, заполните все поля.";
    }
}

// Получение данных о мероприятиях и участниках
$projects_sql = "SELECT * FROM cultural_projects";
$projects_result = $conn->query($projects_sql);

$projects = [];
if ($projects_result->num_rows > 0) {
    while($project = $projects_result->fetch_assoc()) {
        $projects[] = $project;
    }
}

$participation_sql = "SELECT p.event_id, p.username, p.contact_info, c.title 
                      FROM participation p 
                      JOIN cultural_projects c ON p.event_id = c.id";
$participation_result = $conn->query($participation_sql);

$participations = [];
if ($participation_result->num_rows > 0) {
    while($participation = $participation_result->fetch_assoc()) {
        $participations[] = $participation;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ Панель - Культура</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: url('kultura.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #333;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 30px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-top: 5%;
        }
        h1 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #555;
        }
        input[type="text"], input[type="date"], input[type="time"], textarea, select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input[type="submit"] {
            width: 107%;
            padding: 10px;
            background-color: #5cb85c;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #4cae4c;
        }
        .message {
            margin-top: 20px;
            font-size: 16px;
        }
        .message.success {
            color: #5cb85c;
        }
        .message.error {
            color: #d9534f;
        }
        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Админ Панель</h1>
        <form action="admin.php" method="POST">
            <input type="text" name="title" placeholder="Название проекта" required>
            <textarea name="description" placeholder="Описание проекта" required></textarea>
            <input type="date" name="date" placeholder="Дата" required>
            <input type="time" name="time" placeholder="Время" required>
            <select name="type" required>
                <option value="">Выберите тип</option>
                <option value="Организация мероприятий">Организация мероприятий</option>
                <option value="Выставки">Выставки</option>
                <option value="Концерты">Концерты</option>
            </select>
            <input type="text" name="location" placeholder="Место проведения" required>
            <select name="cost" required>
                <option value="">Выберите платность</option>
                <option value="Платно">Платно</option>
                <option value="Бесплатно">Бесплатно</option>
            </select>
            <input type="text" name="organizer" placeholder="Организатор" required>
            <input type="text" name="contact_info" placeholder="Контактная информация" required>
            <input type="submit" value="Добавить проект">
        </form>
        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'успешно') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <h2>Участники мероприятий</h2>
        <?php if (!empty($participations)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Название мероприятия</th>
                        <th>Имя пользователя</th>
                        <th>Контактная информация</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($participations as $participation): ?>
                        <tr>
                            <td><?= htmlspecialchars($participation['title']) ?></td>
                            <td><?= htmlspecialchars($participation['username']) ?></td>
                            <td><?= htmlspecialchars($participation['contact_info']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Участников нет.</p>
        <?php endif; ?>
    </div>
</body>
</html>
