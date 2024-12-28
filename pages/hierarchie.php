<?php

require_once '../routes/database.php';
$database = Database::getInstance();

session_start();

$isLoggedIn = isset($_SESSION['user']);

$drinks = $database->get_recettes();

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style/style.css">
    <title>Crazy Potions</title>
</head>

<body>
    <div class="main">
        <div class="navigation">
            <p class="path">Chemin actuel : <strong><?= htmlspecialchars(implode(' > ', $currentPath)) ?></strong></p>
        </div>
        <div id="results-container">
        </div>
        </div>
    </div>
    <div id="results-container">
        <?php foreach ($drinks as $drink): ?>
            <div class="result-item">
                <h3><?= htmlspecialchars($drink['name']) ?></h3>
                <?php
                    $imageName = str_replace(' ', '_', $drink['name']) . '.jpg';
                    $imagePath = "../ressources/images/" . $imageName;
                    $genericImagePath = "../ressources/images/generic.jpg";
                ?>
                <div class="recipe-image">
                    <img src="<?= file_exists($imagePath) ? htmlspecialchars($imagePath) : htmlspecialchars($genericImagePath) ?>" alt="Image de <?= htmlspecialchars($drink['name']) ?>" style="max-width: 150px; height: auto;">
                </div
                <?php if (!empty($drink['description'])): ?>
                    <p>
                        <span class="recipe-label"><strong>Description :</strong></span>
                        <?= nl2br(htmlspecialchars($drink['description'])) ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($drink['ingredients'])): ?>
                    <div class="recipe-ingredients">
                        <span class="recipe-label"><strong>Ingrédients :</strong></span>
                        <ul>
                            <?php foreach (preg_split("/\|/", $drink['ingredients']) as $ingredient): ?>
                                <li><?= htmlspecialchars(trim($ingredient)) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
</body>

</html>