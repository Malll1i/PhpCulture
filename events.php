<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'admin') {
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

// Обработка добавления отзыва
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id']) && isset($_POST['rating'])) {
    $event_id = $_POST['event_id'];
    $username = $_SESSION['username'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'] ?? '';

    $stmt = $conn->prepare("INSERT INTO reviews (event_id, username, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $event_id, $username, $rating, $comment);
    $stmt->execute();
    $stmt->close();
}

// Обработка регистрации на мероприятие
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['participate_event_id']) && isset($_POST['contact_info'])) {
    $event_id = $_POST['participate_event_id'];
    $username = $_SESSION['username'];
    $contact_info = $_POST['contact_info'];

    $stmt = $conn->prepare("INSERT INTO participation (event_id, username, contact_info) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $event_id, $username, $contact_info);
    $stmt->execute();
    $stmt->close();
}

$whereClauses = [];
if (!empty($_GET['date'])) {
    $date = $_GET['date'];
    $whereClauses[] = "date='$date'";
}
if (!empty($_GET['type'])) {
    $type = $_GET['type'];
    $whereClauses[] = "type='$type'";
}
if (!empty($_GET['location'])) {
    $location = $_GET['location'];
    $whereClauses[] = "location='$location'";
}
if (!empty($_GET['cost'])) {
    $cost = $_GET['cost'];
    $whereClauses[] = "cost='$cost'";
}
if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $whereClauses[] = "(title LIKE '%$search%' OR description LIKE '%$search%' OR organizer LIKE '%$search%')";
}

$where = "";
if (count($whereClauses) > 0) {
    $where = "WHERE " . implode(" AND ", $whereClauses);
}

$sql = "SELECT * FROM cultural_projects $where";
$result = $conn->query($sql);

$events = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

// Получение отзывов для каждого мероприятия
$reviews_sql = "SELECT * FROM reviews";
$reviews_result = $conn->query($reviews_sql);

$reviews = [];
if ($reviews_result->num_rows > 0) {
    while($review = $reviews_result->fetch_assoc()) {
        $reviews[$review['event_id']][] = $review;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мероприятия - Культура</title>
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
            margin-top: 5%;
        }
        h1 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #555;
            text-align: center;
        }
        .filter-form {
            margin-bottom: 20px;
        }
        .filter-form input[type="date"], .filter-form select, .filter-form input[type="text"] {
            width: 150px;
            padding: 10px;
            margin: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .filter-form input[type="submit"] {
            padding: 10px 20px;
            background-color: #5cb85c;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        .filter-form input[type="submit"]:hover {
            background-color: #4cae4c;
        }
        .card {
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .card h2 {
            margin-top: 0;
        }
        .card p {
            margin: 10px 0;
        }
        .card .date-time, .card .location, .card .cost {
            font-weight: bold;
        }
        .review-form, .participation-form {
            margin-top: 20px;
            padding: 10px;
            border-top: 1px solid #ccc;
        }
        .review-form textarea, .participation-form input[type="text"] {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .review-form input[type="number"], .review-form input[type="submit"], .participation-form input[type="submit"] {
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
        }
        .reviews {
            margin-top: 20px;
        }
        .review {
            border-top: 1px solid #ccc;
            padding-top: 10px;
            margin-top: 10px;
        }
        .review .username {
            font-weight: bold;
        }
        .review .rating {
            color: #f39c12;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Мероприятия</h1>
        <form class="filter-form" method="GET" action="events.php">
            <input type="date" name="date" placeholder="Дата">
            <select name="type">
                <option value="">Тип</option>
                <option value="Организация мероприятий">Организация мероприятий</option>
                <option value="Выставки">Выставки</option>
                <option value="Концерты">Концерты</option>
            </select>
            <select name="location">
                <option value="">Место</option>
                <option value="Москва">Москва</option>
                <option value="Санкт-Петербург">Санкт-Петербург</option>
            </select>
            <select name="cost">
                <option value="">Стоимость</option>
                <option value="Платно">Платно</option>
                <option value="Бесплатно">Бесплатно</option>
            </select>
            <input type="text" name="search" placeholder="Поиск по ключевым словам">
            <input type="submit" value="Фильтровать">
        </form>
        <?php foreach ($events as $event): ?>
            <div class="card">
                <h2><?= htmlspecialchars($event['title']) ?></h2>
                <p class="description"><?= htmlspecialchars($event['description']) ?></p>
                <p class="date-time">Дата: <?= htmlspecialchars($event['date']) ?> | Время: <?= htmlspecialchars($event['time']) ?></p>
                <p class="location">Место проведения: <?= htmlspecialchars($event['location']) ?></p>
                <p class="cost">Стоимость: <?= htmlspecialchars($event['cost']) ?></p>
                <p class="organizer">Организатор: <?= htmlspecialchars($event['organizer']) ?></p>
                <p class="contact_info">Контактная информация: <?= htmlspecialchars($event['contact_info']) ?></p>
                
                <div class="reviews">
                    <h3>Отзывы</h3>
                    <?php if (!empty($reviews[$event['id']])): ?>
                        <?php foreach ($reviews[$event['id']] as $review): ?>
                            <div class="review">
                                <p class="username"><?= htmlspecialchars($review['username']) ?></p>
                                <p class="rating">Оценка: <?= htmlspecialchars($review['rating']) ?>/5</p>
                                <p class="comment"><?= htmlspecialchars($review['comment']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Отзывов пока нет.</p>
                    <?php endif; ?>
                </div>
                <div class="review-form">
                    <form action="events.php" method="POST">
                        <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                        <textarea name="comment" placeholder="Комментарий"></textarea>
                        <input type="number" name="rating" min="1" max="5" placeholder="Оценка (1-5)" required>
                        <input type="submit" value="Оставить отзыв">
                    </form>
                </div>
                <div class="participation-form">
                    <form action="events.php" method="POST">
                        <input type="hidden" name="participate_event_id" value="<?= $event['id'] ?>">
                        <input type="text" name="contact_info" placeholder="Ваши контактные данные" required>
                        <input type="submit" value="Участвовать">
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($events)): ?>
            <p>Мероприятия не найдены.</p>
        <?php endif; ?>
    </div>
</body>
</html>
