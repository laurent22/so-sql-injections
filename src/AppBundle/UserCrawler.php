<?php

namespace AppBundle;

use AppBundle\Model\Question;
use AppBundle\Model\User;
use AppBundle\BaseService;

class UserCrawler extends BaseService {

	private $db_ = null;
	private $api_ = null;

	public function __construct($eloquent, $stackExchangeApi) {
		$this->db_ = $eloquent->connection();
		$this->api_ = $stackExchangeApi;
	}

	public function execute() {
		$offset = 0;
		$limit = 100;

		while (true) {
			$questions = Question::orderBy('question_id', 'asc')
			                     ->limit($limit)
			                     ->offset($offset)
			                     ->get();

			if (!count($questions)) break;

			$this->db_->beginTransaction();
			try {
				$userIds = array();
				foreach ($questions as $question) {
					if (!(int)$question->owner_id) continue;
					$userIds[] = $question->owner_id;
				}
				$userIds = array_unique($userIds);
				$users = User::findMany($userIds);
				$existingUserIds = array();
				foreach ($users as $user) $existingUserIds[] = $user->user_id;

				$userIds = array_diff($userIds, $existingUserIds);

				$results = $this->api_->users($userIds);
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

			if (isset($result['backoff'])) {
				$this->writeln("Got 'backoff' parameter: " . $result['backoff']);
				sleep($result['backoff'] + 1); // Wait a bit longer than required so as not to be blocked
			}

			sleep(5);

			$offset += $limit;
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