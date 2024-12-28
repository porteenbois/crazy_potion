<?php
header('Content-Type: application/json');
require_once 'database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['userId'], $data['recipeId'], $data['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

$userId = $data['userId'];
$recipeId = $data['recipeId'];
$action = $data['action'];

try {
    $database = Database::getInstance();

    if ($action === 'add') {
        $database->add_favorite($userId, $recipeId);
        echo json_encode(['message' => 'Ajouté aux favoris avec succès !']);
    } elseif ($action === 'delete') {
        $database->delete_favorite($userId, $recipeId);
        echo json_encode(['message' => 'Retiré des favoris avec succès !']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Action non valide']);
    }

} catch (PDOException $e) {
    error_log("Erreur interne : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Une erreur interne est survenue.'.$e->getMessage()]);
}
?>
