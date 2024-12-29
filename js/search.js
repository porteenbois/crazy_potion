const searchBar = document.getElementById('search-bar');
const resultsDiv = document.getElementById('autocomplete-results');
const addFilterBtn = document.getElementById('add-filter-btn');
const filtersList = document.getElementById('filters-list');

// Récupération des ingrédients via fetch
const fetchIngredients = async () => {
  try {
    const response = await fetch('../routes/ingredients.php');
    if (!response.ok) {
      throw new Error(`Erreur HTTP : ${response.status}`);
    }
    const ingredients = await response.json();
    return ingredients; // Renvoie les ingrédients pour utilisation ultérieure
  } catch (error) {
    console.error('Erreur lors de la récupération des ingrédients :', error);
    return {}; // Retourne un objet vide en cas d'erreur
  }
};

// Appeler fetchIngredients pour initialiser la variable ingredients
let chemins = {};
let ingredients = [];
fetchIngredients().then(data => {
  chemins = data; // Met à jour les ingrédients après récupération
  ingredients = Object.keys(chemins);
});

// Variable pour stocker les filtres
const filters = {
  positive: [],
  negative: []
};

// Fonction pour effectuer la recherche
const handleSearch = async () => {
  var query = searchBar.value.trim().toLowerCase();
  query = query.replace("pas de ","");

  if (query.length === 0) {
    resultsDiv.innerHTML = '';
    return;
  }

  try {
    const response = await fetch(`../routes/autocomplete.php?query=${encodeURIComponent(query)}`);
    var suggestions = await response.json();

    resultsDiv.innerHTML = '';

    if (ingredients.includes(query)) {
      suggestions = [query, ...suggestions.filter(suggestion => suggestion !== query)];
    }
    if (suggestions.length > 0) {

      suggestions.forEach(suggestion => {
        console.log(chemins[suggestion]);
        const div = document.createElement('div');
        //premiere lettre en majuscule
        div.textContent = chemins[suggestion].charAt(0).toUpperCase() + chemins[suggestion].slice(1).toLowerCase();

        div.addEventListener('click', () => {
          var query = searchBar.value.trim();
          if(query.includes("pas de ")){
            searchBar.value = "pas de "+suggestion;
          }
          else{
            searchBar.value = suggestion;
          }
          resultsDiv.innerHTML = '';
        });
        resultsDiv.appendChild(div);
      });
    } else {
      const noResultDiv = document.createElement('div');
      noResultDiv.textContent = 'Aucun résultat';
      resultsDiv.appendChild(noResultDiv);
    }
  } catch (error) {
    console.error('Erreur lors de la récupération des suggestions :', error);
  }
};

const displayResults = async (results) => {
  const resultsContainer = document.getElementById('results-container');
  resultsContainer.innerHTML = ''; // Réinitialiser la div

  if (results.length === 0) {
    resultsContainer.textContent = 'Aucune recette trouvée.';
    return;
  }

  try {
    // Récupérer les données de session avant d'afficher les résultats
    const response = await fetch('../routes/session_info.php');
    if (!response.ok) {
      console.error(`Erreur HTTP : ${response.status}`);
      resultsContainer.textContent = 'Impossible de charger les données utilisateur.';
      return;
    }
    const session_data = await response.json();
    const userId = session_data?.user?.id || null;

    results.forEach(result => {
      const resultItem = document.createElement('div');
      resultItem.className = 'result-item';

      // Générer le chemin de l'image en remplaçant les espaces par des underscores
      const imageName = result.recipe.recipe_name.replace(/\s+/g, '_');
      const imagePath = `ressources/images/${imageName}.jpg`;

      // Créer une balise <img> avec gestion d'image par défaut
      const imageElement = document.createElement('img');
      imageElement.src = imagePath;
      imageElement.alt = `Image de ${result.recipe.recipe_name}`;
      imageElement.className = 'recipe-image';
      imageElement.style = "max-width: 150px; height: auto;";
      imageElement.onerror = () => {
        imageElement.src = 'ressources/images/generic.jpg';
        imageElement.alt = 'Image non disponible';
      };

      // Créer une liste d'ingrédients
      const ingredientsList = result.recipe.ingredients
        ? result.recipe.ingredients
            .split(', ')
            .map(ingredient => `<li>${ingredient}</li>`)
            .join('')
        : '<li>Aucun ingrédient spécifié</li>';

      // Structure HTML des détails de la recette
      const recipeDetails = `
        <h3>${result.recipe.recipe_name}</h3>
        <p><strong>Description :</strong> ${result.recipe.description || 'Pas de description disponible.'}</p>
        <p><strong>Ingrédients :</strong></p>
        <ul>${ingredientsList}</ul>
        <p><strong>Score :</strong> ${result.score}%</p>
        <br>
      `;

      // Ajouter le bouton "Ajouter aux favoris"
      const favoriteButton = document.createElement('button');
      favoriteButton.className = `favoriteButton`;
      favoriteButton.textContent = 'Ajouter aux favoris';
      favoriteButton.setAttribute('recipeId', result.recipe.id);
      // favoriteButton.disabled = !userId; // Désactiver si l'utilisateur n'est pas connecté
      favoriteButton.addEventListener('click', async () => {

        if(userId){
          try {
            const response = await fetch('../routes/add_favorite.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({ userId, recipeId: result.recipe.id, action: 'add' }),
            });

            const responseText = await response.text();
            try {
              const result = JSON.parse(responseText);
              alert(result.message || 'Ajouté aux favoris !');
            } catch (error) {
              console.error('Erreur lors de l’analyse JSON :', error, responseText);
              alert('La réponse du serveur est invalide.');
            }
          } catch (error) {
            console.error('Erreur lors de la requête :', error);
            alert('Une erreur est survenue lors de l’ajout aux favoris.');
          }
        }else{
          try {
            const response = await fetch("../routes/add_anonymous_favorite.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ recipeId: result.recipe.id, action: "add"}),
            });

            const responseText = await response.text();
            console.log("Réponse brute :", responseText);

            try {
                const result = JSON.parse(responseText);
                alert(result.message || "Ajouté aux favoris !");
            } catch (error) {
                console.error("Erreur lors de l'analyse JSON :", error, responseText);
                alert("La réponse du serveur est invalide.");
            }
          } catch (error) {
              console.error("Erreur lors de la requête :", error);
              alert("Une erreur est survenue lors de l'ajout aux favoris.");
          }
        }
      });

      // Ajouter les éléments au conteneur de résultat
      resultItem.innerHTML = recipeDetails;
      resultItem.appendChild(imageElement); // Ajouter l'image
      resultItem.appendChild(favoriteButton); // Ajouter le bouton
      resultsContainer.appendChild(resultItem);
    });
  } catch (error) {
    console.error('Erreur lors de la récupération des données utilisateur :', error);
    resultsContainer.textContent = 'Une erreur est survenue lors du chargement des résultats.';
  }
};



