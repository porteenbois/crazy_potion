<?php
session_start();

header('Content-Type: application/json');

// Récupérer les données envoyées
$data = json_decode(file_get_contents('php://input'), true);
$recipeId = $data['recipeId'] ?? null;
$action = $data['action'] ?? null;

// Initialiser le tableau des favoris si nécessaire
if (!isset($_SESSION['favoris'])) {
    $_SESSION['favoris'] = [];
}

if (!$recipeId) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de recette invalide.'
    ]);
    exit;
}

if ($action === 'add') {
    // Ajouter aux favoris
    if (!in_array($recipeId, $_SESSION['favoris'])) {
        $_SESSION['favoris'][] = $recipeId;
        echo json_encode([
            'success' => true,
            'message' => 'Ajouté aux favoris ! Créez un compte pour sauvegarder vos favoris de façon permanente.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Cette boisson est déjà dans vos favoris.'
        ]);
    }
} elseif ($action === 'delete') {
    // Supprimer des favoris
    $_SESSION['favoris'] = array_filter($_SESSION['favoris'], function($id) use ($recipeId) {
        return $id !== $recipeId;
    });
    echo json_encode([
        'success' => true,
        'message' => 'Retiré des favoris avec succès !'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Action non valide.'
    ]);
}
