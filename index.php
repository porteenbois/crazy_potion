<?php
include('data/Donnees.inc.php');

function afficherSousCategories($categorie, $hierarchie) {
    if (isset($hierarchie[$categorie]['sous-categorie'])) {
        echo "<ul>";
        foreach ($hierarchie[$categorie]['sous-categorie'] as $sousCategorie) {
            echo "<li><a href='?categorie=$sousCategorie'>$sousCategorie</a></li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Aucune sous-catégorie disponible.</p>";
    }
}

function afficherRecettes($categorie, $recettes) {
    $recettesAffichees = false;
    echo "<h3>Recettes avec '$categorie'</h3>";
    foreach ($recettes as $recette) {
        if (in_array($categorie, $recette['index'])) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
            echo "<h4>{$recette['titre']}</h4>";
            echo "<p><strong>Ingrédients :</strong> {$recette['ingredients']}</p>";
            echo "<p><strong>Préparation :</strong> {$recette['preparation']}</p>";

            $imageName = str_replace(' ', '_', ucfirst(strtolower($recette['titre']))) . '.jpg';
            if (file_exists("ressources/photos/$imageName")) {
                echo "<img src='ressources/photos/$imageName' alt='{$recette['titre']}' style='max-width: 200px;'/>";
            } else {
                echo "<p><em>Aucune image disponible pour cette recette.</em></p>";
            }
            echo "</div>";
            $recettesAffichees = true;
        }
    }

    if (!$recettesAffichees) {
        echo "<p>Aucune recette trouvée pour cette catégorie.</p>";
    }
}

$categorieActuelle = isset($_GET['categorie']) ? $_GET['categorie'] : 'Aliment';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Navigation Hiérarchique</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Navigation dans la hiérarchie des aliments</h1>
    <p><a href="?categorie=Aliment">Retour à la racine</a></p>

    <?php
    $chemin = [];
    $tempCategorie = $categorieActuelle;
    while ($tempCategorie !== 'Aliment') {
        array_unshift($chemin, $tempCategorie);
        $tempCategorie = $Hierarchie[$tempCategorie]['super-categorie'][0] ?? 'Aliment';
    }
    array_unshift($chemin, 'Aliment');

    echo "<p><strong>Chemin :</strong> " . implode(" > ", $chemin) . "</p>";
    echo "<h2>Sous-catégories de '$categorieActuelle'</h2>";
    
    afficherSousCategories($categorieActuelle, $Hierarchie);
    afficherRecettes($categorieActuelle, $Recettes);
    ?>
</body>
</html>

