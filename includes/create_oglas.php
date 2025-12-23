<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/create_oglas.html');
    exit;
}

require_once 'datebase_request.php';

/* 1 UZIMANJE PODATAKA */
$organisation_oib = $_SESSION['user_oib'];
$naziv_aktivnosti = $_POST['naziv_aktivnosti'] ?? '';
$opis             = $_POST['opis'] ?? '';
$grad             = $_POST['grad'] ?? '';
$adresa           = $_POST['adresa'] ?? '';
$datum            = $_POST['datum'] ?? '';
$vrijeme          = $_POST['vrijeme'] ?? '';
$trajanje         = $_POST['trajanje'] ?? '';
$vrsta_aktivnost  = $_POST['vrsta_aktivnost'] ?? '';
$max_volontera    = $_POST['max_volontera'] ?? '';
$min_bodovi       = $_POST['minimalni_bodovi'] ?? '';
/* Provjera da je sve uneseno osim minimalnih bodova  koji mogu biti nula*/
if (
    empty($organisation_oib) || empty($naziv_aktivnosti) || empty($grad) ||
    empty($adresa) || empty($datum) || empty($vrijeme) || empty($trajanje) || 
    empty($vrsta_aktivnost) || empty($max_volontera) 
) {
    die('All fields are required.');
}
elseif($datum < date('Y-m-d')){
    die('Date must be today or in the future.');
}

try {
    /*  NAĐI UDRUGU PO OIB-u */
    $stmt = $pdo->prepare(
        "SELECT udruga_id FROM Udruge WHERE oib_udruge = :oib"
    );
    $stmt->bindParam(':oib', $organisation_oib);
    $stmt->execute();

    $udruga_id_iz_requesta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$udruga_id_iz_requesta) {
        die('Organisation with this OIB does not exist.');
    }
   
    // Prvo izračunaj osnovne bodove
    $osnovni_bodovi = round($trajanje / 10) + 3;
    
    // Dodaj bonus bodove ovisno o vrsti aktivnosti
    $bonus_bodovi = ($vrsta_aktivnost == 'Edukacija' || $vrsta_aktivnost == 'Humanitarni rad') ? 3 : 1;
    
    // Ukupni bodovi
    $points_function_value = $osnovni_bodovi + $bonus_bodovi;
    echo $points_function_value;
    $udruga_id = $udruga_id_iz_requesta['udruga_id'];
    
    $query = "
        INSERT INTO Oglas (
            udruga_id,
            naziv_aktivnosti,
            opis,
            grad,
            adresa,
            datum,
            vrijeme,
            trajanje_minute,
            vrsta_aktivnosti,
            max_volontera,
            min_potrebni_bodovi,
            osvojeni_bodovi,
            status,
            kreirano_at
        ) VALUES (
            :udruga_id,
            :naziv_aktivnosti,
            :opis,
            :grad,
            :adresa,
            :datum,
            :vrijeme,
            :trajanje,
            :vrsta_aktivnosti,
            :max_volontera,
            :min_bodovi,
            :osvojeni_bodovi,
            'aktivan',
            NOW()
        )
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':udruga_id'        => $udruga_id,
        ':naziv_aktivnosti' => $naziv_aktivnosti,
        ':opis'             => $opis, 
        ':grad'             => $grad,
        ':adresa'           => $adresa,
        ':datum'            => $datum,
        ':vrijeme'          => $vrijeme,
        ':trajanje'         => $trajanje,
        ':vrsta_aktivnosti' => $vrsta_aktivnost,
        ':max_volontera'    => $max_volontera,
        ':min_bodovi'       => empty($min_bodovi) ? 0 : $min_bodovi,
        ':osvojeni_bodovi'  => $points_function_value
    ]);

    header('Location: ../pages/Helpout_main.php');
    exit;

} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
