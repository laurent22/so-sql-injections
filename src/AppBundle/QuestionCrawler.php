<?php

namespace AppBundle;

use AppBundle\Model\Question;
use AppBundle\BaseService;

class QuestionCrawler extends BaseService {

	private $settingPath_;
	private $db_ = null;
	private $api_ = null;

	public function __construct($rootDir, $eloquent, $stackExchangeApi) {
		$this->settingPath_	= dirname($rootDir) . '/var/crawler_settings.json';
		$this->db_ = $eloquent->connection();
		$this->api_ = $stackExchangeApi;
	}

	public function execute() {
		$settings = $this->loadSettings();

		if (!isset($settings['fromDate'])) {
			$d = new DateTime();
			$d->setDate(2008,07,31); // Private beta
			//$d->setDate(2008,9,15); // Public beta
			$d->setTime(0,0,0);
			$settings['fromDate'] = $d->getTimestamp();
		}

		if (!isset($settings['page'])) {
			$settings['page'] = 0;
		}

		if (!isset($settings['hasMore'])) {
			$settings['hasMore'] = true;
		}

		$fromDate = $settings['fromDate'];
		$toDate = $fromDate + 60 * 60 * 24;
		$page = $settings['page'];

		if (!$settings['hasMore']) {
			$fromDate = $toDate;
			$toDate = $fromDate + 60 * 60 * 24;
			$page = 1;
		} else {
			$page++;
		}

		while (true) {
			$this->write("Processing " . date('Y-m-d', $fromDate) . ' to ' . date('Y-m-d', $toDate) . ', page ' . $page . "... ");

			$result = $this->api_->questions($fromDate, $toDate, $page);
			if (!isset($result['items'])) throw new \Exception('No "items" property on object: ' . json_encode($result));
			$this->saveQuestions($result['items']);
			$settings['fromDate'] = $fromDate;
			$settings['toDate'] = $toDate;
			$settings['page'] = $page;
			$settings['hasMore'] = $result['has_more'];

			$this->saveSettings($settings);

			$this->writeln('OK');

			if (!$result['has_more']) {
				$fromDate = $toDate;
				$toDate = $fromDate + 60 * 60 * 24;
				if ($toDate > time() - 60 * 60) {
					// Don't process very recent questions, because they often have issues like wrong tags
					// or bad formatting (or they are questions that are going to be deleted). These issues are
					// fixed relatively quickly, so one hour delay should be enough.
					$this->writeln("Done all current questions");
					break;
				}
				$page = 1;
			} else {
				$page++;
			}

			if (isset($result['backoff'])) {
				$this->writeln("Got 'backoff' parameter: " . json_encode($result));
				sleep($result['backoff'] + 1); // Wait a bit longer than required so as not to be blocked
			}

			sleep(1);
		}
	}

	private function saveQuestions($questions) {
		foreach ($questions as $question) {
			try {
				$m = new Question();
				$m->question_id = $question['question_id'];
				$m->body_markdown = $question['body_markdown'];
				$m->creation_date = $question['creation_date'];
				$m->raw_json = json_encode($question);
				$m->save();
			} catch (\Illuminate\Database\QueryException $e) {
				// SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '576908' for key 'PRIMARY'
				// Can happen if doing a Ctrl+C while the questions are being saved, and then resuming the script.
				if ($e->getCode() == 23000) {
					$this->writeln("Skipping question " . $question['question_id'] . ": already saved.");
				} else {
					throw $e;
				}
			}
		}
	}

	// Loop through all the questions and update the database fields based on the stored
	// raw JSON. Useful whenever a new field is added to the database table.
	public function refreshQuestions() {
		$limit = 10000;
		$offset = 0;
		while (true) {
			$questions = Question::orderBy('question_id', 'asc')
			                     ->limit($limit)
			                     ->offset($offset)
			                     ->get();

			if (!count($questions)) break;

			$this->db_->beginTransaction();
			try {
				foreach ($questions as $question) {
					$q = json_decode($question->raw_json, true);
					$question->owner_id = isset($q['owner']['user_id']) ? $q['owner']['user_id'] : 0;
					$question->save();
				}
			} catch (\Exception $e) {
				$this->db_->rollback();
				throw $e;
			}
			$this->db_->commit();

			$offset += $limit;
		}
	}

	private function loadSettings() {
		$d = @file_get_contents($this->settingPath_);
		if ($d === false) return array();
		return json_decode($d, true);
	}

	private function saveSettings($settings) {
		file_put_contents($this->settingPath_, json_encode($settings));
	}


}