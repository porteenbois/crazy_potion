<?php

class Database
{
    // Instance unique pour le Singleton
    private static $instance = null;

    // Connexion PDO
    private $connection;

    // Paramètres de connexion
    private $host = 'localhost';
    private $dbname = 'boissons';
    private $username = 'root';
    private $password = 'potions';

    public $tree = null;

    // Constructeur privé pour empêcher l'instanciation directe
    private function __construct()
    {
        try {
            $this->connection = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    // Méthode pour récupérer l'instance unique
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    // Méthode pour obtenir la connexion PDO
    public function getConnection()
    {
        return $this->connection;
    }

    // Obtenir toutes les recettes avec leurs ingrédients
    public function get_recettes()
    {
        try {
            $stmt = $this->connection->query("
                SELECT r.id, r.name AS recipe_name, r.description, ingredients as ingredients_text, 
                       GROUP_CONCAT(i.name SEPARATOR ', ') AS ingredients
                FROM recipes r
                LEFT JOIN ingredients_assoc ia ON r.id = ia.recipe_id
                LEFT JOIN ingredients i ON ia.ingredient_id = i.id
                GROUP BY r.id
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            echo "Erreur lors de la récupération des recettes : " . $e->getMessage();
            return [];
        }
    }

    // Obtenir la hiérarchie des ingrédients
    public function get_hierarchie()
    {
        try {
            $stmt = $this->connection->query("
                SELECT h.id_super, h.id_sub, i1.name AS parent_name, i2.name AS child_name
                FROM hierarchy h
                LEFT JOIN ingredients i1 ON h.id_super = i1.id
                LEFT JOIN ingredients i2 ON h.id_sub = i2.id
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            echo "Erreur lors de la récupération de la hiérarchie : " . $e->getMessage();
            return [];
        }
    }

    public function build_hierarchy_tree()
    {
        try {
            // Récupérer toutes les relations parent-enfant
            $stmt = $this->connection->query("
                SELECT i1.name AS parent, i2.name AS child
                FROM hierarchy h
                LEFT JOIN ingredients i1 ON h.id_super = i1.id
                LEFT JOIN ingredients i2 ON h.id_sub = i2.id
            ");
            $relations = $stmt->fetchAll();

            // Trouver la racine (élément sans parent)
            $stmt = $this->connection->query("
                SELECT DISTINCT name
                FROM hierarchy h
                LEFT JOIN ingredients i1 ON id_super = id
                WHERE id_super NOT IN (SELECT DISTINCT id_sub FROM hierarchy);

            ");
            $root = $stmt->fetchColumn();
            if (!$root) {
                throw new Exception("Aucune racine trouvée dans la hiérarchie.");
            }

            // Construire l'arbre
            $tree = [];
            $this->build_tree_recursive($root, $relations, $tree);

            $this->tree = $tree;
            return $tree;

        } catch (PDOException $e) {
            echo "Erreur lors de la construction de l'arbre : " . $e->getMessage();
            return [];
        }
    }

    /**
     * Fonction récursive pour construire l'arbre.
     */
    private function build_tree_recursive($node, $relations, &$tree)
    {
        $tree[$node] = [];
        foreach ($relations as $relation) {
            if ($relation['parent'] === $node) {
                $this->build_tree_recursive($relation['child'], $relations, $tree[$node]);
            }
        }
    }

    /**
     * Trouver tous les noeuds sous un noeud donné, peu importe son niveau.
     */
    public function get_all_nodes_from_node($tree, $node)
    {
        $nodes = [];

        // Trouver le sous-arbre correspondant au noeud
        $subtree = $this->find_subtree($tree, $node);

        if ($subtree === null) {
            return [];
        }

        // Collecter tous les noeuds dans la sous-arborescence
        $this->collect_nodes_recursive($subtree, $nodes);

        return $nodes;
    }

    //Fonction  pour trouver le sous-arbre correspondant au noeud.
    private function find_subtree($tree, $node)
    {
        // Si le noeud est trouvé à ce niveau, retourner son sous-arbre
        if (isset($tree[$node])) {
            return $tree[$node];
        }

        // Sinon chercher récursivement dans chaque sous-arbre
        foreach ($tree as $child => $subtree) {
            $result = $this->find_subtree($subtree, $node);
            if ($result !== null) {
                return $result;
            }
        }

        return null; // Le noeud n'a pas été trouvé
    }

    //Fonction récursive pour collecter tous les noeuds dans une sous-arborescence.
    private function collect_nodes_recursive($subtree, &$nodes)
    {
        foreach ($subtree as $child => $childSubtree) {
            // Ajouter le noeud actuel à la liste
            $nodes[] = $child;

            // Si le noeud a des enfants, continuer la collecte
            if (!empty($childSubtree)) {
                $this->collect_nodes_recursive($childSubtree, $nodes);
            }
        }
    }

    public function get_ingredients()
    {
        if ($this->tree === null) {
            $this->build_hierarchy_tree();
        }

        $ingredients = [];

        // Fonction récursive pour parcourir l'arbre et construire les chemins
        $this->build_ingredient_paths($this->tree, '', $ingredients);

        return $ingredients;
    }

    /**
     * Parcourt l'arbre pour construire les chemins des ingrédients.
     */
    private function build_ingredient_paths($tree, $path, &$ingredients)
    {
        foreach ($tree as $node => $children) {
            // Construire le chemin actuel
            $currentPath = $path === '' ? strtolower($node) : $path . ' > ' . strtolower($node);

            // Ajouter le nœud actuel à la liste des ingrédients avec son chemin
            $ingredients[strtolower($node)] = $currentPath;

            // Continuer la construction du chemin pour les enfants, si existants
            if (!empty($children)) {
                $this->build_ingredient_paths($children, $currentPath, $ingredients);
            }
        }
    }
    public function search_by_ingredients(array $positive_ingredients, array $negative_ingredients)
    {
        try {
            // Récupérer toutes les recettes et leurs ingrédients
            $all_recipes = $this->get_recettes();

            // Construire l'arbre de la hiérarchie si ce n'est pas déjà fait
            if ($this->tree === null) {
                $this->build_hierarchy_tree();
            }

            // Préparer les sous-éléments pour chaque ingrédient
            $sub_positives = [];
            $sub_negatives = [];

            foreach ($positive_ingredients as $ingredient) {
                $sub_positives[$ingredient] = $this->get_all_nodes_from_node($this->tree, strtolower($ingredient));
            }

            foreach ($negative_ingredients as $ingredient) {
                $sub_negatives[$ingredient] = $this->get_all_nodes_from_node($this->tree, strtolower($ingredient));
            }

            // Ajouter les ingrédients eux-mêmes dans leurs sous-listes
            foreach ($positive_ingredients as $ingredient) {
                $sub_positives[$ingredient][] = strtolower($ingredient);
            }

            foreach ($negative_ingredients as $ingredient) {
                $sub_negatives[$ingredient][] = strtolower($ingredient);
            }

            // Total des critères (positifs + négatifs)
            $total_criteria = count($positive_ingredients) + count($negative_ingredients);

            // Résultats avec score
            $results = [];

            foreach ($all_recipes as $recipe) {
                $ingredients = explode(', ', strtolower($recipe['ingredients'] ?? ''));

                $negative_match = false; // Indique si un ingrédient négatif est présent
                $criteria_matched = 0;  // Nombre de critères remplis (positifs + négatifs)

                // Vérifier les ingrédients négatifs
                foreach ($sub_negatives as $negative => $sub_items) {
                    if (array_intersect($sub_items, $ingredients)) {
                        $negative_match = true;
                        break;
                    } else {
                        $criteria_matched++;
                    }
                }

                // Si un ingrédient négatif est présent, score = 0%
                if ($negative_match) {
                    $results[] = [
                        'recipe' => $recipe,
                        'score' => 0
                    ];
                    continue;
                }

                // Vérifier les ingrédients positifs
                foreach ($sub_positives as $positive => $sub_items) {
                    if (array_intersect($sub_items, $ingredients)) {
                        $criteria_matched++;
                    }
                }

                // Calculer le score en pourcentage
                $score_percentage = $total_criteria > 0
                    ? ($criteria_matched / $total_criteria) * 100
                    : 0;

                // Ajouter la recette aux résultats avec le score
                $results[] = [
                    'recipe' => $recipe,
                    'score' => round($score_percentage, 1) // Arrondi à 1 décimale
                ];
            }

            // Trier les résultats par score décroissant
            usort($results, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            return $results;

        } catch (Exception $e) {
            echo "Erreur lors de la recherche : " . $e->getMessage();
            return [];
        }
    }

    // Obtenir toutes les recettes avec leurs ingrédients
    public function get_recettes_with_ingredients()
    {
        try {
            $stmt = $this->connection->query("
                SELECT r.id, r.name AS recipe_name, r.description, 
                       GROUP_CONCAT(i.name SEPARATOR ', ') AS ingredients
                FROM recipes r
                LEFT JOIN ingredients_assoc ia ON r.id = ia.recipe_id
                LEFT JOIN ingredients i ON ia.ingredient_id = i.id
                GROUP BY r.id
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            echo "Erreur lors de la récupération des recettes : " . $e->getMessage();
            return [];
        }
    }

    // Fonction récursive pour récupérer tous les IDs des ingrédients enfants
    function get_ChildrenIds($parentId, &$ids = [])
    {
        $stmt = $this->connection->prepare("
            SELECT i.id 
            FROM hierarchy h
            JOIN ingredients i ON h.id_sub = i.id
            WHERE h.id_super = :parentId
        ");
        $stmt->execute(['parentId' => $parentId]);
        $children = $stmt->fetchAll();

        foreach ($children as $child) {
            $ids[] = $child['id'];
            $this->get_ChildrenIds($child['id'], $ids);
        }

        return $ids;
    }

    // Fonction pour récupérer les ingrédients enfants d'un ingrédient
    function getChildren($parentId)
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT i.id, i.name 
                FROM hierarchy h
                JOIN ingredients i ON h.id_sub = i.id
                WHERE h.id_super = :parentId
                ORDER BY i.name
            ");
            $stmt->execute(['parentId' => $parentId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des enfants : " . $e->getMessage());
            return [];
        }
    }

    // Fonction pour récupérer l'ID d'un ingrédient par son nom
    function get_ingredientId_by_name($name)
    {
        try {
            $stmt = $this->connection->prepare("SELECT id FROM ingredients WHERE name = :name");
            $stmt->execute(['name' => $name]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur dans get_ingredientId_by_name: " . $e->getMessage());
            return null;
        }
    }

    // Fonction pour récupérer les enfants d'un ingrédient
    function get_children_by_ingredientId($ingredientId)
    {
        try {
            $stmt = $this->connection->prepare("
                SELECT i.id, i.name 
                FROM hierarchy h
                JOIN ingredients i ON h.id_sub = i.id
                WHERE h.id_super = :ingredientId
                ORDER BY i.name
            ");
            $stmt->execute(['ingredientId' => $ingredientId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur dans get_children_by_ingredientId: " . $e->getMessage());
            return [];
        }
    }

    // Fonction modifiée pour récupérer les boissons avec tous leurs détails
    function get_boissons_by_ingredientId($ingredientId)
    {
        try {
            $allIds = $this->get_ChildrenIds($ingredientId);
            $allIds[] = $ingredientId;

            $idList = implode(',', array_map('intval', $allIds));

            if (empty($idList)) {
                return [];
            }

            $query = "
                SELECT DISTINCT r.id, r.name, r.description, r.ingredients
                FROM ingredients_assoc ia
                JOIN recipes r ON ia.recipe_id = r.id
                WHERE ia.ingredient_id IN ($idList)
                ORDER BY r.name
            ";

            $stmt = $this->connection->query($query);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des boissons : " . $e->getMessage());
            return [];
        }
    }

    function get_tree($path)
    {
        $tree = [];
        $currentTree = &$tree;

        foreach ($path as $step) {
            $ingredientId = $this->get_ingredientId_by_name($step);

            if (!$ingredientId) {
                break;
            }

            $children = $this->get_children_by_ingredientId($ingredientId);

            $currentTree[$step] = [
                'id' => $ingredientId,
                'children' => [],
            ];
            foreach ($children as $child) {
                $currentTree[$step]['children'][$child['name']] = [
                    'id' => $child['id'],
                    'children' => [],
                ];
            }

            $currentTree = &$currentTree[$step]['children'];
        }

        return $tree;
    }

    // Fonction d'affichage de l'arborescence
    function display_tree(array $tree, $depth = 0, $currentPath = [])
    {
        foreach ($tree as $name => $node) {
            // Construire le lien pour chaque élément
            $newPath = array_merge($currentPath, [$name]);
            $urlPath = htmlspecialchars(implode('>', $newPath));

            // Afficher l'élément avec un lien cliquable
            echo str_repeat("&nbsp;", $depth * 4) . '<a href="?path=' . $urlPath . '">' . htmlspecialchars($name) . '</a><br>';

            // Si des enfants existent, les afficher récursivement
            if (!empty($node['children'])) {
                $this->display_tree($node['children'], $depth + 1, $newPath);
            }
        }
    }

    function add_favorite($userId, $recipeId){
        $stmt = $this->connection->prepare("
            INSERT INTO favorites (recipe_id, user_id) 
            VALUES (:recipeId, :userId) 
            ON DUPLICATE KEY UPDATE recipe_id = recipe_id
        ");
        $stmt->execute(['recipeId' => $recipeId, 'userId' => $userId]);
    }

    function delete_favorite($userId, $recipeId){
        $stmt = $this->connection->prepare("
            DELETE FROM favorites 
            WHERE user_id = :userId AND recipe_id = :recipeId
        ");
        $stmt->execute(['userId' => $userId, 'recipeId' => $recipeId]);
    }
    

    function get_favorite_by_userId($userId){
        $stmt = $this->connection->prepare("
            SELECT DISTINCT r.id, r.name, r.description, r.ingredients
            FROM favorites f
            JOIN recipes r ON f.recipe_id = r.id
            WHERE f.user_id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    function get_recette_by_Id($recipeId){
        $stmt = $this->connection->prepare("
            SELECT DISTINCT r.id, r.name, r.description, r.ingredients
            FROM recipes r
            WHERE r.id = :recipe_id;
        ");
        $stmt->execute([':recipe_id' => $recipeId]);
        return $stmt->fetchAll()[0];
    }

    private function __clone()
    {
    }
    private function __wakeup()
    {
    }
}

?>