<?php

// To get number of questions per month:
//
// select extract(year_month FROM date(from_unixtime(creation_date))) month, count(*) from questions group by month;

namespace AppBundle;

use AppBundle\Model\Question;
use AppBundle\BaseService;

class QuestionCrawler extends BaseService {

	private $db_ = null;
	private $api_ = null;

	public function __construct($rootDir, $eloquent, $stackExchangeApi) {
		$this->db_ = $eloquent->connection();
		$this->api_ = $stackExchangeApi;
	}

	public function setOutput($output) {
		$this->api_->setOutput($output);
		parent::setOutput($output);
	}

	public function execute() {
		$fromDate = new \DateTime();

		$lastTimestamp = Question::lastQuestionDate();
		if ($lastTimestamp) {
			$fromDate->setTimestamp($lastTimestamp);
		} else {
			$fromDate->setDate(2008,7,1); // Private beta
		}

		$toDate = clone $fromDate;
		$toDate->add(new \DateInterval('P' . $fromDate->format('t') . 'D'));
		$page = 1;

		while (true) {
			$result = $this->api_->questions($fromDate->getTimestamp(), $toDate->getTimestamp(), $page);

			$this->writeln("Got " . $fromDate->format('Y-m-d') . ' to ' . $toDate->format('Y-m-d') . ', page ' . $page . ". Total items: " . $result['total']);

			// {"error_id":502,"error_message":"too many requests from this IP, more requests available in 32322 seconds","error_name":"throttle_violation"}

			if (isset($result['error_id'])) {
				$this->writeln('');
				$this->writeln('Got error: ' . json_encode($result));
				if (isset($result['error_message'])) {
					preg_match('/available in (.*) seconds/', $result['error_message'], $matches);
					if (isset($matches[1])) {
						$this->writeln('Waiting for ' . gmdate('H\h i\m s\s', $matches[1]) . '...');
						sleep($matches[1] + 1);
						continue;
					} else {
						throw new \Exception('Unexpected error: ' . json_encode($result));
					}
				}
			}

			if (!isset($result['items'])) throw new \Exception('No "items" property on object: ' . json_encode($result));
			$this->saveQuestions($result['items']);

			if (!$result['has_more']) {
				$expectedPageCount = ceil($result['total'] / $this->api_->pageSize());
				if ($page < $expectedPageCount) {
					// Something weird that shouldn't happen but sometime does.
					throw new \Exception('Didn\'t get expected number of pages, but has_more parameter is false: ' . json_encode($result));
				}

				$fromDate = $toDate;
				$toDate = clone $fromDate; //$fromDate + 60 * 60 * 24;
				$toDate->add(new \DateInterval('P' . $fromDate->format('t') . 'D'));
				if ($toDate->getTimestamp() > time() - 60 * 60) {
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
				$this->writeln("Got 'backoff' parameter: " . $result['backoff']);
				sleep($result['backoff'] + 1); // Wait a bit longer than required so as not to be blocked
			}

			sleep(1);
		}
	}

	private function saveQuestions($questions) {
		foreach ($questions as $question) {
			try {
				$m = new Question();
				$m->fromApiArray($question);
				$m->save();
			} catch (\Exception $e) {
				// SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '576908' for key 'PRIMARY'
				// Can happen if doing a Ctrl+C while the questions are being saved, and then resuming the script.
				if ($e->getCode() == 23000) {
					$this->writeln("Skipping question " . $question['question_id'] . ": already saved.");
				} else if ($e->getCode() == 22220) {
					$this->writeln("Skipping question " . $question['question_id'] . ": " . $e->getMessage());
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
					$question->fromApiArray($q);
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


}