#!/usr/bin/env php
<?php

class Dedup {

	public array $args = [];
	public string $leads_json_file_path = '';
	public string $leads_string = '';
	public string $log_file_path = 'log.txt';

	public function __construct($argv) {
		if (empty($argv) || in_array('--help', $argv) || in_array('-h', $argv)) {
			$this->print_help();
		}

		$this->args = $argv;
		array_shift($this->args); // Remove script name
		$this->leads_json_file_path = $this->args[0] ?? '';
	}

	public function print_help() {
		echo PHP_EOL . 'Description: This script de-duplicates leads based on _id and email keys' . PHP_EOL;
		echo 'Usage: ' . basename($GLOBALS['argv'][0]) . ' <leads json file path> ' . PHP_EOL;
		echo '  --help, -h  Display this help message.' . PHP_EOL;
		exit(0);
	}

	/**
	 * Runs basic checks if the script should run or not. And stores data into memory from the input file
	 */
	public function prelim_checks() {
		if (!file_exists($this->leads_json_file_path)) {
			fputs(STDERR, 'Error: Could not find leads file at: ' . $this->leads_json_file_path . PHP_EOL);
			$this->print_help();
			exit(1);
		}

		if ($this->write_log('Script started') === false) {
			fputs(STDERR, "Error: Could not write to " . $this->log_file_path . PHP_EOL);
			$this->print_help();
			exit(1);
		}


		if (!($this->leads_string = file_get_contents($this->leads_json_file_path))) {
			fputs(STDERR, 'Error: Could not read leads file at: ' . $this->leads_json_file_path . PHP_EOL);
			$this->print_help();
			exit(1);
		}

		$this->leads_array = json_decode($this->leads_string, true);
		if (empty($this->leads_array)) {
			fputs(STDERR, 'Error: Leads Json did not decode into valid array. Error:' . json_last_error_msg() . PHP_EOL);
			exit(1);
		}
	}

	/**
	 * Main method to process the leads json structure's data
	 * 
	 * @return array $final The final result to be returned after deduplication process completes
	 */
	public function process_leads() {
		$final = $this->leads_array;

		$temp_result_id = $temp_result_email = [];
		foreach ($this->leads_array['leads'] as $key => $element) {
			$id = $element['_id'];
			$email = $element['email'];
			
			[$temp_result_id, $final] = $this->dedup_helper('_id', $id, $key, $element, $temp_result_id, $final);
			[$temp_result_email, $final] = $this->dedup_helper('email', $email, $key, $element, $temp_result_email, $final);
		}

		$this->write_log('Script ended', $final);
		return $final;
	}

	/**
	 * The helper method that actually checks for duplicate _id or email key values and removed the duplicate
	 * value based on the older timestamp or the element that appears earlier if the timestamps are the same
	 * 
	 * @param string $id_type whether it is _id or email field to check against for duplicate values
	 * @param string $id_value The associated value for the _id or email key
	 * @param int $lead_index The numeric index value of the current element being processed
	 * @param array $lead_element The array value of the current element being processed
	 * @param array $temp_result The intermediate array to store the _id value or _email value to check for duplicates in subsequent calls
	 * @param array $final The array that is initially a copy of the main $this->leads_array, and unneeded keys are undet, resulting in the final array as a response
	 * 
	 * @return array 
	 */
	private function dedup_helper($id_type, $id_value, $lead_index, $lead_element, $temp_result, $final) {
		if (isset($temp_result[$id_value])) { // To check for duplicate _id or email value
			if (strtotime($temp_result[$id_value]['entryDate']) <= strtotime($lead_element['entryDate'])) {
				// This block is to check for the timestamp to see if the prior element's timestamp is
				// older or equal to the latter one. If so keep the latter one and remove the older one
				$this->write_log(sprintf('Removing leads index %d as newer version of %s exists at index %d. Removed contents were:', $temp_result[$id_value]['index'], $id_type, $lead_index), $this->leads_array['leads'][$temp_result[$id_value]['index']]);
				unset($final['leads'][$temp_result[$id_value]['index']]);
				$lead_element['index'] = $lead_index;
				$temp_result[$id_value] = $lead_element;
			} else {
				// This block is to check for the timestamp to see if the prior element's timestamp is
				// greater to the latter one. If so keep the older one and remove the latter one
				unset($final['leads'][$lead_index]);
			}
		} else {
			$lead_element['index'] = $lead_index; // Preserve the original numeric index, to be used to unset if apt.
			$temp_result[$id_value] = $lead_element;
		}

		return [$temp_result, $final];
	}

	private function get_timestamp() {
		return date('Y-m-d H:i:s T');
	}

	/**
	 * Writes log messages to a file. Prefixes timestamp by default. Returns boolean if the Write to file succeeds
	 * 
	 * @param string $message String message to be written to the file
	 * @param array $details Optional additional details to be appended to the log message
	 * 
	 * @return bool
	 */
	private function write_log($message, $details = []) {
		if (!empty($details)) {
			$details = var_export($details, true);
		} else {
			$details = '';
		}
		$text = sprintf('%s: %s %s', $this->get_timestamp(), $message, $details . PHP_EOL);
		return file_put_contents($this->log_file_path, $text, FILE_APPEND);
	}
}

// Start the dedup process
$dedup = new Dedup($argv);
$dedup->prelim_checks();
$final = $dedup->process_leads();
print_r($final);
exit(0);
