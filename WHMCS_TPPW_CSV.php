<?php

/**
 * TPP Wholesale Domain Pricing CSV Importer for WHMCS
 *
 * This PHP script reads CSV files (in the TPP Wholesale format) containing domain pricing
 * and imports it directly into a WHMCS database.
 *
 * Instructions:
 * 1. Place this script in the "admin" folder of your WHMCS installation.
 * 2. Place CSV files in a "csv" subdirectory within the "admin" folder.
 * 3. Run the script from the command line within the "admin" directory by executing "php WHMCS_TPPW_CSV.php".
 *    It will immediately import all records from the CSV file into the WHMCS database.
 * 4. Existing domain extensions will be updated with new pricing, while new extensions will create new records.
 *
 * Please ensure the accuracy of the CSV files before running this script, as it will immediately import all records.
 *
 * @author Ben Childs (https://github.com/BenAChilds)
 * @license GNU GPL v3
 * @version 1.0.0
 *
 * TERMS OF USE:
 * - This script is licensed under the GNU General Public License version 3 (GNU GPL v3).
 * - You are free to modify and distribute this script under the terms of the GNU GPL v3 license.
 * - This script is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
 * - Please see the full text of the GNU GPL v3 license for more details.
 * - You should have received a copy of the GNU GPL v3 license along with this script.
 * - If not, please visit https://www.gnu.org/licenses/gpl-3.0.en.html to obtain a copy.
 *
 */

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
    $csvData = array_map('str_getcsv', file($csvFile));

    // Start the transaction
    mysqli_begin_transaction($connection);

    try {
        // Process each line in the CSV file
        foreach ($csvData as $row => $data) {
            // Skip the first 5 lines
            if ($row < 5) {
                continue;
            }

            // Extract the necessary data from the CSV row
            if (!$data[0] == '' || $data[0]) {
                // This is a new TLD
                $extension = trim($data[0]);
                
                // Get the registration & transfer prices from the next line
                $registrationPrices = array_slice($csvData[$row + 1], 3);
                foreach ($registrationPrices as $key => $reg) {
                    // Replace any empty strings with 0 to imply the period is disabled
                    $registrationPrices[$key] = !empty($reg) ? $reg : 0;
                }

                // Get renewal prices from 2 lines below
                $renewalPrices = array_slice($csvData[$row + 4], 3);
                foreach ($renewalPrices as $key => $reg) {
                    // Replace any empty strings with 0 to imply the period is disabled
                    $renewalPrices[$key] = !empty($reg) ? $reg : 0;
                }

                // Check if extension exists in tbldomainpricing
                $extensionExistsQuery = "SELECT id FROM tbldomainpricing WHERE extension = '$extension'";
                $extensionExistsResult = mysqli_query($connection, $extensionExistsQuery);

                if ($extensionExistsResult && mysqli_num_rows($extensionExistsResult) > 0) {
                    // Extension exists, get the relid for tblpricing
                    $recordRow = mysqli_fetch_assoc($extensionExistsResult);
                    $relid = $recordRow['id'];

                    // Check if the record exists in tblpricing for the given type and relid
                    $existingRecordQuery = "SELECT id FROM tblpricing WHERE type IN ('domainregister', 'domaintransfer', 'domainrenew') AND relid = $relid";
                    $existingRecordResult = mysqli_query($connection, $existingRecordQuery);

                    if ($existingRecordResult && mysqli_num_rows($existingRecordResult) > 0) {
                        // Update existing record in tblpricing
                        mysqli_query($connection, "UPDATE tblpricing SET msetupfee = '$registrationPrices[0]', qsetupfee = '$registrationPrices[1]', ssetupfee = '$registrationPrices[2]', asetupfee = '$registrationPrices[3]', bsetupfee = '$registrationPrices[4]', tsetupfee = 0, monthly = '$registrationPrices[5]', quarterly = '$registrationPrices[6]', semiannually = '$registrationPrices[7]', annually = '$registrationPrices[8]', biennially = '$registrationPrices[9]', triennially = '0.00' WHERE type = 'domainregister' AND relid = $relid");
                        mysqli_query($connection, "UPDATE tblpricing SET msetupfee = '$registrationPrices[0]', qsetupfee = '$registrationPrices[1]', ssetupfee = '$registrationPrices[2]', asetupfee = '$registrationPrices[3]', bsetupfee = '$registrationPrices[4]', tsetupfee = 0, monthly = '$registrationPrices[5]', quarterly = '$registrationPrices[6]', semiannually = '$registrationPrices[7]', annually = '$registrationPrices[8]', biennially = '$registrationPrices[9]', triennially = '0.00' WHERE type = 'domaintransfer' AND relid = $relid");
                        mysqli_query($connection, "UPDATE tblpricing SET msetupfee = '$renewalPrices[0]', qsetupfee = '$renewalPrices[1]', ssetupfee = '$renewalPrices[2]', asetupfee = '$renewalPrices[3]', bsetupfee = '$renewalPrices[4]', tsetupfee = 0, monthly = '$renewalPrices[5]', quarterly = '$renewalPrices[6]', semiannually = '$renewalPrices[7]', annually = '$renewalPrices[8]', biennially = '$renewalPrices[9]', triennially = '0.00' WHERE type = 'domainrenew' AND relid = $relid");
                    } else {
                        // Insert new records in tblpricing
                        mysqli_query($connection, "INSERT INTO tblpricing (`type`, `currency`, `relid`, `msetupfee`, `qsetupfee`, `ssetupfee`, `asetupfee`, `bsetupfee`, `tsetupfee`, `monthly`, `quarterly`, `semiannually`, `annually`, `biennially`, `triennially`)
                                        VALUES ('domainregister', '1', $relid, '$registrationPrices[0]', '$registrationPrices[1]', '$registrationPrices[2]', '$registrationPrices[3]', '$registrationPrices[4]', 0, '$registrationPrices[5]', '$registrationPrices[6]', '$registrationPrices[7]', '$registrationPrices[8]', '$registrationPrices[9]', 0.00)");
                        mysqli_query($connection, "INSERT INTO tblpricing (`type`, `currency`, `relid`, `msetupfee`, `qsetupfee`, `ssetupfee`, `asetupfee`, `bsetupfee`, `tsetupfee`, `monthly`, `quarterly`, `semiannually`, `annually`, `biennially`, `triennially`)
                                        VALUES ('domaintransfer', '1', $relid, '$registrationPrices[0]', '$registrationPrices[1]', '$registrationPrices[2]', '$registrationPrices[3]', '$registrationPrices[4]', 0, '$registrationPrices[5]', '$registrationPrices[6]', '$registrationPrices[7]', '$registrationPrices[8]', '$registrationPrices[9]', 0.00)");
                        mysqli_query($connection, "INSERT INTO tblpricing (`type`, `currency`, `relid`, `msetupfee`, `qsetupfee`, `ssetupfee`, `asetupfee`, `bsetupfee`, `tsetupfee`, `monthly`, `quarterly`, `semiannually`, `annually`, `biennially`, `triennially`)
                                        VALUES ('domainrenew', '1', $relid, '$renewalPrices[0]', '$renewalPrices[1]', '$renewalPrices[2]', '$renewalPrices[3]', '$renewalPrices[4]', 0, '$renewalPrices[5]', '$renewalPrices[6]', '$renewalPrices[7]', '$renewalPrices[8]', '$renewalPrices[9]', 0.00)");
                    }

                    // Echo the extension, registration price, and renewal prices
                    echo "Extension: $extension" . PHP_EOL;
                    echo "Registration Price: " . implode(', ', $registrationPrices) . PHP_EOL;
                    echo "Renewal Prices: " . implode(', ', $renewalPrices) . PHP_EOL;
                    echo PHP_EOL;
                } else {
                    // Create new
                    $newRecordResult = mysqli_query($connection, "INSERT INTO tbldomainpricing (`extension`, `dnsmanagement`, `emailforwarding`, `idprotection`, `eppcode`, `autoreg`, `order`, `group`, `grace_period`, `grace_period_fee`, `redemption_grace_period`, `redemption_grace_period_fee`, `created_at`, `updated_at`)
                                        VALUES ('$extension', 1, 1, 0, 1, 1, '0', 'none', -1, '0', -1, -1.00, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                    $relid = mysqli_insert_id($connection);

                    // Insert new records in tblpricing
                    mysqli_query($connection, "INSERT INTO tblpricing (`type`, `currency`, `relid`, `msetupfee`, `qsetupfee`, `ssetupfee`, `asetupfee`, `bsetupfee`, `tsetupfee`, `monthly`, `quarterly`, `semiannually`, `annually`, `biennially`, `triennially`)
                                        VALUES ('domainregister', '1', '$relid', '$registrationPrices[0]', '$registrationPrices[1]', '$registrationPrices[2]', '$registrationPrices[3]', '$registrationPrices[4]', 0, '$registrationPrices[5]', '$registrationPrices[6]', '$registrationPrices[7]', '$registrationPrices[8]', '$registrationPrices[9]', 0.00)");
                    mysqli_query($connection, "INSERT INTO tblpricing (`type`, `currency`, `relid`, `msetupfee`, `qsetupfee`, `ssetupfee`, `asetupfee`, `bsetupfee`, `tsetupfee`, `monthly`, `quarterly`, `semiannually`, `annually`, `biennially`, `triennially`)
                                        VALUES ('domaintransfer', '1', '$relid', '$registrationPrices[0]', '$registrationPrices[1]', '$registrationPrices[2]', '$registrationPrices[3]', '$registrationPrices[4]', 0, '$registrationPrices[5]', '$registrationPrices[6]', '$registrationPrices[7]', '$registrationPrices[8]', '$registrationPrices[9]', 0.00)");
                    mysqli_query($connection, "INSERT INTO tblpricing (`type`, `currency`, `relid`, `msetupfee`, `qsetupfee`, `ssetupfee`, `asetupfee`, `bsetupfee`, `tsetupfee`, `monthly`, `quarterly`, `semiannually`, `annually`, `biennially`, `triennially`)
                                        VALUES ('domainrenew', '1', '$relid', '$renewalPrices[0]', '$renewalPrices[1]', '$renewalPrices[2]', '$renewalPrices[3]', '$renewalPrices[4]', 0, '$renewalPrices[5]', '$renewalPrices[6]', '$renewalPrices[7]', '$renewalPrices[8]', '$renewalPrices[9]', 0.00)");

                    // Echo the extension, registration price, and renewal prices
                    echo "Extension: $extension" . PHP_EOL;
                    echo "Registration Price: " . implode(', ', $registrationPrices) . PHP_EOL;
                    echo "Renewal Prices: " . implode(', ', $renewalPrices) . PHP_EOL;
                    echo PHP_EOL;
                }
            } else {
                continue;
            }
        }

        // Commit the transaction if the query executed successfully
        mysqli_commit($connection);

        echo "Transaction committed successfully!" . PHP_EOL;
    } catch (Exception $e) {
        // Rollback the transaction if an error occurred
        mysqli_rollback($connection);

        echo "Transaction rolled back. Error: " . $e->getMessage() . PHP_EOL;
    }
}

// Close the database connection
mysqli_close($connection);

echo "Bulk TLD import completed successfully." . PHP_EOL;
