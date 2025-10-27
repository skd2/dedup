# dedup
- Command to run the script: php dedup.php leads.json

For the given leads.json input file (also committed to this repo),
the log output is written to a file named log.txt (placed in the same folder as the script),
and the expected output of running the above command is as below:

```
Array
(
    [leads] => Array
        (
            [2] => Array
                (
                    [_id] => wabaj238238jdsnfsj23
                    [email] => bog@bar.com
                    [firstName] => Fran
                    [lastName] => Jones
                    [address] => 8803 Dark St
                    [entryDate] => 2014-05-07T17:31:20+00:00
                )

            [6] => Array
                (
                    [_id] => vug789238jdsnfsj23
                    [email] => foo1@bar.com
                    [firstName] => Blake
                    [lastName] => Douglas
                    [address] => 123 Reach St
                    [entryDate] => 2014-05-07T17:33:20+00:00
                )

            [7] => Array
                (
                    [_id] => wuj08238jdsnfsj23
                    [email] => foo@bar.com
                    [firstName] => Micah
                    [lastName] => Valmer
                    [address] => 123 Street St
                    [entryDate] => 2014-05-07T17:33:20+00:00
                )

            [8] => Array
                (
                    [_id] => belr28238jdsnfsj23
                    [email] => mae@bar.com
                    [firstName] => Tallulah
                    [lastName] => Smith
                    [address] => 123 Water St
                    [entryDate] => 2014-05-07T17:33:20+00:00
                )

            [9] => Array
                (
                    [_id] => jkj238238jdsnfsj23
                    [email] => bill@bar.com
                    [firstName] => John
                    [lastName] => Smith
                    [address] => 888 Mayberry St
                    [entryDate] => 2014-05-07T17:33:20+00:00
                )

        )

)
```

- The log.txt records the elements being removed and why they are being removed, and records the final resultant array.

- If the command is run with incorrect params, expect the below response that shows it's usage:
```
php dedup.php
Error: Could not find leads file at:

Description: This script de-duplicates leads based on _id and email keys
Usage: dedup.php <leads json file path>
  --help, -h  Display this help message.
```