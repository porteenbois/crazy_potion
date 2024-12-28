<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crazy Potions</title>
  <link rel="stylesheet" href="style/style.css">
</head>

<body>
  <?php
  session_start();
  $isLoggedIn = isset($_SESSION['user']);
  ?>

  <div class="header">
    <div class="left">
      <h1>Crazy Potions</h1>
    </div>

    <div class="center">
      <a href="pages/hierarchie.php">
        <h3>Hiérarchie</h3>
      </a>
      <h3>Recherche</h3>
      <a href="pages/favorites.php">
        <h3>Favoris</h3>
      </a>
    </div>

    <div class="right">
      <?php if ($isLoggedIn): ?>
	<p>Bonjour, <?php echo htmlspecialchars($_SESSION['user']['first_name']); ?> !</p>
	<a href="pages/modify_account.php">Modifier profil</>
        <a href="pages/logout.php">Se déconnecter</a>
      <?php else: ?>
        <a href="pages/login.php">Se connecter</a>
        <a href="pages/signup.php">Créer un compte</a>
      <?php endif; ?>
    </div>
  </div>

  <br>
  <div id="main">
    <div id="search-container">
      <input type="text" id="search-bar" placeholder="Rechercher un aliment..." autocomplete="off">
      <button id="add-filter-btn">Ajouter filtre</button>
    </div>
    <div id="autocomplete-results"></div>
    <div id="filters-container">
      <h3>Filtres :</h3>
      <div id="filters-list"></div>
    </div>

    <div id="results-container"></div>

  </div>
  <script src="js/search.js"></script>
</body>

</html>
