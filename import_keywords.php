<?php

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

    // Keep track of the duplicates locally to be able to send everything in one batch
    $keywordsToAdd = array();
    $keywordsExisting = array();

    // Prepare the INSERT statement for keywords
    $insertKeywordStmt = $conn->prepare("INSERT INTO $table_keyword (keyword) VALUES (?)");
    $insertKeywordStmt->bind_param("s", $keyword);

    // Prepare the INSERT statement for book-keyword associations
    $insertBookKeywordStmt = $conn->prepare("INSERT INTO $table_book_keywords (books_id, keywords_id) VALUES (?, ?)");
    $insertBookKeywordStmt->bind_param("ii", $bookId, $keywordId);

    // Start a transaction
    $conn->begin_transaction();
    
    // Retrieve existing keyword and their id from the database
    $sql = "SELECT id, keyword FROM $table_keyword";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $keyword = $row["keyword"];
            $keywordId = $row["id"];
            $keywordsExisting[$keyword] = $keywordId;
        }
    }

    // Read the keywords from the CSV file
    while (($data = fgetcsv($handle)) !== false) {
        $isbn = $data[0];
        $rawKeywords = explode(",", $data[1]);
        
        // Normalize raw keywords
        $rawKeywords = array_map("trim", $rawKeywords);
        
        // Add all the keywords that need to be added
        $keywordsToAdd = array_merge($keywordsToAdd, array_diff($rawKeywords, $keywordsExisting));
    }

    // Link each keyword to it's corresponding book.
    $sql = "SELECT id FROM $table_book WHERE isbn = '$isbn'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $bookId = $result->fetch_assoc()["id"];

        foreach ($keywordsToAdd as $keyword) {
            $keyword = $conn->real_escape_string($keyword);
            $insertKeywordStmt->execute();
            $keywordId = $insertKeywordStmt->insert_id;
            $keywordsExisting[$keyword] = $keywordId;
            unset($keywordsToAdd[$keyword]);

            $insertBookKeywordStmt->execute();
            if ($insertBookKeywordStmt->errno) {
                echo "Error linking keyword to book: " . $insertBookKeywordStmt->error;
            }
        }
    }

    // Commit the transaction
    $conn->commit();

    fclose($handle);
}

$insertKeywordStmt->close();
$insertBookKeywordStmt->close();
$conn->close();

echo "Keywords import completed successfully.";

?>