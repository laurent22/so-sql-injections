<?php

namespace AppBundle;

class StackExchangeApi extends BaseService {

	private $key_ = 'BmopG)d9Thccirg4e)CjOw(('; // This is public information
	private $pageSize_ = 100;

	public function pageSize() {
		return $this->pageSize_;
	}

	private function executeQuery($path, $query) {
		$query['key'] = $this->key_;
		$query['site'] = 'stackoverflow';

		$baseUrl = 'https://api.stackexchange.com/2.2';

		$url = $baseUrl . '/' . $path . '?' . http_build_query($query);

		$this->writeln($url);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_ENCODING, "gzip");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($ch);
		curl_close($ch);

		$d = json_decode($data, true);
		if ($d === false) throw new \Exception('Could not decode JSON: ' . $data);
		return $d;
	}

	public function questions($fromDate, $toDate, $page) {
		$query = array();
		$query['filter'] = '!-*f(6rkvD-tO';
		$query['tagged'] = 'php';
		$query['fromdate'] = $fromDate;
		$query['todate'] = $toDate;
		$query['page'] = $page;
		$query['pagesize'] = $this->pageSize();

		return $this->executeQuery('questions', $query);
	}

	public function users($ids) {
		if (!count($ids)) throw new \Exception('No user IDs specified');
		$query = array();
		$query['filter'] = '!LnO)*RBjDUSz2sWDlSDTDB';
		$query['pagesize'] = $this->pageSize();

		$path = 'users/' . implode(';', $ids);

		return $this->executeQuery($path, $query);
	}

}
