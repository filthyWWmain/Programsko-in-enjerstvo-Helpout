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

// Dobavi podatke o kapacitetu
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
    <title><?= htmlspecialchars($oglas['naziv_aktivnosti']) ?> - HELPOUT</title>
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
                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'udruga'): ?>
                        <li><a href="../pages/create_oglas.html">Create listing</a></li>
                        <li><a href="../pages/account_udruga.php">My account</a></li>
                        <li><a href="../includes/log_out.php">Log out</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>
        <hr>

        <div class="details-card">
            <!-- Action buttons on top -->
            <div class="action-header">
                <div class="action-buttons">
                    <!-- DELETE OGLAS -->
                    <form method="post" class="delete-form" 
                          onsubmit="return confirm('Are you sure you want to delete this listing? This action cannot be undone.');">
                        <button type="submit" name="obrisi_oglas" class="btn btn-delete">
                            <span class="btn-icon">🗑️</span>
                            Delete Listing
                        </button>
                    </form>

                    <!-- VIEW VOLONTERE -->
                    <a href="detalji_o_volonterima.php?id=<?= $oglas['oglas_id'] ?>" class="btn btn-view-volunteers">
                        <span class="btn-icon">👥</span>
                        View Volunteers (<?= $broj_prijavljenih ?>)
                    </a>
                    
                    <!-- STATUS BADGE -->
                    <div class="status-badge">
                        Status: 
                        <span class="status-value <?= $oglas['status'] ?>">
                            <?= htmlspecialchars($oglas['status']) ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <hr class="action-divider">

            <h1 class="listing-title"><?= htmlspecialchars($oglas['naziv_aktivnosti']) ?></h1>
            
            <!-- GRID LAYOUT - 2 STUPCA -->
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
                
                <div class="grid-item">
                    <div class="icon">👥</div>
                    <div class="grid-content">
                        <strong class="label">Capacity:</strong>
                        <span class="value"><?= $max_volontera ?> volunteers</span>
                    </div>
                </div>
                
                <div class="grid-item grid-item-highlight">
                    <div class="icon">✅</div>
                    <div class="grid-content">
                        <strong class="label">Registered volunteers:</strong>
                        <span class="value highlight"><?= $broj_prijavljenih ?> / <?= $max_volontera ?></span>
                    </div>
                </div>
                
                <div class="grid-item">
                    <div class="icon">📧</div>
                    <div class="grid-content">
                        <strong class="label">Contact email:</strong>
                        <span class="value email"><?= htmlspecialchars($oglas['email']) ?></span>
                    </div>
                </div>
                
                <div class="grid-item">
                    <div class="icon">📅</div>
                    <div class="grid-content">
                        <strong class="label">Created at:</strong>
                        <span class="value"><?= date('F j, Y', strtotime($oglas['kreirano_at'])) ?></span>
                    </div>
                </div>
            </div>

            <hr>

            <!-- Description Section -->
            <div class="description-box">
                <h3>Activity Description</h3>
                <p class="description-text"><?= nl2br(htmlspecialchars($oglas['opis'])) ?></p>
            </div>
            
            <hr>

            <!-- Bottom buttons -->
            <div class="button-group">    
                <a href="account_udruga.php" class="btn btn-back">
                    <span class="btn-icon">←</span>
                    Back to My Listings
                </a>
            </div>
        </div>
    </div>
</body>
</html>