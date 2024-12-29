<?php
require_once '../routes/database.php';
$database = Database::getInstance();

session_start();

// Initialisation du chemin et gestion de la navigation
$defaultPath = 'Aliment';

$path = explode('>', $_GET['path'] ?? '');

$currentPath = !empty($_GET['path']) ? $path : [$defaultPath];
$currentIngredientId = null;

// Récupération de l'ID de l'ingrédient courant
if (!empty($currentPath)) {
    try {
        $lastIngredient = end($currentPath);
        $stmt = $database->getConnection()->prepare("SELECT id FROM ingredients WHERE name = :name");
        $stmt->execute(['name' => $lastIngredient]);
        $currentIngredientId = $stmt->fetchColumn();

        if (!$currentIngredientId) {
            $currentPath = [$defaultPath];
            $stmt->execute(['name' => $defaultPath]);
            $currentIngredientId = $stmt->fetchColumn();
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération de l'ingrédient : " . $e->getMessage());
        $currentPath = [$defaultPath];
        $stmt->execute(['name' => $defaultPath]);
        $currentIngredientId = $stmt->fetchColumn();
    }
}

// Récupérer les enfants et les boissons
$children = $currentIngredientId ? $database->getChildren($currentIngredientId) : [];
$drinks = $currentIngredientId ? $database->get_boissons_by_ingredientId($currentIngredientId) : [];

// Préparer le chemin pour le bouton retour
$parentPath = array_slice($currentPath, 0, -1);

// Vérification de la connexion utilisateur
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
    <div class="header">
        <div class="left">
            <h1>Crazy Potions</h1>
        </div>

        <div class="center">
            <h3>Hiérarchie</h3>
            <a href="../index.php">
                <h3>Recherche</h3>
            </a>
            <a href="favorites.php">
                <h3>Favoris</h3>
            </a>
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
    <div class="main">
        <div class="navigation">
            <p class="path">Chemin actuel : <strong><?= htmlspecialchars(implode(' > ', $currentPath)) ?></strong></p>

            <h2>Sous-catégories :</h2>
            <ul>
                <?php
                $database->display_tree($database->get_tree($currentPath));
                ?>
            </ul>
            <h2>Boissons correspondantes :</h2>
            <p class="drink-count"><?= count($drinks) ?> boisson(s) trouvée(s)</p>
        </div>

        <?php if (!empty($drinks)): ?>

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
                            isloggedIn="<?= htmlspecialchars($isLoggedIn ? "1" : "0") ?>">Ajouter aux favoris</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-results">Aucune boisson correspondante trouvée.</p>
        <?php endif; ?>

    </div>
    <script>
        document.querySelectorAll(".favoriteButton").forEach(button => {
            button.addEventListener("click", async function (event) {
                const recipeId = event.target.getAttribute("recipeId");
                const userId = event.target.getAttribute("userId");
                const isLoggedIn = event.target.getAttribute("isLoggedIn");
                if (isLoggedIn == "1") {
                    try {
                        const response = await fetch("../routes/add_favorite.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify({ userId, recipeId, action: "add" }),
                        });
                        
                        const responseText = await response.text();
                        console.log("Réponse brute :", responseText);
                        
                        try {
                            const result = JSON.parse(responseText);
                            alert(result.message || "Ajouté aux favoris !");
                        } catch (error) {
                            console.error("Erreur lors de l'analyse JSON :", error, responseText);
                            alert("La réponse du serveur est invalide.");
                        }
                    } catch (error) {
                        console.error("Erreur lors de la requête :", error);
                        alert("Une erreur est survenue lors de l'ajout aux favoris.");
                    }
                } else {
                    try {
                        const response = await fetch("../routes/add_anonymous_favorite.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify({ recipeId, action: "add"}),
                        });

                        const responseText = await response.text();
                        console.log("Réponse brute :", responseText);

                        try {
                            const result = JSON.parse(responseText);
                            alert(result.message || "Ajouté aux favoris !");
                        } catch (error) {
                            console.error("Erreur lors de l'analyse JSON :", error, responseText);
                            alert("La réponse du serveur est invalide.");
                        }
                    } catch (error) {
                        console.error("Erreur lors de la requête :", error);
                        alert("Une erreur est survenue lors de l'ajout aux favoris.");
                    }
                }

            });
        });
    </script>
</body>

</html>
