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
$sql = "INSERT INTO $table (isbn, title, writer, avg_score, active) VALUES ";

// Make an array for all the values that will be inserted
$toBeInserted = [];
while (($data = fgetcsv($file)) !== false) {
    $isbn = mysqli_real_escape_string($conn, $data[0]);
    $title = mysqli_real_escape_string($conn, $data[1]);
    $writer = mysqli_real_escape_string($conn, $data[2]);
    $avg_score = floatval($data[3]);
    $active = true;

    $toBeInserted[] = "('$isbn', '$title', '$writer', $avg_score, $active)";
}

// Append the to be inserted values into the SQL statement
$values = implode(", ", $toBeInserted);
$sql .= $values;

// Execute the SQL statement
if ($conn->query($sql) === true) {
    echo "Data imported successfully!";
} else {
    echo "Error importing data: " . $conn->error;
}

fclose($file);
$conn->close();

?>