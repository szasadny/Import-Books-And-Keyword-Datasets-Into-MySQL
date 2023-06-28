<?php

// Set maximum execution time to 10 minutes
ini_set('max_execution_time', 600);

// Configuration variables
$servername = "localhost";
$username = "root";
$password = "";
$database = "wt_library";
$table_book = "book";
$table_book_keywords = "keyword_books";
$table_keyword = "keyword";
$csvFile = "books_keywords.csv";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process CSV file
if (($handle = fopen($csvFile, "r")) !== false) {
 
    // We splitsen het hier in twee arrays om lokaal de duplicaten eruit te filteren
    // en zo het aantal SQL statements the verminderen voor een snellere runtime.
    $keywordsToAdd = array(); // Lijst met keywords om toe voegen
    $keywordsExisting = array(); // Lijst met keywords die al toegevoegd zijn

    while (($data = fgetcsv($handle)) !== false) {
        $isbn = $data[0];
        $rawKeywords = explode(",", $data[1]);

        // Normalize and deduplicate keywords
        $rawKeywords = array_map("trim", $rawKeywords);
        $keywordsToAdd = array_merge($keywordsToAdd, array_diff($rawKeywords, $keywordsExisting));

        // Retrieve existing keyword IDs
        $sql = "SELECT id, keyword FROM $table_keyword WHERE keyword IN ('" . implode("','", $rawKeywords) . "')";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $keyword = $row["keyword"];
                $keywordId = $row["id"];
                $keywordsExisting[$keyword] = $keywordId;
            }
        }

        // Link keywords to the book in the keyword_books table
        $sql = "SELECT id FROM $table_book WHERE isbn = '$isbn'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $bookId = $result->fetch_assoc()["id"];

            foreach ($rawKeywords as $keyword) {
                if (isset($keywordsExisting[$keyword])) {
                    $keywordId = $keywordsExisting[$keyword];
                } else {
                    $keyword = $conn->real_escape_string($keyword);
                    $sql = "INSERT INTO $table_keyword (keyword) VALUES ('$keyword')";

                    if ($conn->query($sql)) {
                        $keywordId = $conn->insert_id;
                        $keywordsExisting[$keyword] = $keywordId;
                    } else {
                        echo "Error inserting keyword: " . $conn->error;
                        continue;
                    }
                }

                $sql = "INSERT INTO $table_book_keywords (books_id, keywords_id) VALUES ($bookId, $keywordId)";

                if (!$conn->query($sql)) {
                    echo "Error linking keyword to book: " . $conn->error;
                }
            }
        }
    }

    fclose($handle);
}

$conn->close();

echo "Keywords import completed successfully.";

?>