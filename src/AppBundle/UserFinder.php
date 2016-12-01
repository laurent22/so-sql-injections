<?php

namespace AppBundle;

use AppBundle\Model\Question;
use AppBundle\Model\User;
use AppBundle\BaseService;

class UserFinder extends BaseService {

	private $db_ = null;
	private $api_ = null;
	private $userIds_ = array();

	public function __construct($eloquent, $stackExchangeApi) {
		$this->db_ = $eloquent->connection();
		$this->api_ = $stackExchangeApi;
	}

	public function execute() {
		$limit = 1000;
		$lastId = 0;

		while (true) {
			$ownerIds = Question::ownerIdsWithNoUserObject($lastId, $limit);
			if (!count($ownerIds)) break;
			$this->processUsers($ownerIds);
			if (count($ownerIds) <= 1) break;
			$lastId = $ownerIds[count($ownerIds) - 1];			
		}

		$this->processAllUsers();
	}

	private function processAllUsers() {
		$this->processUsers('all');
	}

	private function processUsers($userIds) {
		$allUsers = $userIds === 'all';
		if (!count($userIds) && !$allUsers) return;

		$bufferSize = 100;

		if (!$allUsers) {
			$this->userIds_ = array_merge($this->userIds_, $userIds);
			$this->userIds_ = array_unique($this->userIds_);
		}
		
		while (count($this->userIds_) >= $bufferSize) {
			$d = array_slice($this->userIds_, 0, $bufferSize);
			$this->userIds_ = array_slice($this->userIds_, $bufferSize);

			$results = array();
			while (true) {
				$results = $this->api_->users($d);
				if (isset($results['backoff'])) {
					$this->writeln("Got 'backoff' parameter: " . $results['backoff']);
					sleep($results['backoff'] + 1); // Wait a bit longer than required so as not to be blocked
					continue;
				}
				break;
			}

			if (!isset($results['items'])) throw new \Exception('Missing "items" property: ' . json_encode($results));

			$this->db_->beginTransaction();

			try {
				foreach ($results['items'] as $result) {
					$user = new User();
					$user->fromApiArray($result);
					$user->save();
				}
			} catch (\Exception $e) {
				$this->db_->rollback();
				throw $e;
			}

			$this->db_->commit();

			sleep(1);
		}
	}

	public function refreshUsers() {
		$limit = 10000;
		$offset = 0;
		while (true) {
			$users = User::orderBy('user_id', 'asc')
			                     ->limit($limit)
			                     ->offset($offset)
			                     ->get();

			if (!count($users)) break;

			$this->db_->beginTransaction();
			try {
				foreach ($users as $user) {
					$u = json_decode($user->raw_json, true);
					$user->fromApiArray($u);
					$user->save();
				}
			} catch (\Exception $e) {
				$this->db_->rollback();
				throw $e;
			}
			$this->db_->commit();

			$offset += $limit;
		}
	}


}