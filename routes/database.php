<?php

class Database{
    private static $instance = null;

    private $connection;

    private $host = 'localhost';
    private $dbname = 'boissons';
    private $username = 'root';
    private $password = 'potions';

    public $tree = null;

    private function __construct(){
        try {
            $this->connection = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    public static function getInstance(){
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(){
        return $this->connection;
    }

    public function get_recettes(){
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

    private function __clone()
    {
    }
    private function __wakeup()
    {
    }
}

?>