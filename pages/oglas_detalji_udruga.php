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

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['obrisi_oglas']) &&
    isset($_SESSION['user_type']) &&
    $_SESSION['user_type'] === 'udruga' &&
    $_SESSION['user_id'] == $oglas['udruga_id']
) {
    try {
        $query = "DELETE FROM oglas 
                  WHERE oglas_id = :oglas_id 
                  AND udruga_id = :udruga_id";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':oglas_id', $oglas_id, PDO::PARAM_INT);
        $stmt->bindParam(':udruga_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();

        header("Location: ../pages/account_udruga.php");
        exit;

    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
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
        <!-- kopia je na svin stranica ista jer mi se ni dalo raditi zasebi file za sve headere...
        sad je prekasno za to-->
        <header>
            <div class="logo">HELPOUT</div>

            <nav>
                <ul>
                    <li><a href="Helpout_main.php">Home</a></li>
                    <?php if ($_SESSION['user_type'] === 'udruga'): ?>
                        <li><a href="../pages/create_oglas.html">Create listing</a></li>
                        <li><a href="../pages/account_udruga.php">My account</a></li>
                        <li><a href="../includes/log_out.php">Log out</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>
        <hr>

        <div class="details-card">
            <div class="container">
                <div class="button-group_last">
                    <!-- DELETE OGLAS -->
                    <form method="post" style="display:inline;">
                        <button type="submit" name="obrisi_oglas" class="btn">
                            Delete listing
                        </button>
                    </form>

                    <!-- VIEW VOLONTERE -->
                     <div class="view-volonteri-button">
                        <a href="detalji_o_volonterima.php?id=<?= $oglas['oglas_id'] ?>" class="btn">
                        View volunteers
                    </a>
                     </div>
                </div>
                <hr>
                <h1><?= htmlspecialchars($oglas['naziv_aktivnosti']) ?></h1>
                <ul>
                    <li><p><strong class="osta_san_bez_ideja_vise_za_nazive">Organisation:</strong> <?= htmlspecialchars($oglas['naziv_udruge']) ?></p></li>
                    <li><p><strong class="osta_san_bez_ideja_vise_za_nazive">city:</strong> <?= htmlspecialchars($oglas['grad']) ?></p></li>
                    <li><p><strong class="osta_san_bez_ideja_vise_za_nazive">Adress:</strong> <?= htmlspecialchars($oglas['adresa']) ?></p></li>
                    <li><p><strong class="osta_san_bez_ideja_vise_za_nazive">Date:</strong> <?= $oglas['datum'] ?> u <?= $oglas['vrijeme'] ?></p></li>
                    <li><p><strong class="osta_san_bez_ideja_vise_za_nazive">Duration:</strong> <?= $oglas['trajanje_minute'] ?> min</p></li>
                    <li><p><strong class="osta_san_bez_ideja_vise_za_nazive">Activity type:</strong> <?= htmlspecialchars($oglas['vrsta_aktivnosti']) ?></p></li>
                    <li><p><strong class="osta_san_bez_ideja_vise_za_nazive">Required points:</strong> <?= $oglas['min_potrebni_bodovi'] ?></p></li>
                    <li><p><strong class="osta_san_bez_ideja_vise_za_nazive">Earned points:</strong> <?= $oglas['osvojeni_bodovi'] ?></p></li>
                </ul>

                <hr>

                <p><?= nl2br(htmlspecialchars($oglas['opis'])) ?></p>
                <hr>
                <div class="button-group_last">    
                    <a href="account_udruga.php" class="btn">← Back</a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="../pages/login.html" class="btn" id="log_in_btn">Log in</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
