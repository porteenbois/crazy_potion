<?php
session_start(); // Démarre une session pour gérer l'état de connexion

// Configuration de la base de données
$host = 'localhost';
$username = 'root';
$password = 'potions';
$dbname = 'boissons';

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'];
    $password = $_POST['password'];

    // Rechercher l'utilisateur par son login
    $sql = "SELECT * FROM users WHERE login = :login";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':login' => $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
      // Stocker les informations utilisateur dans la session
      $_SESSION['user'] = [
        'id' => $user['id'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email']
      ];

      // Rediriger vers la page d'accueil
      header('Location: ../index.php');
      exit;
    } else {
      $error = "Nom d'utilisateur ou mot de passe incorrect.";
    }
  }
} catch (PDOException $e) {
  $error = "Erreur : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style/login.css">
  <title>Connexion</title>
</head>

<body>
  <div class="form-container">
    <h1>Connexion</h1>

    <?php if (isset($error)): ?>
      <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" class="form">
      <input type="text" name="login" placeholder="Nom d'utilisateur" required class="form-input">
      <input type="password" name="password" placeholder="Mot de passe" required class="form-input">
      <button type="submit" class="form-button">Se connecter</button>
    </form>
    <a href="signup.php" class="form-link">Créer un compte</a>
  </div>
</body>

</html>

