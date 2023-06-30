<?php

require '../configuration.php';

// Database connection details
$db_host = $db_host ?? '';
$db_port = $db_port !== '' ? (int)$db_port : 3306;
$db_username = $db_username ?? '';
$db_password = $db_password ?? '';
$db_name = $db_name ?? '';

// Connect to the database
$connection = mysqli_connect($db_host, $db_username, $db_password, $db_name, $db_port);

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Directory path for CSV files
$csvDirectory = './csv/';

// Get all .csv files in the directory
$csvFiles = glob($csvDirectory . '*.csv');

// Process each CSV file
foreach ($csvFiles as $csvFile) {
    // Open the CSV file
    $handle = fopen($csvFile, 'r');

    // Skip the first 5 lines
    for ($i = 0; $i < 5; $i++) {
        fgetcsv($handle);
    }

    // Process each line in the CSV file
    while (($csvData = fgetcsv($handle)) !== false) {
        // Extract the necessary data from the CSV row
        $extension = trim($csvData[0]);
        $registrationPrice = trim($csvData[3]);
        $renewalPrice = trim($csvData[3]);

        // Skip the record if extension is null or empty
        if (empty($extension)) {
            continue;
        }
        
        // Check if the extension exists in tbldomainpricing
        $selectQuery = "SELECT id FROM tbldomainpricing WHERE extension = ?";
        $selectStatement = mysqli_prepare($connection, $selectQuery);
        mysqli_stmt_bind_param($selectStatement, 's', $extension);
        mysqli_stmt_execute($selectStatement);
        $selectResult = mysqli_stmt_get_result($selectStatement);

        if ($selectRow = mysqli_fetch_assoc($selectResult)) {
            $domainPricingId = $selectRow['id'];
            
            // Check if the pricing record exists in tblpricing
            $pricingQuery = "SELECT id FROM tblpricing WHERE type = 'domainregister' AND relid = ?";
            $pricingStatement = mysqli_prepare($connection, $pricingQuery);
            mysqli_stmt_bind_param($pricingStatement, 'i', $domainPricingId);
            mysqli_stmt_execute($pricingStatement);
            $pricingResult = mysqli_stmt_get_result($pricingStatement);
            
            if ($pricingRow = mysqli_fetch_assoc($pricingResult)) {
                // Pricing record exists, update the existing record
                $pricingId = $pricingRow['id'];
                $updateQuery = "UPDATE tblpricing SET annually = ? WHERE id = ?";
                $updateStatement = mysqli_prepare($connection, $updateQuery);
                mysqli_stmt_bind_param($updateStatement, 'di', $registrationPrice, $pricingId);
                mysqli_stmt_execute($updateStatement);
            } else {
                // Pricing record does not exist, insert a new record
                $insertQuery = "INSERT INTO tblpricing (type, currency, relid, monthly, quarterly, semiannually, annually, biennially, triennially) VALUES ('domainregister', 1, ?, -1, -1, -1, ?, -1, -1)";
                $insertStatement = mysqli_prepare($connection, $insertQuery);
                mysqli_stmt_bind_param($insertStatement, 'id', $domainPricingId, $registrationPrice);
                mysqli_stmt_execute($insertStatement);
            }
            
            $pricingQuery = "SELECT id FROM tblpricing WHERE type = 'domainrenew' AND relid = ?";
            $pricingStatement = mysqli_prepare($connection, $pricingQuery);
            mysqli_stmt_bind_param($pricingStatement, 'i', $domainPricingId);
            mysqli_stmt_execute($pricingStatement);
            $pricingResult = mysqli_stmt_get_result($pricingStatement);
            
            if ($pricingRow = mysqli_fetch_assoc($pricingResult)) {
                // Pricing record exists, update the existing record
                $pricingId = $pricingRow['id'];
                $updateQuery = "UPDATE tblpricing SET annually = ? WHERE id = ?";
                $updateStatement = mysqli_prepare($connection, $updateQuery);
                mysqli_stmt_bind_param($updateStatement, 'di', $renewalPrice, $pricingId);
                mysqli_stmt_execute($updateStatement);
            } else {
                // Pricing record does not exist, insert a new record
                $insertQuery = "INSERT INTO tblpricing (type, currency, relid, monthly, quarterly, semiannually, annually, biennially, triennially) VALUES ('domainrenew', 1, ?, -1, -1, -1, ?, -1, -1)";
                $insertStatement = mysqli_prepare($connection, $insertQuery);
                mysqli_stmt_bind_param($insertStatement, 'id', $domainPricingId, $renewalPrice);
                mysqli_stmt_execute($insertStatement);
            }
        } else {
            // Extension does not exist in tbldomainpricing, insert a new record
            $insertQuery = "INSERT INTO tbldomainpricing (extension) VALUES (?)";
            $insertStatement = mysqli_prepare($connection, $insertQuery);
            mysqli_stmt_bind_param($insertStatement, 's', $extension);
            mysqli_stmt_execute($insertStatement);
            
            // Get the last inserted ID
            $domainPricingId = mysqli_insert_id($connection);
            
            // Insert pricing records in tblpricing
            $insertQuery = "INSERT INTO tblpricing (type, currency, relid, monthly, quarterly, semiannually, annually, biennially, triennially) VALUES ('domainregister', 1, ?, -1, -1, -1, ?, -1, -1)";
            $insertStatement = mysqli_prepare($connection, $insertQuery);
            mysqli_stmt_bind_param($insertStatement, 'id', $domainPricingId, $registrationPrice);
            mysqli_stmt_execute($insertStatement);

            $insertQuery = "INSERT INTO tblpricing (type, currency, relid, monthly, quarterly, semiannually, annually, biennially, triennially) VALUES ('domaintransfer', 1, ?, -1, -1, -1, ?, -1, -1)";
            $insertStatement = mysqli_prepare($connection, $insertQuery);
            mysqli_stmt_bind_param($insertStatement, 'id', $domainPricingId, $registrationPrice);
            mysqli_stmt_execute($insertStatement);
            
            $insertQuery = "INSERT INTO tblpricing (type, currency, relid, monthly, quarterly, semiannually, annually, biennially, triennially) VALUES ('domainrenew', 1, ?, -1, -1, -1, ?, -1, -1)";
            $insertStatement = mysqli_prepare($connection, $insertQuery);
            mysqli_stmt_bind_param($insertStatement, 'id', $domainPricingId, $renewalPrice);
            mysqli_stmt_execute($insertStatement);
        }

        // Echo the extension, registration price, and renewal price
        echo "Extension: $extension | Registration Price: $registrationPrice | Renewal Price: $renewalPrice" . PHP_EOL;
    }

    fclose($handle);
}

// Close the database connection
mysqli_close($connection);

echo "Bulk TLD import completed successfully.";