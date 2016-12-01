<?php

namespace AppBundle\Model;

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Support\Facades\DB;

class Question extends \Illuminate\Database\Eloquent\Model {

	protected $primaryKey = 'question_id';

	public function fromApiArray($a) {
		if (!isset($a['body_markdown'])) throw new \Exception('Question without a body_markdown property: ' . json_encode($a), 22220);

		$this->question_id = $a['question_id'];
		$this->body_markdown = $a['body_markdown'];
		$this->creation_date = $a['creation_date'];
		$this->owner_id = isset($a['owner']['user_id']) ? $a['owner']['user_id'] : 0;
		$this->raw_json = json_encode($a);
	}

	static public function bodyToLines($body) {
		$body = str_replace("\r\n", "\n", $body);
		$body = str_replace("\r", "\n", $body);
		$lines = explode("\n", $body);
		$output = array();
		foreach ($lines as $line) {
			$output[] = mb_convert_encoding($line, 'UTF-8', 'HTML-ENTITIES');
		}
		return $output;
	}

	static public function earliestQuestionDate() {
		return self::min('creation_date');
	}

	static public function lastQuestionDate() {
		return self::max('creation_date');
	}

	// Questions that have an associated user ID but
	// no correspondance in the users table.
	static public function ownerIdsWithNoUserObject($minId, $limit) {
		$con = (new self())->getConnection();
		$results = $con->getPdo()->query(sprintf('
			SELECT DISTINCT owner_id
			FROM questions
			LEFT JOIN users ON questions.owner_id = users.user_id
			WHERE
				owner_id != 0
				AND users.raw_json IS NULL
				AND question_id > %s
			LIMIT %s
		', (int)$minId, (int)$limit));

		$r = $results->fetchAll(\PDO::FETCH_ASSOC);
		$output = array();
		foreach ($r as $v) $output[] = $v['owner_id'];
		return $output;
	}

}