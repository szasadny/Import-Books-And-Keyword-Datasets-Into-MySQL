<?php
// Configuration variables
$servername = "localhost";
$username = "root";
$password = "";
$database = "wt_library";
$table = "book";
$csvFile = "books_import.csv";
$active = true;

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Read the CSV file
$file = fopen($csvFile, "r");
if (!$file) {
    die("Error opening the CSV file.");
}

// Skip the header row
fgetcsv($file);

// Prepare the SQL statement
$sql = "INSERT INTO $table (isbn, title, writer, avg_score, active) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssdi", $isbn, $title, $writer, $avg_score, $active);

// Execute the SQL statement for each row in the CSV file
while (($data = fgetcsv($file)) !== false) {
    $isbn = $data[0];
    $title = $data[1];
    $writer = $data[2];
    $avg_score = floatval($data[3]);
    $active = true;

    // Execute the prepared statement
    $stmt->execute();
}

$stmt->close();
fclose($file);

$conn->close();

echo "Data imported successfully!";
?>