<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'volonter') {
    die('Access denied.');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Volunteer Account</title>

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
            <li><a href="../includes/log_out.php">Log out</a></li>
            <li><a href="../pages/delete_volonter.html">Delete account</a></li>
        </ul>
    </nav>

    <div class="search-box">
        <form method="get" action="Helpout_main.php">
            <input type="text" name="grad" placeholder="Search by city">
            <button type="submit">🔍</button>
        </form>
    </div>
</header>

<!-- DINAMIČKI OGLASI IZ BAZE -->
<?php
require_once '../includes/datebase_request.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'volonter') {
    die('Access denied.');
}

$volonter_id = $_SESSION['user_id'];

/* DOHVAT PODATAKA O VOLONTERU */
$stmt = $pdo->prepare("
    SELECT *
    FROM volonteri
    WHERE volonter_id = :volonter_id
");
$stmt->execute([
    ':volonter_id' => $volonter_id
]);

$volonter = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$volonter) {
    die('Volunteer not found.');
}

$query = "
    SELECT o.*
    FROM Oglas o
    JOIN Volonter_Oglas vo ON o.oglas_id = vo.oglas_id
    WHERE vo.volonter_id = :volonter_id
    ORDER BY o.datum ASC
";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':volonter_id', $volonter_id, PDO::PARAM_INT);
$stmt->execute();

$oglasi = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="profile-section">
    <h1>My Profile</h1>
    
    <ul class="volonter-detalji">
        <li>
            <strong>First name:</strong>
            <span><?= htmlspecialchars($volonter['volonter_ime']) ?></span>
        </li>
        
        <li>
            <strong>Last name:</strong>
            <span><?= htmlspecialchars($volonter['volonter_prezime']) ?></span>
        </li>
        
        <li>
            <strong>Email:</strong>
            <span class="email-display"><?= htmlspecialchars($volonter['volonter_email']) ?></span>
        </li>
        
        <li>
            <strong>Total points:</strong>
            <span class="points-display"><?= (int)$volonter['volonter_bodovi'] ?> pts</span>
        </li>
        
        <li>
            <strong>Registered at:</strong>
            <span class="date-display"><?= date('F j, Y', strtotime($volonter['kreirano_at'])) ?></span>
        </li>
    </ul>
</div>

<div id="Title">My volunteer experiences</div>
<hr>
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
</div>
</section>

</body>
</html>
