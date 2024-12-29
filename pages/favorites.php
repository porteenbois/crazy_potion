<?php
require_once '../routes/database.php';

$database = Database::getInstance();

session_start();

$isLoggedIn = isset($_SESSION['user']);

$userId = $_SESSION['user']['id'];

if($isLoggedIn){
    $favorites = $database->get_favorite_by_userId($userId);
}else{
    foreach($_SESSION["favoris"] as $recipeId){
        $favorites[] = $database->get_recette_by_Id($recipeId);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Recettes Favorites</title>
    <link rel="stylesheet" href="../style/style.css">
</head>

<body>
    <div class="header">
        <div class="left">
            <h1>Crazy Potions</h1>
        </div>

        <div class="center">
            <a href="hierarchie.php">
                <h3>Hiérarchie</h3>
            </a>
            <a href="../index.php">
                <h3>Recherche</h3>
            </a>
            <h3>Favoris</h3>
        </div>

        <div class="right">
            <?php if ($isLoggedIn): ?>
		        <p>Bonjour, <?php echo htmlspecialchars($_SESSION['user']['first_name']); ?> !</p>
		        <a href="modify_account.php">Modifier profil</>
                <a href="logout.php">Se déconnecter</a>
            <?php else: ?>
                <a href="login.php">Se connecter</a>
                <a href="signup.php">Créer un compte</a>
            <?php endif; ?>
        </div>
    </div>
	<div id="results-container">
    <h1>Mes Recettes Favorites</h1>

    <?php if (empty($favorites)): ?>
        <p>Vous n'avez aucune recette favorite.</p>
    <?php else: ?>
        <?php foreach ($favorites as $drink): ?>
            <div class="result-item">
                <h3><?= htmlspecialchars($drink['name']) ?></h3>
                <?php
                    $imageName = str_replace(' ', '_', $drink['name']) . '.jpg';
                    $imagePath = "../ressources/images/" . $imageName;
                    $genericImagePath = "../ressources/images/generic.jpg";
                ?>
                <div class="recipe-image">
                    <img src="<?= file_exists($imagePath) ? htmlspecialchars($imagePath) : htmlspecialchars($genericImagePath) ?>" alt="Image de <?= htmlspecialchars($drink['name']) ?>" style="max-width: 150px; height: auto;">
                </div>
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
                <button class="favoriteButton" 
                    userId="<?= htmlspecialchars($_SESSION['user']['id'] ?? '') ?>"
                    recipeId="<?= htmlspecialchars($drink['id']) ?>"
                    isloggedIn="<?= htmlspecialchars($isLoggedIn ? "1" : "0") ?>">Supprimer des favoris</button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
	</div>
    <script>
        document.querySelectorAll(".favoriteButton").forEach(button => {
            button.addEventListener("click", async function (event) {
                const recipeId = event.target.getAttribute("recipeId");
                const userId = event.target.getAttribute("userId");
                const isLoggedIn = event.target.getAttribute("isLoggedIn");

                if(isLoggedIn == "1"){
                    try {
                        const response = await fetch("../routes/add_favorite.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify({ userId, recipeId, action: "delete" }),
                        });
                        
                        const responseText = await response.text();
                        console.log("Réponse brute :", responseText);
                        
                        try {
                            const result = JSON.parse(responseText);
                            location.reload();
                        } catch (error) {
                            console.error("Erreur lors de l'analyse JSON :", error, responseText);
                            alert("La réponse du serveur est invalide.");
                        }
                    } catch (error) {
                        console.error("Erreur lors de la requête :", error);
                        alert("Une erreur est survenue lors de l'ajout aux favoris.");
                    }
                }else{
                    try {
                        const response = await fetch("../routes/add_anonymous_favorite.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify({ recipeId, action: "delete"}),
                        });

                        const responseText = await response.text();
                        console.log("Réponse brute :", responseText);

                        try {
                            const result = JSON.parse(responseText);
                            location.reload();
                        } catch (error) {
                            console.error("Erreur lors de l'analyse JSON :", error, responseText);
                            alert("La réponse du serveur est invalide.");
                        }
                    } catch (error) {
                        console.error("Erreur lors de la requête :", error);
                    }
                }
            });
        });
    </script>
</body>
</html>
