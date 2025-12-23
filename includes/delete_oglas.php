<?php 
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $oib = $_POST['oib'];
    $naziv_aktivnosti = $_POST['naziv_aktivnosti'];
    $grad = $_POST['grad'];

    require_once 'datebase_request.php';
    
    $stmt = $pdo->prepare(
        "SELECT udruga_id FROM Udruge WHERE oib_udruge = :oib"
    );
    $stmt->bindParam(':oib', $oib);
    $stmt->execute();

    $udruga_iz_requesta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$udruga_iz_requesta) {
        die('Organisation with this OIB does not exist.');
    }

    $udruga_id = $udruga_iz_requesta['udruga_id'];

    try {

        $querry = "DELETE FROM oglas WHERE udruga_id = :udruga_id AND naziv_aktivnosti = :naziv_aktivnosti AND grad = :grad";
        $stmt = $pdo->prepare($querry);
        $stmt->bindParam(':udruga_id', $udruga_id);
        $stmt->bindParam(':naziv_aktivnosti', $naziv_aktivnosti);
        $stmt->bindParam(':grad', $grad);
        $stmt->execute();
        
        $pdo = null; // Close the database connection mozda treba prominiti
        $stmt = null; // Close the statement
        
        header("Location: ../pages/account_udruga.php");

        die("User registered successfully.");
        } catch (PDOException $e) {
            die("Queery fail: " . $e->getMessage());
        }
    }
else {
    echo "No data received.";
    header("Location: ../pages/learn_more.html");
}