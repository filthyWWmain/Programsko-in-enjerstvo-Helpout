<?php
session_start();
require_once '../includes/datebase_request.php';

if (!isset($_GET['id'])) {
    die('Invalid listing ID.');
}

$oglas_id = (int) $_GET['id'];

$query = "
    SELECT o.*, u.naziv_udruge, u.email
    FROM oglas o
    JOIN udruge u ON o.udruga_id = u.udruga_id
    WHERE o.oglas_id = :id
";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':id', $oglas_id, PDO::PARAM_INT);
$stmt->execute();

$oglas = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$oglas) {
    die('Listing not found.');
}
$stmt = $pdo->prepare("
    SELECT 
        o.max_volontera,
        COUNT(vo.volonter_id) AS broj_prijavljenih
    FROM oglas o
    LEFT JOIN volonter_oglas vo ON o.oglas_id = vo.oglas_id
    WHERE o.oglas_id = :oglas_id
    GROUP BY o.max_volontera
");
$stmt->execute([
    ':oglas_id' => $oglas_id
]);

$kapacitet = $stmt->fetch(PDO::FETCH_ASSOC);

$max_volontera = (int)$kapacitet['max_volontera'];
$broj_prijavljenih = (int)$kapacitet['broj_prijavljenih'];
$slobodna_mjesta = max(0, $max_volontera - $broj_prijavljenih);


// PRIJAVA VOLONTERA NA OGLAS
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['prijavi_se']) &&
    isset($_SESSION['user_type']) &&
    $_SESSION['user_type'] === 'volonter'
) {
    $volonter_id = $_SESSION['user_id'];

    // provjeri je li već prijavljen
    $check = $pdo->prepare("
        SELECT 1 FROM volonter_oglas
        WHERE volonter_id = :volonter_id AND oglas_id = :oglas_id
    ");
    $check->execute([
        ':volonter_id' => $volonter_id,
        ':oglas_id' => $oglas_id
    ]);

    if ($check->fetch()) {
        $poruka = "You are already registered for this listing.";
    } else {
        // prijava
        /* Dohvati max_volontera i trenutni broj prijavljenih */
            $stmt = $pdo->prepare("
                SELECT 
                    o.max_volontera,
                    COUNT(vo.volonter_id) AS broj_prijavljenih
                FROM oglas o
                LEFT JOIN Volonter_Oglas vo ON o.oglas_id = vo.oglas_id
                WHERE o.oglas_id = :oglas_id
                GROUP BY o.max_volontera
            ");
            $stmt->execute([
                ':oglas_id' => $oglas_id
            ]);
            $podaci = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = $pdo->prepare("
            SELECT 
                v.volonter_bodovi,
                o.min_potrebni_bodovi
            FROM volonteri v
            JOIN oglas o ON o.oglas_id = :oglas_id
            WHERE v.volonter_id = :volonter_id
            ");

            $stmt->execute([
                ':volonter_id' => $volonter_id,
                ':oglas_id' => $oglas_id
            ]);

            $podaci_2 = $stmt->fetch(PDO::FETCH_ASSOC);

            $volonter_bodovi = (int)$podaci_2['volonter_bodovi'];
            $min_potrebni_bodovi = (int)$podaci_2['min_potrebni_bodovi'];

            if (!$podaci) {
                die('Oglas ne postoji.');
            }
            elseif ($podaci['broj_prijavljenih'] >= $podaci['max_volontera']) { 
                $poruka = "Unfortunately, this listing has reached its maximum number of volunteers.";
            }
            elseif ($volonter_bodovi < $min_potrebni_bodovi){
                $poruka = "You do not have enough points to apply for this listing.";
            }
            else{
                $insert = $pdo->prepare("
                INSERT INTO volonter_oglas (volonter_id, oglas_id, odradio)
                VALUES (:volonter_id, :oglas_id, 0)
                ");
                $insert->execute([
                    ':volonter_id' => $volonter_id,
                    ':oglas_id' => $oglas_id
                ]);
                $poruka = "You have successfully registered for the listing!";
            }

        
    }
}

// ODJAVA VOLONTERA S OGLASA
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['odjavi_se_s_oglasa']) &&
    isset($_SESSION['user_type']) &&
    $_SESSION['user_type'] === 'volonter'
) {
    $volonter_id = $_SESSION['user_id'];

    // provjeri je li već prijavljen
    $check = $pdo->prepare("
        SELECT 1 FROM volonter_oglas
        WHERE volonter_id = :volonter_id AND oglas_id = :oglas_id
    ");
    $check->execute([
        ':volonter_id' => $volonter_id,
        ':oglas_id' => $oglas_id
    ]);

    if ($check->fetch()) {
        // ako je onda odjava

        $querry_odjava = "DELETE FROM volonter_oglas WHERE volonter_id = :volonter_id AND oglas_id = :oglas_id";
            $stmt = $pdo->prepare($querry_odjava);
            $stmt->bindParam(':volonter_id', $volonter_id);
            $stmt->bindParam(':oglas_id', $oglas_id);
            $stmt->execute();

        $poruka = "You have successfully unregistered from the listing.";
    } else {
        $poruka = "You are not registered for this listing!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($oglas['naziv_aktivnosti']) ?></title>
    <link rel="stylesheet" href="../styles/normalize.css">
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="./oglas_detalji.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">HELPOUT</div>
            <nav>
                <ul>
                    <li><a href="Helpout_main.php">Home</a></li>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <li><a href="../pages/register_option.html">Register</a></li>
                        <li><a href="../pages/login.html">Log in</a></li>
                    <?php elseif ($_SESSION['user_type'] === 'udruga'): ?>
                        <li><a href="../pages/create_oglas.html">Create listing</a></li>
                        <li><a href="../pages/account_udruga.php">My account</a></li>
                        <li><a href="../includes/log_out.php">Log out</a></li>
                    <?php elseif ($_SESSION['user_type'] === 'volonter'): ?>
                        <li><a href="../includes/log_out.php">Log out</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>
        <hr>

        <div class="details-card">
            <div class="container">

                <h1><?= htmlspecialchars($oglas['naziv_aktivnosti']) ?></h1>
                
                <!-- PROMJENA OVDJE - GRID LAYOUT UMJESTO LISTE -->
                <div class="details-grid">
                    <div class="grid-item">
                        <div class="icon">🏢</div>
                        <div class="grid-content">
                            <strong class="label">Organisation:</strong>
                            <span class="value"><?= htmlspecialchars($oglas['naziv_udruge']) ?></span>
                        </div>
                    </div>
                    
                    <div class="grid-item">
                        <div class="icon">📍</div>
                        <div class="grid-content">
                            <strong class="label">City:</strong>
                            <span class="value"><?= htmlspecialchars($oglas['grad']) ?></span>
                        </div>
                    </div>
                    
                    <div class="grid-item">
                        <div class="icon">🏠</div>
                        <div class="grid-content">
                            <strong class="label">Address:</strong>
                            <span class="value"><?= htmlspecialchars($oglas['adresa']) ?></span>
                        </div>
                    </div>
                    
                    <div class="grid-item">
                        <div class="icon">📅</div>
                        <div class="grid-content">
                            <strong class="label">Date & Time:</strong>
                            <span class="value"><?= $oglas['datum'] ?> at <?= $oglas['vrijeme'] ?></span>
                        </div>
                    </div>
                    
                    <div class="grid-item">
                        <div class="icon">⏱️</div>
                        <div class="grid-content">
                            <strong class="label">Duration:</strong>
                            <span class="value"><?= $oglas['trajanje_minute'] ?> minutes</span>
                        </div>
                    </div>
                    
                    <div class="grid-item">
                        <div class="icon">🎯</div>
                        <div class="grid-content">
                            <strong class="label">Activity type:</strong>
                            <span class="value"><?= htmlspecialchars($oglas['vrsta_aktivnosti']) ?></span>
                        </div>
                    </div>
                    
                    <div class="grid-item">
                        <div class="icon">⭐</div>
                        <div class="grid-content">
                            <strong class="label">Required points:</strong>
                            <span class="value"><?= $oglas['min_potrebni_bodovi'] ?></span>
                        </div>
                    </div>
                    
                    <div class="grid-item">
                        <div class="icon">🏆</div>
                        <div class="grid-content">
                            <strong class="label">Earned points:</strong>
                            <span class="value"><?= $oglas['osvojeni_bodovi'] ?></span>
                        </div>
                    </div>
                    
                    <div class="grid-item grid-item-full">
                        <div class="icon">👥</div>
                        <div class="grid-content">
                            <strong class="label">Available spots:</strong>
                            <span class="value highlight"><?= $slobodna_mjesta ?> / <?= $max_volontera ?></span>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="description-box">
                    <h3>Description</h3>
                    <p id="opis"><?= nl2br(htmlspecialchars($oglas['opis'])) ?></p>
                </div>
                <hr>

                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'volonter'): ?>
                    <?php if (isset($poruka)): ?> 
                        <div class="message <?= strpos($poruka, 'successfully') !== false ? 'success' : 'error' ?>">
                            <strong><?= htmlspecialchars($poruka) ?></strong> 
                        </div> 
                    <?php endif; ?>

                    <form method="post" class="action-buttons">
                        <button type="submit" name="prijavi_se" class="btn btn-primary" <?= $slobodna_mjesta === 0 ? 'disabled' : '' ?>>    
                            Apply for listing
                        </button>
                        <button type="submit" name="odjavi_se_s_oglasa" class="btn btn-secondary">
                            Unregister from listing
                        </button>
                    </form>

                <?php elseif (!isset($_SESSION['user_id'])): ?>
                    <div class="login-warning">
                        <strong>You need to be logged in to apply for a listing.</strong>
                    </div>
                <?php endif; ?>

                <div class="button-group">    
                    <a href="Helpout_main.php" class="btn btn-back">← Back to Home</a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="../pages/login.html" class="btn btn-login">Log in</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
