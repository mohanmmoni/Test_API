<?php
include_once "database/manage_databse.php";
$database = new Manage_Database();

if ($_SERVER["REQUEST_METHOD"] === "GET" && $_SERVER["REQUEST_URI"] === "/api/v1/longest-duration-movies") {

    // set the content type header to JSON
    header('Content-Type: application/json');
    $movies = $database->get_longest_duration_movies();
    echo json_encode($movies);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $_SERVER["REQUEST_URI"] === "/api/v1/new-movie") {
    // Get request body as JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (empty($data['tconst']) || empty($data['titleType']) || empty($data['primaryTitle']) || empty($data['runtimeMinutes']) || empty($data['genres'])) {

        echo "Error: Missing Movies Information";
    } else {

        $new_data['tconst'] = $data['tconst'];
        $new_data['titleType'] = $data['titleType'];
        $new_data['primaryTitle'] = $data['primaryTitle'];
        $new_data['runtimeMinutes'] = $data['runtimeMinutes'];
        $new_data['genres'] = $data['genres'];

        $status = $database->insert_data($new_data, 'movies');

        $new_data1['tconst']    =   $data['tconst'];
        $new_data1['averageRating'] =   isset($data['averageRating']) && !empty($data['averageRating']) ? $data['averageRating'] : 0;
        $new_data1['numVotes']  =   isset($data['numVotes']) && !empty($data['numVotes']) ? $data['numVotes'] : 0;
        $status1 = $database->insert_data($new_data1, 'ratings');

        if ($status && $status1) {
            echo "Success";
        } else {
            echo "Failed";
        }
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SERVER['REQUEST_URI'] == '/api/v1/update-runtime-minutes') {

    $status = $database->update_runtime_minutes();
    if ($status) {
        echo "Success";
    } else {
        echo "Failed";
    } 
    exit();
}
// check if the route matches the request URI and method
if ($_SERVER['REQUEST_METHOD'] == 'GET' && $_SERVER['REQUEST_URI'] == '/api/v1/top-rated-movies') {

    // set the content type header to JSON
    header('Content-Type: application/json');
    $movies = $database->get_top_rated_movies();
    echo json_encode($movies);
    exit();
}

// check if the route matches the request URI and method
if ($_SERVER['REQUEST_METHOD'] == 'GET' && $_SERVER['REQUEST_URI'] == '/api/v1/genre-movies-with-subtotals' ) {
    // set the content type header to JSON
    // header('Content-Type: application/json');

    $movies = $database->get_genre_movies_with_subtotals();
    ?>
    <style>
        table.genre-movies-with-subtotals {
            border-collapse: collapse;
        }
        table.genre-movies-with-subtotals, table.genre-movies-with-subtotals td, table.genre-movies-with-subtotals th  {
            border: solid 1.5px black;
        }
    </style>
    <table class="genre-movies-with-subtotals">
        <thead>
            <tr>
                <th>
                    Genre
                </th>
                <th>
                    primaryTitle
                </th>
                <th>
                    numVotes
                </th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach($movies as $movie) {
                echo "<tr>";
                if($movie['genres'] == null){
                    echo "<td></td>";
                    echo "<td><b>Grand Total</b></td>";
                    echo "<td>".$movie['numVotes']."</td>";
                }else if($movie['primaryTitle'] == null){
                    echo "<td></td>";
                    echo "<td><b>Total</b></td>";
                    echo "<td>".$movie['numVotes']."</td>";
                } else {
                    echo "<td>".$movie['genres']."</td>";
                    echo "<td>".$movie['primaryTitle']."</td>";
                    echo "<td>".$movie['numVotes']."</td>";
                    echo "</tr>";
                }
            } ?>
        </tbody>
    </table>
    <?php
    exit();
  }