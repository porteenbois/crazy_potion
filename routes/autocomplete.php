<?php
require_once 'database.php';

header('Content-Type: application/json');

try {
  if (isset($_GET['query'])) {
    $query = $_GET['query'];
    $db = Database::getInstance();
    $db->build_hierarchy_tree();
    $suggestions = $db->get_all_nodes_from_node($db->tree, $query);
    echo json_encode($suggestions, JSON_UNESCAPED_UNICODE);
  } else {
    echo json_encode([]);
  }
} catch (Exception $e) {
  echo json_encode(['error' => $e->getMessage()]);
}
?>