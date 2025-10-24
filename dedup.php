#!/usr/bin/env php
<?php

// Argument parsing (basic example)
$args = $argv;
array_shift($args); // Remove script name

if (empty($args) || in_array('--help', $args) || in_array('-h', $args)) {
    echo 'Description: This script de-duplicates leads based on _id and email keys' . PHP_EOL;
    echo 'Usage: ' . basename($argv[0]) . ' <leads json file path> ' . PHP_EOL;
    echo '  --help, -h  Display this help message.' . PHP_EOL;
    exit(0);
}

// Main script logic
$leads_json_file_path = $args[0];
echo 'Received path as: ' . $leads_json_file_path . PHP_EOL;

if (!file_exists($leads_json_file_path)) {
    fputs(STDERR, 'Error: Could not find file at: ' . $leads_json_file_path . PHP_EOL);
    exit(1);
}

// Example of writing to a file
$outputFile = 'log.txt';
if (file_put_contents($outputFile, "Processed data for: " . $argument1 . "\n") === false) {
    fputs(STDERR, "Error: Could not write to " . $outputFile . "\n");
    exit(1);
}



if (!($leads_string = file_get_contents($leads_json_file_path))) {
    fputs(STDERR, 'Error: Could not read file at: ' . $leads_json_file_path . PHP_EOL);
    exit(1);
}

$final = $leads_array = json_decode($leads_string, true);

$result_id = $result_email = [];
foreach ($leads_array['leads'] as $key => $element) {
	$id = $element['_id'];
	$email = $element['email'];
	if (isset($result_id[$id])) {
		if (strtotime($result_id[$id]['entryDate']) <= strtotime($element['entryDate'])) {
			unset($final['leads'][$result_id[$id]['index']]);
			$element['index'] = $key;
			$result_id[$id] = $element;
		} else {
			unset($final['leads'][$key]);
		}
	} else {
		$element['index'] = $key;
		$result_id[$id] = $element;
	}
	if (isset($result_email[$email])) {
		if (strtotime($result_email[$email]['entryDate']) <= strtotime($element['entryDate'])) {
			unset($final['leads'][$result_email[$email]['index']]);
			$element['index'] = $key;
			$result_email[$email] = $element;
		} else {
			unset($final['leads'][$key]);
		}
	} else {
		$element['index'] = $key;
		$result_email[$email] = $element;
	}
}

print_r($final);
print count($final['leads']) . PHP_EOL;

echo 'Script finished successfully.' . PHP_EOL;
exit(0);
