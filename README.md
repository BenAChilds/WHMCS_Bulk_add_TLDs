# TPP Wholesale Domain Pricing CSV Importer for WHMCS

This PHP script reads CSV files (in the TPP Wholesale format) containing domain pricing and imports it directly into a WHMCS database.

## Instructions

1. Place this script in the "admin" folder of your WHMCS installation.
2. Place CSV files in a "csv" subdirectory within the "admin" folder.
3. Run the script from the command line within the "admin" directory by executing `php WHMCS_TPPW_CSV.php`. It will immediately import all records from the CSV file into the WHMCS database.
4. Existing domain extensions will be updated with new pricing, while new extensions will create new records.

Please ensure the accuracy of the CSV files before running this script, as it will immediately import all records.

## Author

This script was created by [Ben Childs](https://github.com/BenAChilds), inspired by [thecravenone](https://github.com/thecravenone/WHMCS_Bulk_add_TLDs)'s work.

## License

This script is licensed under the GNU General Public License version 3 (GNU GPL v3). You are free to modify and distribute this script under the terms of the GNU GPL v3 license. This script is distributed without any warranty. Please see the full text of the [GNU GPL v3 license](https://www.gnu.org/licenses/gpl-3.0.en.html) for more details.
