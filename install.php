<?php
// Configuration de la base de données
$host = 'localhost'; // Serveur de la base de données
$username = 'root';  // Nom d'utilisateur MySQL/MariaDB
$password = 'potions'; // Mot de passe MySQL/MariaDB
$dbname = 'boissons'; // Nom de la base à créer

include 'ressources/Donnees.inc.php';

// Liste des noms de tables
const DB_TABLES = ["ingredients", "hierarchy", "users", "recipes", "ingredients_assoc", "favorites"];

// Liste des requêtes SQL pour créer les tables
const SQL_TABLES = [
    "CREATE TABLE IF NOT EXISTS ingredients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL
    )",

    "CREATE TABLE IF NOT EXISTS hierarchy (
        id_super INT,
        id_sub INT,
        PRIMARY KEY (id_super, id_sub),
        FOREIGN KEY (id_super) REFERENCES ingredients(id),
        FOREIGN KEY (id_sub) REFERENCES ingredients(id)
    )",

    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        login VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        last_name VARCHAR(255),
        first_name VARCHAR(255),
        postal_address VARCHAR(255),
        gender ENUM('m', 'f'),
        email VARCHAR(255),
        phone_number VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        birth_date DATE
    )",

    "CREATE TABLE IF NOT EXISTS recipes (
        id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(255) NOT NULL,
	ingredients TEXT NOT NULL,
        description TEXT NOT NULL
    )",

    "CREATE TABLE IF NOT EXISTS ingredients_assoc (
        recipe_id INT,
        ingredient_id INT,
        PRIMARY KEY (recipe_id, ingredient_id),
        FOREIGN KEY (recipe_id) REFERENCES recipes(id),
        FOREIGN KEY (ingredient_id) REFERENCES ingredients(id)
    )",

    "CREATE TABLE IF NOT EXISTS favorites (
        recipe_id INT,
        user_id INT,
        PRIMARY KEY (recipe_id, user_id),
        FOREIGN KEY (recipe_id) REFERENCES recipes(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )"
];

try {
    // Connexion à MySQL (sans base de données pour la création initiale)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Supprimer la base de données si elle existe
    $sql = "DROP DATABASE IF EXISTS $dbname";
    $pdo->exec($sql);
    echo "Database '$dbname' dropped successfully.<br>";

    // Création de la base de données
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    $pdo->exec($sql);
    echo "Database '$dbname' created successfully.<br>";

    // Connexion à la base de données nouvellement créée
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Création des tables
    foreach (SQL_TABLES as $index => $query) {
        $tableName = DB_TABLES[$index];
        $pdo->exec($query);
        echo "Table '$tableName' created successfully.<br>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to database '$dbname'.<br>";

    // 1. Insertion des aliments et de la hiérarchie
    foreach ($Hierarchie as $drink => $relations) {
        // Insérer l'aliment dans la table `ingredients`
        $stmt = $pdo->prepare("INSERT INTO ingredients (name) VALUES (:name) ON DUPLICATE KEY UPDATE id=id");
        $stmt->execute(['name' => strtolower($drink)]);

        // Récupérer l'ID de l'aliment inséré
        $stmt = $pdo->prepare("SELECT id FROM ingredients WHERE name = :name");
        $stmt->execute(['name' => $drink]);
        $drinkId = $stmt->fetchColumn();

        // Insérer les relations de sous-catégorie
        if (isset($relations['sous-categorie'])) {
            foreach ($relations['sous-categorie'] as $subCategory) {
                // Insérer ou récupérer la sous-catégorie
                $stmt = $pdo->prepare("INSERT INTO ingredients (name) VALUES (:name) ON DUPLICATE KEY UPDATE id=id");
                $stmt->execute(['name' => strtolower($subCategory)]);

                $stmt = $pdo->prepare("SELECT id FROM ingredients WHERE name = :name");
                $stmt->execute(['name' => $subCategory]);
                $subCategoryId = $stmt->fetchColumn();

                // Insérer la relation dans la table `hierarchy`
                $stmt = $pdo->prepare("INSERT INTO hierarchy (id_super, id_sub) VALUES (:idSuper, :idSub)");
                $stmt->execute(['idSuper' => strtolower($drinkId), 'idSub' => strtolower($subCategoryId)]);
            }
        }
    }
    echo "Hierarchy data inserted successfully.<br>";

    // 2. Insertion des recettes
    foreach ($Recettes as $recette) {
        // Insérer la recette dans la table `recipes`
        $stmt = $pdo->prepare("INSERT INTO recipes (name, ingredients, description) VALUES (:name, :ingredients ,:description)");
        $stmt->execute([
            'name' => $recette['titre'],
            'ingredients' => strtolower($recette["ingredients"]),
            'description' => strtolower($recette['preparation'])
        ]);
        echo $recette['titre'];
        echo "<br>";


        // Récupérer l'ID de la recette insérée
        $recipeId = $pdo->lastInsertId();

        // Insérer les ingrédients
        foreach ($recette["index"] as $ingredient) {
            // Insérer ou récupérer l'aliment dans la table `drinks`
            $stmt = $pdo->prepare("INSERT INTO ingredients (name) VALUES (:name) ON DUPLICATE KEY UPDATE id=id");
            $stmt->execute(['name' => strtolower($ingredient)]);

            echo $ingredient;
            echo "<br>";

            $stmt = $pdo->prepare("SELECT id FROM ingredients WHERE name = :name");
            $stmt->execute(['name' => $ingredient]);
            $ingredientId = $stmt->fetchColumn();

            // Insérer la relation recette-ingrédient dans la table `ingredients_assoc`
            $stmt = $pdo->prepare("INSERT INTO ingredients_assoc (recipe_id, ingredient_id) VALUES (:recipeId, :ingredientId) ON DUPLICATE KEY UPDATE recipe_id = recipe_id");
            $stmt->execute(['recipeId' => $recipeId, 'ingredientId' => $ingredientId]);

            echo $recipeId;
            echo "<br>";
            echo $ingredientId;
            echo "<br>";

        }
    }
    echo "Recipes data inserted successfully.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
