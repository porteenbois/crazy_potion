<?php
require_once 'database.php';

header('Content-Type: application/json');

try {
  // requete POST
  $data = json_decode(file_get_contents('php://input'), true);

  if (isset($data['positive']) && isset($data['negative'])) {
    $positive = $data['positive'];
    $negative = $data['negative'];

    $db = Database::getInstance();
    $db->build_hierarchy_tree();

    // Utilise la fonction search_by_ingredients
    $results = $db->search_by_ingredients($positive, $negative);

    echo json_encode($results, JSON_UNESCAPED_UNICODE);
  } else {
    echo json_encode(['error' => 'Données invalides. Fournir les ingrédients positifs et négatifs.']);
  }
} catch (Exception $e) {
  echo json_encode(['error' => $e->getMessage()]);
}
?>