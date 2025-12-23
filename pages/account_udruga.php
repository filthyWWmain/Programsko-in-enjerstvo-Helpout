<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'udruga') {
    die('Access denied.');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Udruga Account</title>

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
            <li><a href="Helpout_main.php">Home</a></li>
            <li><a href="../pages/create_oglas.html">Create listing</a></li>
            <li><a href="../includes/log_out.php">Log out</a></li>
            <li><a href="../pages/delete_udruga.html">Delete account</a></li> 
        </ul>
    </nav>

    <div class="search-box">
        <form method="get" action="Helpout_main.php">
            <input type="text" name="grad" placeholder="Search by city">
            <button type="submit">🔍</button>
        </form>
    </div>
</header>
<div id="Title">My previous listings</div>
<!-- DINAMIČKI OGLASI IZ BAZE -->
<?php
require_once '../includes/datebase_request.php';

$udruga_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM oglas WHERE udruga_id = :udruga_id_session");
    $stmt->bindParam(':udruga_id_session', $_SESSION['user_id']);

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
    <a href="./oglas_detalji_udruga.php?id=<?= $oglas['oglas_id'] ?>" class="card-link">
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
