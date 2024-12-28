<?php
require_once 'database.php';

header('Content-Type: application/json');

try {
  $db = Database::getInstance();
  $db->build_hierarchy_tree();
  $ingredients = $db->get_ingredients(); // retourne les ingrédients avec leurs chemins

  echo json_encode($ingredients, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
  echo json_encode(['error' => $e->getMessage()]);
}
?>