// Ajouter un filtre
const addFilter = () => {
  const value = searchBar.value.trim();

  const type = !value.includes('pas de ') ? 'positive' : 'negative';

  const to_search = value.replace('pas de ', '').toLowerCase();
  if (!ingredients.includes(to_search)) {
      return;
  }

  if (value.length === 0 || (filters.positive.includes(value) || filters.negative.includes(value))) return;

  // Crée un élément de filtre
  const filterItem = document.createElement('div');
  filterItem.className = `filter-item ${type}`;
  filterItem.textContent = chemins[to_search];

  // Ajouter un bouton pour supprimer le filtre
  const removeBtn = document.createElement('button');
  removeBtn.innerHTML = '&times;';
  removeBtn.addEventListener('click', () => {
    filtersList.removeChild(filterItem);
    const index = filters[type].indexOf(to_search);
    if (index > -1) filters[type].splice(index, 1);

    // Mise à jour des résultats après suppression d'un filtre
    searchRecipes(filters.positive, filters.negative).then(displayResults);
    console.log(filters); // Affiche l'état des filtres
  });

  filterItem.appendChild(removeBtn);
  filtersList.appendChild(filterItem);

  // Ajouter le filtre à la liste
  filters[type].push(to_search);
  console.log(filters); // Affiche l'état des filtres

  // Mise à jour des résultats après ajout d'un filtre
  searchRecipes(filters.positive, filters.negative).then(displayResults);

  // Réinitialiser l'input
  searchBar.value = '';
  resultsDiv.innerHTML = '';
};


// Ajouter l'événement `input` pour les modifications de l'utilisateur
searchBar.addEventListener('input', handleSearch);

// Observer les changements de l'input
const observer = new MutationObserver(() => {
  handleSearch();
});
observer.observe(searchBar, {
  attributes: true,
  attributeFilter: ['value']
});

// Gérer l'ajout de filtres avec le bouton ou la touche Entrée
addFilterBtn.addEventListener('click', () => {
  addFilter();
});

searchBar.addEventListener('keydown', (e) => {
  if (e.key === 'Enter') {
    addFilter();
  }
});

// Fonction pour effectuer une recherche
const searchRecipes = async (positiveIngredients, negativeIngredients) => {
  try {
    // Préparer les données pour la requête
    const payload = {
      positive: positiveIngredients,
      negative: negativeIngredients,
    };

    // Envoyer une requête POST à l'API
    const response = await fetch('../routes/search_recipes.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    // Vérifier la réponse
    if (!response.ok) {
      throw new Error(`Erreur HTTP : ${response.status}`);
    }

    // Analyser les résultats
    const results = await response.json();
    console.log(results);

    // Retourner les résultats pour une utilisation ultérieure
    return results;
  } catch (error) {
    console.error('Erreur lors de la recherche des recettes :', error);
    return [];
  }
};
