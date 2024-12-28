<?php
session_start();

header('Content-Type: application/json');

// Récupérer les données envoyées
$data = json_decode(file_get_contents('php://input'), true);
$recipeId = $data['recipeId'] ?? null;

// Initialiser le tableau des favoris si nécessaire
if (!isset($_SESSION['favoris'])) {
    $_SESSION['favoris'] = [];
}

// Vérifier si la recette n'est pas déjà dans les favoris
if ($recipeId && !in_array($recipeId, $_SESSION['favoris'])) {
    $_SESSION['favoris'][] = $recipeId;
    echo json_encode([
        'success' => true,
        'message' => 'Ajouté aux favoris ! Créez un compte pour sauvegarder vos favoris de façon permanente.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Cette boisson est déjà dans vos favoris ou ID invalide.'
    ]);
}
?>