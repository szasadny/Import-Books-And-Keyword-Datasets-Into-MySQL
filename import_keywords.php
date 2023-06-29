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
$batchSize = 5000;

// Create connection
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process CSV file
if (($handle = fopen($csvFile, "r")) !== false) {

    // Define counter for batch processing size
    $counter = 0;

    // Keep track of the existing keywords
    $keywordsExisting = array();

    // Prepare the INSERT statement for keywords
    $insertKeywordStmt = $conn->prepare("INSERT IGNORE INTO $table_keyword (keyword) VALUES (?)");
    $insertKeywordStmt->bind_param("s", $keyword);

    // Prepare the INSERT statement for book-keyword associations
    $insertBookKeywordStmt = $conn->prepare("INSERT INTO $table_book_keywords (books_id, keywords_id) VALUES (?, ?)");
    $insertBookKeywordStmt->bind_param("ii", $bookId, $keywordId);

    // Start a transaction
    $conn->begin_transaction();

    // Retrieve existing keywords from the database
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

        foreach ($rawKeywords as $keyword) {
            // If keyword already exists, use the existing id
            if (isset($keywordsExisting[$keyword])) {
                $keywordId = $keywordsExisting[$keyword];
            // Else insert the new keyword and get its id
            } else {
                $insertKeywordStmt->execute();
                $keywordId = $insertKeywordStmt->insert_id;
                $keywordsExisting[$keyword] = $keywordId;
            }

            // Link the keyword to the book
            $sql = "SELECT id FROM $table_book WHERE isbn = '$isbn'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $bookId = $result->fetch_assoc()["id"];

                $insertBookKeywordStmt->execute();
                if ($insertBookKeywordStmt->errno) {
                    echo "Error linking keyword to book: " . $insertBookKeywordStmt->error;
                }
            }
        }

        $counter++;

        // Execute batch insert after reaching the batch size
        if ($counter === $batchSize) {
            $conn->commit();

            // Start a new transaction
            $conn->begin_transaction();

            // Reset the counter
            $counter = 0;
        }
    }

    // Commit the remaining records
    $conn->commit();

    fclose($handle);
}

$insertKeywordStmt->close();
$insertBookKeywordStmt->close();
$conn->close();

echo "Keywords import completed successfully.";

?>