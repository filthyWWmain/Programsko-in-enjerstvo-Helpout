<?php 
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    try {

        require_once 'datebase_request.php';
        $querry = "INSERT INTO volonteri (volonter_ime, volonter_prezime, volonter_email, volonter_lozinka) VALUES (:first_name, :last_name, :email, :lozinka);";

        $stmt = $pdo->prepare($querry);
        $stmt->bindParam(':first_name', $firstName);
        $stmt->bindParam(':last_name', $lastName);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':lozinka', $hashedPassword);
        $stmt->execute();
        
        $pdo = null; // Close the database connection mozda treba prominiti
        $stmt = null; // Close the statement
        
        header("Location: ../pages/Helpout_main.php");

        die("User registered successfully.");
        } catch (PDOException $e) {
            die("Queery fail: " . $e->getMessage());
        }
    }
else {
    echo "No data received.";
    header("Location: ../pages/Helpout_main.php");
}