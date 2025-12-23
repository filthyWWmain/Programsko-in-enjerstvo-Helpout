<?php
session_start();
require_once '../includes/datebase_request.php';

/* SAMO UDRUGA */
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'udruga') {
    die('Access denied.');
}

/* PROVJERA ID-a */
if (!isset($_GET['id'])) {
    die('Invalid listing ID.');
}

$oglas_id = (int) $_GET['id'];
$udruga_id = $_SESSION['user_id'];

/* PROVJERA DA OGLAS PRIPADA TOJ UDRUZI */
$stmt = $pdo->prepare("
    SELECT naziv_aktivnosti, status 
    FROM oglas 
    WHERE oglas_id = :oglas_id AND udruga_id = :udruga_id
");
$stmt->execute([
    ':oglas_id' => $oglas_id,
    ':udruga_id' => $udruga_id
]);

$oglas = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$oglas) {
    die('Access denied to this listing.');
}

$oglas_status = $oglas['status'] ?? null;

/* MAKNUTI VOLONTERA S OGLASA */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_volonter'])) {
    $volonter_id = (int)$_POST['volonter_id'];

    // Provjera je li volonter već odradio
    $stmt = $pdo->prepare("
        SELECT vo.odradio, o.status
        FROM Volonter_Oglas vo
        JOIN oglas o ON vo.oglas_id = o.oglas_id
        WHERE vo.volonter_id = :volonter_id AND vo.oglas_id = :oglas_id
    ");
    $stmt->execute([
        ':volonter_id' => $volonter_id,
        ':oglas_id' => $oglas_id
    ]);
    
    $check = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($check && $check['odradio'] == 1 && $check['status'] === 'istekao') {
        $_SESSION['error'] = 'Cannot remove volunteer who already completed the activity and received points.';
        header("Location: detalji_o_volonterima.php?id=$oglas_id");
        exit;
    }

    // Dozvoljeno brisanje
    $stmt = $pdo->prepare("
        DELETE FROM Volonter_Oglas
        WHERE volonter_id = :volonter_id AND oglas_id = :oglas_id
    ");
    $stmt->execute([
        ':volonter_id' => $volonter_id,
        ':oglas_id' => $oglas_id
    ]);

    $_SESSION['success'] = 'Volunteer removed successfully.';
    header("Location: detalji_o_volonterima.php?id=$oglas_id");
    exit;
}

/* OZNAČITI JE LI ODRADIO */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['odradio'])) {
    $volonter_id = (int)$_POST['volonter_id'];
    $odradio = (int)$_POST['odradio'];
    
    $stmt = $pdo->prepare("
        UPDATE Volonter_oglas
        SET odradio = :odradio
        WHERE volonter_id = :volonter_id AND oglas_id = :oglas_id
    ");
    $stmt->execute([
        ':odradio' => $odradio,
        ':volonter_id' => $volonter_id,
        ':oglas_id' => $oglas_id
    ]);
    
    $_SESSION['success'] = $odradio == 1 ? 'Volunteer marked as completed.' : 'Volunteer marked as not completed.';
    header("Location: detalji_o_volonterima.php?id=$oglas_id");
    exit;
}

/* ZAVRŠI OGLAS I DODIJELI BODOVE */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['zavrsi_oglas'])) {
    // Dohvati datum i bodove oglasa
    $stmt = $pdo->prepare("
        SELECT datum, osvojeni_bodovi, status
        FROM oglas
        WHERE oglas_id = :id
    ");
    $stmt->execute([':id' => $oglas_id]);
    $oglasData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$oglasData) {
        die('Listing not found.');
    }
    
    // Provjera statusa
    if ($oglasData['status'] === 'istekao') {
        $_SESSION['error'] = 'Points have already been assigned.';
        header("Location: detalji_o_volonterima.php?id=$oglas_id");
        exit;
    }
    
    $today = new DateTime();
    $listingDate = new DateTime($oglasData['datum']);
    
    if ($today < $listingDate) {
        $_SESSION['error'] = 'You cannot assign points before the activity date.';
        header("Location: detalji_o_volonterima.php?id=$oglas_id");
        exit;
    }
    
    // Dodjela bodova
    $stmt = $pdo->prepare("
        UPDATE Volonteri v
        JOIN Volonter_Oglas vo ON v.volonter_id = vo.volonter_id
        SET v.volonter_bodovi = v.volonter_bodovi + :bodovi
        WHERE vo.oglas_id = :oglas_id AND vo.odradio = 1
    ");
    $stmt->execute([
        ':bodovi' => (int)$oglasData['osvojeni_bodovi'],
        ':oglas_id' => $oglas_id
    ]);
    
    // Oglas završen
    $stmt = $pdo->prepare("
        UPDATE oglas SET status = 'istekao' WHERE oglas_id = :id
    ");
    $stmt->execute([':id' => $oglas_id]);
    
    $_SESSION['success'] = 'Activity completed! Points have been assigned to volunteers.';
    header("Location: account_udruga.php");
    exit;
}

/* DOHVAT VOLONTERA */
$stmt = $pdo->prepare("
    SELECT 
        v.volonter_id,
        v.volonter_ime,
        v.volonter_prezime,
        v.volonter_email,
        vo.odradio,
        vo.prijava_at
    FROM Volonter_Oglas vo
    JOIN Volonteri v ON vo.volonter_id = v.volonter_id
    WHERE vo.oglas_id = :oglas_id
    ORDER BY vo.prijava_at DESC
");

$stmt->bindParam(':oglas_id', $oglas_id, PDO::PARAM_INT);
$stmt->execute();

$volonteri = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Volunteers - HELPOUT</title>
    <link rel="stylesheet" href="../styles/normalize.css">
    <link rel="stylesheet" href="../styles/detalji_o_volonterima.css">
</head>
<body>

<div class="container">

<header>
    <div class="logo">HELPOUT</div>
    <nav>
        <ul>
            <li><a href="account_udruga.php">← Back</a></li>
            <li><a href="../includes/log_out.php">Log out</a></li>
        </ul>
    </nav>
</header>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div style="background:#d4edda; color:#155724; padding:15px; border-radius:10px; margin-bottom:20px;">
        <?= htmlspecialchars($_SESSION['success']) ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:10px; margin-bottom:20px;">
        <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<h1>Volunteers for: <?= htmlspecialchars($oglas['naziv_aktivnosti']) ?></h1>

<?php if (empty($volonteri)): ?>
    <div class="empty-message">
        No volunteers have applied yet.
    </div>
<?php else: ?>
    <ul class="volunteer-list">
        <?php foreach ($volonteri as $v): ?>
            <li class="volunteer-item">
                <div class="volunteer-header">
                    <div class="volunteer-name">
                        <?= htmlspecialchars($v['volonter_ime'] . ' ' . $v['volonter_prezime']) ?>
                        <?php if ($v['odradio'] == 1): ?>
                            <span class="status done">✔ Completed</span>
                        <?php else: ?>
                            <span class="status not-done">✖ Not Completed</span>
                        <?php endif; ?>
                    </div>
                    <div class="volunteer-email">
                        <?= htmlspecialchars($v['volonter_email']) ?>
                    </div>
                </div>
                
                <div class="btn-group">
                    <?php if ($v['odradio'] == 0): ?>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="volonter_id" value="<?= $v['volonter_id'] ?>">
                            <button type="submit" name="odradio" value="1" class="btn btn-success">
                                ✔ Mark as Completed
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <form method="post" class="inline-form">
                        <input type="hidden" name="volonter_id" value="<?= $v['volonter_id'] ?>">
                        <button type="submit" name="odradio" value="0" class="btn btn-warning">
                            ✖ Mark as Not Completed
                        </button>
                    </form>
                    
                    <form method="post" class="inline-form" 
                          onsubmit="return confirm('Are you sure you want to remove this volunteer?');">
                        <input type="hidden" name="volonter_id" value="<?= $v['volonter_id'] ?>">
                        <button type="submit" name="remove_volonter" class="btn btn-danger">
                            Remove Volunteer
                        </button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($oglas_status !== 'istekao' && !empty($volonteri)): ?>
    <form method="post" onsubmit="return confirm('Are you sure you want to finish this activity and assign points?');">
        <button type="submit" name="zavrsi_oglas" class="btn btn-complete">
            ✅ Complete Activity – Assign Points to Volunteers
        </button>
    </form>
<?php elseif ($oglas_status === 'istekao'): ?>
    <div class="status-message">
        <p>This activity has been completed and points have been assigned.</p>
    </div>
<?php endif; ?>

</div>

</body>
</html>