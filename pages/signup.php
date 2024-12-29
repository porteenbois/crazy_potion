<?php
$host = 'localhost';
$username = 'root';
$password = 'potions';
$dbname = 'boissons';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $login = $_POST['login'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $email = $_POST['email'];
        $phone = $_POST['phone_number'];
        $address = $_POST['address'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $gender = $_POST['gender'];
        $birth_date = $_POST['birth_date'];

        $sql = "INSERT INTO users (login, password, email, phone_number, postal_address, first_name, last_name, gender, birth_date)
                VALUES (:login, :password, :email, :phone, :address, :first_name, :last_name, :gender, :birth_date)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':login' => $login,
            ':password' => $password,
            ':email' => $email,
            ':phone' => $phone,
            ':address' => $address,
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':gender' => $gender,
            ':birth_date' => $birth_date,
        ]);

        // Rediriger vers la page de connexion
        header('Location: login.php');
        exit;
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
  <title>Créer un compte</title>
</head>

<body>
  <div class="form-container">
    <h1>Créer un compte</h1>

    <?php if (isset($error)): ?>
      <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" class="form">
      <input type="text" name="login" placeholder="Nom d'utilisateur" required class="form-input">
      <input type="password" name="password" placeholder="Mot de passe" required class="form-input">
      <input type="email" name="email" placeholder="Email" required class="form-input">
      <input type="text" name="phone_number" placeholder="Numéro de téléphone" required class="form-input">
      <textarea name="address" placeholder="Adresse" required class="form-input"></textarea>
      <input type="text" name="first_name" placeholder="Prénom" required class="form-input">
      <input type="text" name="last_name" placeholder="Nom" required class="form-input">
      <select name="gender" required class="form-input">
        <option value="m">Homme</option>
        <option value="f">Femme</option>
      </select>
      <input type="date" name="birth_date" required class="form-input">
      <button type="submit" class="form-button">Créer le compte</button>
    </form>
    <a href="login.php" class="form-link">Se connecter</a>
  </div>
</body>

</html>

