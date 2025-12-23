<?php
session_start();
require_once 'datebase_request.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.html');
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$user_type = $_POST['user_type'] ?? '';

if (empty($email) || empty($password)) {
    die('Missing login data.');
}

try {
    if ($user_type === 'volonter') {

        $stmt = $pdo->prepare(
            "SELECT * FROM volonteri WHERE volonter_email = :email"
        );
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['volonter_lozinka'])) {

            $_SESSION['user_id'] = $user['volonter_id'];
            $_SESSION['user_type'] = 'volonter';
            $_SESSION['user_name'] = $user['volonter_ime'];
            header('Location: ../pages/Helpout_main.php');
            exit;
        }

    } elseif ($user_type === 'udruga') {

        $stmt = $pdo->prepare(
            "SELECT * FROM udruge WHERE email = :email"
        );
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['lozinka'])) {

            $_SESSION['user_id'] = $user['udruga_id'];
            $_SESSION['user_type'] = 'udruga';
            $_SESSION['user_name'] = $user['naziv_udruge'];
            $_SESSION['user_oib'] = $user['oib_udruge'];

            header('Location: ../pages/Helpout_main.php');
            exit;
        }
    }

    die('Invalid login credentials.');

} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}