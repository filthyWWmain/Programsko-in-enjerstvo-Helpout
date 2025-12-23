<?php 
session_start();
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $password = $_POST['password'];
    $udruga_id_session = $_SESSION['user_id'];
    require_once 'datebase_request.php';


    $stmt = $pdo->prepare("SELECT * FROM udruge WHERE udruga_id = :udruga_id_session");
    $stmt->bindParam(':udruga_id_session', $_SESSION['user_id']);

    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($password, $user['lozinka'])) {
        try {
            //Dohvaćanje svih oglasa te udruge 
            $stmt = $pdo->prepare("SELECT * FROM oglas WHERE udruga_id = :udruga_id");
            $stmt->bindParam(':udruga_id', $user['udruga_id']);
            $stmt->execute();
            $oglasi = $stmt->fetchAll(PDO::FETCH_ASSOC);

            //Brisanje oglasa iz tablice volonter_oglas za te oglase
            $querry = "DELETE FROM volonter_oglas WHERE oglas_id = :oglas_id ";
            $stmt = $pdo->prepare($querry);
            foreach ($oglasi as $oglas) {
                $stmt->bindParam(':oglas_id', $oglas['oglas_id']);
                $stmt->execute();
            }

            //Brisanje oglasa iz tablice oglasi od te udruge
            $querry = "DELETE FROM oglas WHERE udruga_id = :udruga_id ";
            $stmt = $pdo->prepare($querry);
            foreach ($oglasi as $oglas) {
                $stmt->bindParam(':udruga_id', $user['udruga_id']);
                $stmt->execute();
            }

            //Brisanje same udruge iz tablice udruge
            $querry = "DELETE FROM udruge WHERE udruga_id = :udruga_id";
            $stmt = $pdo->prepare($querry);
            $stmt->bindParam(':udruga_id', $user['udruga_id']);
            $stmt->execute();

            $pdo = null; // Close the database connection mozda treba prominiti
            $stmt = null; // Close the statement
            
            session_destroy();

            header("Location: ../pages/Helpout_main.php");

            die("User deleted successfully.");
        } catch (PDOException $e) {
            die("Query fail: " . $e->getMessage());
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