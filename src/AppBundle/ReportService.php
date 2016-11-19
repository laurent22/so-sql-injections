<?php

// - Latest injections
// - Injections over time

namespace AppBundle;

use AppBundle\Model\Question;

class ReportService {

	private $db_ = null;

	public function __construct($eloquent) {
		$this->db_ = $eloquent->connection();
	}

	public function monthlyInjections() {
		$results = $this->db_->getPdo()->query('
			SELECT
				extract(year_month FROM date(from_unixtime(creation_date))) month,
				count(CASE WHEN has_sql = 1 THEN 1 END) has_sql_count,
				count(CASE WHEN has_sql_injection = 1 THEN 1 END) has_sql_injection_count
			FROM questions WHERE has_sql = 1 GROUP BY month
		');
		return $results->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function latestInjections() {
		$results = $this->db_->getPdo()->query('
			SELECT *
			FROM questions
			WHERE has_sql_injection = 1
			ORDER BY creation_date DESC
			LIMIT 50
		');

		$rows = $results->fetchAll(\PDO::FETCH_ASSOC);

		$output = array();
		foreach ($rows as $row) {
			$lines = Question::bodyToLines($row['body_markdown']);
			if (count($lines) <= $row['sql_injection_line']) continue; // shouldn't happen
			$line = $lines[$row['sql_injection_line']];
			$output[] = array(
				'line' => trim($line),
				'creation_date' => $row['creation_date'],
			);
		}

		return $output;
	}

}