# WHMCS\_bulk\_add\_TLDs.php v0.1.0

This script is designed to add pricing for various top level domains (TLDs) in bulk in WHMCS.

WHMCS\_bulk\_add\TLDs.php is refered to as "the script" for the remainder of this README.

Information regarding how the script works may be found [here](http://blog.seemsgood.com/bulk-adding-new-tlds-in-whmcs).

## To use:

1. Make a backup of your WHMCS database.
2. Upload WHMCS\_bulk\_add\_TLDs.php to your WHMCS admin directory.
3. Create/upload a CSV file called tlds.csv in your WHMCS admin directory. (Details below)
4. Run `php WHMCS_bulk_add_TLDs.php`
5. In WHMCS, confirm that the pricing has been set as desired.

## tlds.csv

A sample tlds.csv may be found at `tlds.csv.SAMPLE`. This file is specifically designed such that each TLD features a different configuration that may be used in TLD pricing.

The formatting for tlds.csv is:

`tld,registration\_price,transfer\_price,renew\_price,minimum\_year,maximum\_year`

All pricing in tlds.csv is on a per-year basis. The price that will be added to WHMCS will be the per-year price multiplied by the number of years being purchased.

To disable domain registrations, set the registration price to `0`.

To disable domain transfers or renewals, set their prices to `-1`.

## Further notes and warnings

(Most of this is a list of things that the script doesn't do)

**Always back up your database before making changes of any kind.**

The current version of this script has only been tested on WHMCS version 6.2.0, which is the current version as of the time of this file's creation.

The script does not check whether a domain already exists in the database.

* If you wish to use the script to update a TLD's pricing, you should delete that TLD's pricing from the WHMCS admin interface first.

Domains that are unavailable for one type of purchase but available for other types will show as available in WHMCS. This occurs when the pricing is set via the WHMCS admin area as well and is therefore not an error with the script.

**Always back up your database before making changes of any kind.**

## I want something changed

* To submit a bug go [here](https://github.com/thecravenone/WHMCS-Bulk-add-TLDs/issues).
* To submit a complaint, submit a pull request that includes a fix for your complaint.


## Credits

WHMCS\_bulk\_add\_TLDs.php was created by Samuel Craven

Donations may be directed to:

* BTC: 18RfhjtmAiqnuCqhrNPWYtTpyyBCLpHhn
* PayPal: [Donate](https://blog.seemsgood.com/paypal-donate/)
