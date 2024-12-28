<?php
session_start();

// Vérifier si la session est démarrée
if (session_status() === PHP_SESSION_ACTIVE) {
    // Retourner les informations de la session sous forme de tableau JSON
    echo json_encode($_SESSION);
} else {
    echo json_encode(["error" => "La session n'est pas active."]);
}
?>
