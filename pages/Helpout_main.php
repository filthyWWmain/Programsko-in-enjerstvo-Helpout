<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Helpout_main</title>

    <link rel="stylesheet" href="../styles/normalize.css" />
    <link rel="stylesheet" href="../styles/main.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body>
<div class="container">

<header>
    <div class="logo">HELPOUT</div>

    <nav>
        <ul>
            <li><a class="active" href="Helpout_main.php">Home</a></li>

            <?php if (!isset($_SESSION['user_id'])): ?>
                <!-- NIKO NI ULOGIRAN -->
                <li><a href="../pages/register_option.html">Register</a></li>
                <li><a href="../pages/login.html">Log in</a></li>

            <?php elseif ($_SESSION['user_type'] === 'udruga'): ?>
                <!-- UDRUGA -->
                <li><a href="../pages/account_udruga.php">My account</a></li>
                <li><a href="../pages/create_oglas.html">Create listing</a></li>
                <li><a href="../includes/log_out.php">Log out</a></li>

            <?php elseif ($_SESSION['user_type'] === 'volonter'): ?>
                <!-- VOLONTER -->
                <li><a href="../pages/account_volonter.php">My account</a></li>
                <li><a href="../includes/log_out.php">Log out</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="search-box">
        <form method="get" action="Helpout_main.php">
            <input type="text" name="grad" placeholder="Search by city">
            <button type="submit">🔍</button>
        </form>
    </div>
</header>


<section class="hero">
    <img src="../images/Main_image.png" alt="volunteering">
    <div class="hero-text">
        <h1>Help others, doing what you love</h1>
        <p>Find a perfect volunteering job for yourself to gain experience and connections.</p>
        <a href="./learn_more.html" class="btn">Learn more →</a>
    </div>
</section>
<!-- DINAMIČKI OGLASI IZ BAZE -->
<?php
require_once '../includes/datebase_request.php';

$grad = $_GET['grad'] ?? '';

if (!empty($grad)) {
    $query = "SELECT * FROM Oglas 
              WHERE status = 'aktivan'
              AND datum >= CURDATE()
              AND grad LIKE :grad
              ORDER BY datum ASC";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':grad', '%' . $grad . '%');
} else {
    $query = "SELECT * FROM Oglas 
              WHERE status = 'aktivan'
              AND datum >= CURDATE()
              ORDER BY datum ASC";

    $stmt = $pdo->prepare($query);
}

$stmt->execute();
$oglasi = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<section class="cards">

<?php
$images = [
    '../images/volunteering1.png',
    '../images/volunteering2.png',
    '../images/volunteering3.png',
    '../images/volunteering4.png',
    '../images/volunteering5.png',
    '../images/volunteering6.png',
    '../images/volunteering7.png'
];
?>

<?php foreach ($oglasi as $oglas): ?>
    <a href="./oglas_detalji.php?id=<?= $oglas['oglas_id'] ?>" class="card-link">
        <div class="card">
            <img src="<?= $images[array_rand($images)] ?>" alt="volunteering">
            <div class="card-content">
                <h3><?= htmlspecialchars($oglas['naziv_aktivnosti']) ?></h3>
                <p><strong><?= htmlspecialchars($oglas['grad']) ?></strong> | <?= $oglas['datum'] ?></p>
                <p><?= htmlspecialchars($oglas['opis']) ?></p>
            </div>
        </div>
    </a>
<?php endforeach; ?>
</section>

</div>
</body>
</html>
