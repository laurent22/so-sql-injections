<?php

namespace AppBundle;

class StackExchangeApi {

	private $key_ = 'BmopG)d9Thccirg4e)CjOw(('; // This is public information

	public function __construct() {

	}

	private function executeQuery($path, $query) {
		$query['key'] = $this->key_;
		$query['site'] = 'stackoverflow';

		$baseUrl = 'https://api.stackexchange.com/2.2';

		$url = $baseUrl . '/' . $path . '?' . http_build_query($query);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_ENCODING, "gzip");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($ch);
		curl_close($ch);

		$d = json_decode($data, true);
		if ($d === false) throw new Exception('Could not decode JSON: ' . $data);
		return $d;
	}

	public function questions($fromDate, $toDate, $page) {
		$query = array();
		$query['filter'] = '!9YdnSJ*_T';
		$query['tagged'] = 'php';
		$query['fromdate'] = $fromDate;
		$query['todate'] = $toDate;
		$query['page'] = $page;

		return $this->executeQuery('questions', $query);
	}

	public function users($ids) {
		$query = array();
		$query['filter'] = '!LnO)*RBjDUSz2sWDlSDTDB';

		$path = 'users/' . implode(';', $ids);

		return $this->executeQuery($path, $query);
	}

}