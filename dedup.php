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

$dedup = new Dedup($argv);
$dedup->prelim_checks();
$final = $dedup->process_leads();
print_r($final);
exit(0);
