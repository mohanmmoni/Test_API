<?php

/**
 * Manage Databse
 */
class Manage_Database
{
    protected $table_status;

    /**
     * @var $db mysql object
     */
    protected $db;
    /**
     * Construct
     */
    public function __construct()
    {
        $servername = "localhost";
        $username = "mohan";
        $password = "Mohan@123";
        $dbname = "test";
        $this->db = new mysqli($servername, $username, $password, $dbname);
        // Check connection
        if ($this->db->connect_error) {
            die("Connection failed: " . $this->db->connect_error);
        }
        $this->check_table_exist();
        // $this->insert_data_movies();
    }

    public function check_table_exist()
    {
        // SQL query to check if the first table exists
        $sql_check = "SHOW TABLES LIKE 'movies'";

        // Execute the query
        $result = $this->db->query($sql_check);
        if ($result->num_rows > 0) {
            $this->table_status['movies']  =   true;
        } else {
            $this->table_status['movies']  =   $this->create_table_movies();
        }
        // SQL query to check if the first table exists
        $sql_check1 = "SHOW TABLES LIKE 'ratings'";

        // Execute the query
        $result1 = $this->db->query($sql_check1);
        if ($result1->num_rows > 0) {
            $this->table_status['ratings']  =   true;
        } else {
            $this->table_status['ratings']  =   $this->create_table_ratings();
        }
        return $this->table_status['ratings'] && $this->table_status['movies']  ? true : false ;
    }

    private function create_table_movies()
    {
        $query = "CREATE TABLE movies (
                    tconst VARCHAR(255) NOT NULL,
                    titleType VARCHAR(255) NOT NULL,
                    primaryTitle VARCHAR(255) NOT NULL,
                    runtimeMinutes INT,
                    genres VARCHAR(255),
                    PRIMARY KEY (tconst));";

        $status = $this->db->query($query);
        if ($status) {
            $this->insert_data_movies();
        }
        return $status;
    }

    private function create_table_ratings()
    {
        $query = "CREATE TABLE ratings (
            tconst VARCHAR(255) NOT NULL,
            averageRating DECIMAL(3,1),
            numVotes INT,
            PRIMARY KEY (tconst));";
        $status = $this->db->query($query);
        if ($status) {
            $this->insert_data_ratings();
        }
        return $status;
    }

    public function insert_data_ratings()
    {
        // Open the CSV file
        $filename = __DIR__ . "/ratings.csv";
        $file = fopen($filename, "r");

        $i = 0;
        // Loop through the rows of the CSV file
        while (($data = fgetcsv($file)) !== FALSE) {

            if ($data[0] == 'tconst') {
                continue;
            }
            // Escape the values to prevent SQL injection
            $tconst = $data[0];
            $averageRating = $data[1];
            $numVotes = $data[2];

            // SQL query to insert the row into the table
            $query = "INSERT INTO ratings (tconst, averageRating, numVotes) VALUES ('$tconst', '$averageRating', '$numVotes')";
            // Execute the query
            $this->db->query($query);
            if(feof($file)) {
                break;
            }
        }

        // Close the CSV file
        fclose($file);
    }

    public function insert_data_movies()
    {
        // Open the CSV file
        $filename = __DIR__ . "/movies.csv";
        $file = fopen($filename, "r");

        // Loop through the rows of the CSV file
        while (($data = fgetcsv($file)) !== FALSE) {

            if ($data[0] == 'tconst') {
                continue;
            }
            // Escape the values to prevent SQL injection
            $tconst = $data[0];
            $titleType = $data[1];
            $primaryTitle = $data[2];
            $runtimeMinutes = $data[3];
            $genres = $data[4];

            // SQL query to insert the row into the table
            $query = "INSERT INTO movies (tconst, titleType, primaryTitle, runtimeMinutes, genres) VALUES ('$tconst', '$titleType', '$primaryTitle', '$runtimeMinutes', '$genres')";
            // Execute the query
            $this->db->query($query);
            if(feof($file)) {
                break;
            }
        }

        //  Close the CSV file
        fclose($file);
    }

    public function get_longest_duration_movies()
    {
        $query = "SELECT tconst, primaryTitle, runtimeMinutes, genres 
                FROM movies 
                ORDER BY runtimeMinutes DESC LIMIT 10";
        $result = $this->db->query($query);

        $movies = [];
        while ($row = $result->fetch_assoc()) {
            $movies[] = $row;
        }
        return $movies;
    }

    public function insert_data($data,$table_name)
    {
        $query = '';
        if($table_name == 'movies') {
            $tconst = $data['tconst'];
            $titleType = $data['titleType'];
            $primaryTitle = $data['primaryTitle'];
            $runtimeMinutes = $data['runtimeMinutes'];
            $genres = $data['genres'];
            $query = "INSERT INTO movies (tconst, titleType, primaryTitle, runtimeMinutes, genres) VALUES ('$tconst', '$titleType', '$primaryTitle', '$runtimeMinutes', '$genres')";
        }else if($table_name == 'ratings') {
            // Escape the values to prevent SQL injection
            $tconst = $data['tconst'];
            $averageRating = $data['averageRating'];
            $numVotes = $data['numVotes'];

            // SQL query to insert the row into the table
            $query = "INSERT INTO ratings (tconst, averageRating, numVotes) VALUES ('$tconst', '$averageRating', '$numVotes')";
        }

        if(!empty($query)) {
            try{
            $status = $this->db->query($query);
                return $status;
            } catch (Exception $e) {
                echo $e->getMessage();
                exit();
            }
        }

    }

    public function get_top_rated_movies()
    {
        $query = 'SELECT m.tconst, m.primaryTitle, m.genres as genre, AVG(r.`averageRating`) AS averageRating 
                FROM movies m
                JOIN ratings r ON m.tconst = r.tconst 
                GROUP BY m.tconst HAVING averageRating > 6.0 
                ORDER BY averageRating DESC';
            try{
                $result = $this->db->query($query);
                $movies = [];
                while ($row = $result->fetch_assoc()) {
                    $movies[] = $row;
                }
                    return $movies;
                } catch (Exception $e) {
                    echo $e->getMessage();
                    exit();
                }

    }

    public function get_genre_movies_with_subtotals()
    {
          // prepare the SQL query
          $query = 'SELECT m.genres, m.primaryTitle, SUM(r.numVotes) AS numVotes
          FROM movies m
          JOIN ratings r ON m.tconst = r.tconst
          GROUP BY m.genres, m.primaryTitle WITH ROLLUP';

        // execute the query
        try{
            $result = $this->db->query($query);
            $movies = [];
            while ($row = $result->fetch_assoc()) {
                $movies[] = $row;
            }
                return $movies;
            } catch (Exception $e) {
                echo $e->getMessage();
                exit();
            }
    }

    public function update_runtime_minutes()
    {
        $query = "UPDATE movies
                SET runtimeMinutes = 
                CASE
                    WHEN genres = 'Documentary' THEN runtimeMinutes + 15
                    WHEN genres = 'Animation' THEN runtimeMinutes + 30
                    ELSE runtimeMinutes + 45
                END";
                        // execute the query
        try{
            $status = $this->db->query($query);
            return $status;
            } catch (Exception $e) {
                echo $e->getMessage();
                exit();
            }
    }

    public function __destruct()
    {
        $this->db->close();
    }
}
