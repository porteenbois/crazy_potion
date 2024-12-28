<?php

session_start();

$isLoggedIn = isset($_SESSION['user']);

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
</body>

</html>