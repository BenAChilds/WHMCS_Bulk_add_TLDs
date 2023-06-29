<?php

require_once('../configuration.php');

// Function to parse CSV file and generate SQL statements
function parseCSV($csvFile, $dbConnection) {
    // Variable to store SQL statements
    $sql = "";

    // Read the CSV file
    if (($handle = fopen($csvFile, "r")) !== false) {
        // Discard the first 5 rows
        for ($i = 0; $i < 5; $i++) {
            fgetcsv($handle);
        }

        // Skip the header row
        fgetcsv($handle);

        // Loop through the remaining rows
        while (($row = fgetcsv($handle)) !== false) {
            $tld = trim($row[0]);
            $registrationPrice = trim($row[3]);
            $renewalPrice = trim($row[6]);

            // Skip rows without TLD or rows without pricing information
            if (empty($tld) || empty($registrationPrice) || empty($renewalPrice)) {
                continue;
            }

            // Generate SQL statement for tbldomainpricing table
            $dbConnection->query("INSERT INTO tbldomainpricing (extension, dnsmanagement, emailforwarding, idprotection, eppcode, autoreg, grace_period, redemption_grace_period, redemption_grace_period_fee, grace_period_fee) VALUES ('$tld', 0, 0, 0, 0, 1, -1, -1, -1, 0)");

            // Get the last inserted ID
            $lastInsertIdResult = $dbConnection->query("SELECT LAST_INSERT_ID()");
            $lastInsertIdRow = $lastInsertIdResult->fetch_row();
            $lastInsertId = $lastInsertIdRow[0];
            $lastInsertIdResult->close();

            // Generate SQL statement for tblpricing table (domainregister)
            $dbConnection->query("INSERT INTO tblpricing (type, currency, relid, msetupfee, qsetupfee, ssetupfee, asetupfee, bsetupfee, tsetupfee, monthly, quarterly, semiannually, annually, biennially, triennially) VALUES ('domainregister', 1, $lastInsertId, -1, -1, -1, -1, -1, -1, -1, -1, -1, $registrationPrice, -1, -1)");

            // Generate SQL statement for tblpricing table (domaintransfer)
            $dbConnection->query("INSERT INTO tblpricing (type, currency, relid, msetupfee, qsetupfee, ssetupfee, asetupfee, bsetupfee, tsetupfee, monthly, quarterly, semiannually, annually, biennially, triennially) VALUES ('domaintransfer', 1, $lastInsertId, -1, -1, -1, -1, -1, -1, -1, -1, -1, $registrationPrice, -1, -1)");

            // Generate SQL statement for tblpricing table (domainrenew)
            $dbConnection->query("INSERT INTO tblpricing (type, currency, relid, msetupfee, qsetupfee, ssetupfee, asetupfee, bsetupfee, tsetupfee, monthly, quarterly, semiannually, annually, biennially, triennially) VALUES ('domainrenew', 1, $lastInsertId, -1, -1, -1, -1, -1, -1, -1, -1, -1, $renewalPrice, -1, -1)");
        }

        fclose($handle);
    }

    return $sql;
}

$csvDirectory = "./csv/";
$sqlStatements = "";

// Create a new database connection
$dbConnection = new mysqli($db_host, $db_username, $db_password, $db_name, $db_port ?: 3306);

// Check if the connection was successful
if ($dbConnection->connect_error) {
    die("Database connection failed: " . $dbConnection->connect_error);
}

// Find all CSV files in the directory
if (is_dir($csvDirectory)) {
    $csvFiles = glob($csvDirectory . "*.csv");

    foreach ($csvFiles as $csvFile) {
        echo "Processing file: $csvFile\n";
        $sqlStatements .= parseCSV($csvFile, $dbConnection);
        echo "Done processing file: $csvFile\n";
    }
}

// Close the database connection
$dbConnection->close();

echo "All CSV files processed.\n";
echo "SQL statements:\n";
echo $sqlStatements;
