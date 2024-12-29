<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
  header('Location: login.php');
  exit;
}

// Récupération des informations utilisateur
$user = $_SESSION['user'];

$host = 'localhost';
$username = 'root';
$password = 'potions';
$dbname = 'boissons';

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les nouvelles informations depuis le formulaire
    $email = $_POST['email'];
    $phone = $_POST['phone_number'];
    $address = $_POST['address'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $gender = $_POST['gender'];
    $birth_date = $_POST['birth_date'];

    // Mettre à jour les informations utilisateur dans la base de données
    $sql = "UPDATE users SET 
            email = :email,
            phone_number = :phone,
            postal_address = :address,
            first_name = :first_name,
            last_name = :last_name,
            gender = :gender,
            birth_date = :birth_date
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':email' => $email,
      ':phone' => $phone,
      ':address' => $address,
      ':first_name' => $first_name,
      ':last_name' => $last_name,
      ':gender' => $gender,
      ':birth_date' => $birth_date,
      ':id' => $user['id'],
    ]);

    // Mettre à jour les informations en session
    $_SESSION['user']['email'] = $email;
    $_SESSION['user']['phone_number'] = $phone;
    $_SESSION['user']['postal_address'] = $address;
    $_SESSION['user']['first_name'] = $first_name;
    $_SESSION['user']['last_name'] = $last_name;
    $_SESSION['user']['gender'] = $gender;
    $_SESSION['user']['birth_date'] = $birth_date;

    $success = "Vos informations ont été mises à jour avec succès.";
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
  <title>Modifier les informations du compte</title>
</head>

<body>
  <div class="form-container">
    <h1>Modifier les informations</h1>

    <?php if (isset($success)): ?>
      <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <?php if (isset($error)): ?>
      <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" class="form">
      <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="form-input">
      <input type="text" name="phone_number" placeholder="Numéro de téléphone" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" class="form-input">
      <textarea name="address" placeholder="Adresse" class="form-input"><?php echo htmlspecialchars($user['postal_address'] ?? ''); ?></textarea>
      <input type="text" name="first_name" placeholder="Prénom" value="<?php echo htmlspecialchars($user['first_name']); ?>" required class="form-input">
      <input type="text" name="last_name" placeholder="Nom" value="<?php echo htmlspecialchars($user['last_name']); ?>" required class="form-input">
      <select name="gender" required class="form-input">
        <option value="m" <?php echo ($user['gender'] === 'm') ? 'selected' : ''; ?>>Homme</option>
        <option value="f" <?php echo ($user['gender'] === 'f') ? 'selected' : ''; ?>>Femme</option>
      </select>
      <input type="date" name="birth_date" value="<?php echo htmlspecialchars($user['birth_date']); ?>" required class="form-input">
      <button type="submit" class="form-button">Enregistrer</button>
    </form>
    <a href="../index.php" class="form-link">Retour à l'accueil</a>
  </div>
</body>

</html>

