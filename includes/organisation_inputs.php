<?php 
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $organisation_name = $_POST['organisation_name'];
    $Oib = $_POST['Oib'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        
        require_once 'datebase_request.php';
        $querry = "INSERT INTO udruge (naziv_udruge, oib_udruge, email, lozinka) VALUES (:organisation_name, :Oib, :email, :lozinka);";

        $stmt = $pdo->prepare($querry);
        $stmt->bindParam(':organisation_name', $organisation_name);
        $stmt->bindParam(':Oib', $Oib);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':lozinka', $hashedPassword);
        $stmt->execute();
        
        $pdo = null; // Close the database connection
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
