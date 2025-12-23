<?php 
session_start();
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $password = $_POST['password'];
    $volonter_id_session = $_SESSION['user_id'];
    require_once 'datebase_request.php';


    $stmt = $pdo->prepare("SELECT * FROM volonteri WHERE volonter_id = :volonter_id_session");
        $stmt->bindParam(':volonter_id_session', $_SESSION['user_id']);

        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

         if (password_verify($password, $user['volonter_lozinka'])) {
            try {
            $querry = "DELETE FROM volonter_oglas WHERE volonter_id = :volonter_id ";
            $stmt = $pdo->prepare($querry);
            $stmt->bindParam(':volonter_id', $user['volonter_id']);
            $stmt->execute();

            $querry = "DELETE FROM volonteri WHERE volonter_id = :volonter_id AND volonter_lozinka = :lozinka";
            

            $stmt = $pdo->prepare($querry);
            $stmt->bindParam(':volonter_id', $user['volonter_id']);
            $stmt->bindParam(':lozinka', $user['volonter_lozinka']);
            $stmt->execute();

            $pdo = null; // Close the database connection mozda treba prominiti
            $stmt = null; // Close the statement
            
            session_destroy();

            header("Location: ../pages/Helpout_main.php");

            die("User registered successfully.");
            } catch (PDOException $e) {
                die("Queery fail: " . $e->getMessage());
            }

            } 
            else {
                    die("Incorrect password.");
        }
    }
else {
    echo "No data received.";
    header("Location: ../pages/learn_more.html");
}