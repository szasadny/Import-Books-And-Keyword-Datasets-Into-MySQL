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

    // We keep track of the duplicates locally to limit the amount of SQL queries being sent.
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
                    $insertKeywordStmt->execute();
                    $keywordId = $insertKeywordStmt->insert_id;
                    $keywordsExisting[$keyword] = $keywordId;
                }

                $insertBookKeywordStmt->execute();
                if ($insertBookKeywordStmt->errno) {
                    echo "Error linking keyword to book: " . $insertBookKeywordStmt->error;
                }
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