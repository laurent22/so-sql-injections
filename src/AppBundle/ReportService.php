<?php

namespace AppBundle;

use AppBundle\Model\Question;
use AppBundle\Model\Country;
use AppBundle\BaseService;

class ReportService extends BaseService {

	private $db_ = null;
	private $cache_ = null;

	public function __construct($eloquent, $cache) {
		$this->db_ = $eloquent->connection();
		$this->cache_ = $cache;
	}

	public function monthlyInjections() {
		$d1 = new \DateTime();
		$d1->setTimestamp(Question::earliestQuestionDate());
		$d1->setDate($d1->format('Y'), $d1->format('m'), 1);
		$d1->setTime(0,0,0);

		$lastQuestionDate = Question::lastQuestionDate();

		$output = array();

		while (true) {
			$d2 = clone $d1;
			$d2->add(new \DateInterval('P' . $d2->format('t') . 'D'));

			$cacheTimeout = 60 * 60 * 24 * 31 * 12;
			$now = new \DateTime();
			if ($d2->format('Ym') >= $now->format('Ym')) {
				$cacheTimeout = 60 * 60 * 24;
			}

			$this->writeln("Processing " . $d1->format('Y-m') . '... Cache: ' . $cacheTimeout);

			$params = array('date1' => $d1->getTimestamp(), 'date2' => $d2->getTimestamp());
			
			$rows = $this->cache_->getOrSet('ReportService::monthlyInjection' . md5(json_encode($params)), function() use($params) {
				$st = $this->db_->getPdo()->prepare('
					SELECT
						has_sql_injection, has_sql
					FROM questions
					WHERE has_sql = 1 AND creation_date >= :date1 AND creation_date < :date2
				');
				$st->execute($params);

				return $st->fetchAll(\PDO::FETCH_ASSOC);
			}, $cacheTimeout);

			$summary = array(
				'month' => $d1->format('Ym'),
				'has_sql_count' => 0,
				'has_sql_injection_count' => 0,
			);

			foreach ($rows as $row) {
				if ((int)$row['has_sql']) $summary['has_sql_count']++;
				if ((int)$row['has_sql_injection']) $summary['has_sql_injection_count']++;
			}

			$output[] = $summary;

			$d1 = clone $d2;

			if ($d1->getTimestamp() > $lastQuestionDate) break;
		}

		return $output;
	}

	public function latestInjections() {
		$results = $this->db_->getPdo()->query('
			SELECT question_id, body_markdown, sql_injection_line, creation_date
			FROM questions
			WHERE has_sql_injection = 1
			ORDER BY creation_date DESC
			LIMIT 1000
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
				'question_id' => $row['question_id'],
			);
		}

		return $output;
	}

	public function sqlInjectionsPerCountry() {
		$output = array();
		$db = $this->db_;
		$lastId = 0;
		$lastQuestionId = Question::max('question_id');
		while (true) {
			$this->writeln(round(($lastId / $lastQuestionId) * 100) . '%');
			$sql = 'SELECT question_id, owner_id, has_sql, has_sql_injection FROM questions WHERE owner_id > 0 AND question_id > :question_id AND has_sql = 1 ORDER BY question_id LIMIT 10000';
			$this->writeln('Fetch questions...');
			$params = array('question_id' => $lastId);
			$questions = $this->cache_->getOrSet('ReportService::sqlInjectionsPerCountry_questions' . md5($sql . json_encode($params)), function() use($sql, $db, $params) {
				$st = $db->getPdo()->prepare($sql);
				$st->execute($params);
				return $st->fetchAll(\PDO::FETCH_ASSOC);
			}, 60 * 60 * 24 * 31);

			if (!count($questions)) break;

			$lastId = $questions[count($questions) - 1]['question_id'];

			$userIds = array();
			foreach ($questions as $q) $userIds[] = (int)$q['owner_id'];

			$this->writeln('Fetch users...');

			$s = implode(',', $userIds);
			$sql = 'SELECT user_id, country FROM users WHERE country IS NOT NULL AND user_id IN (' . $s . ')';
			$users = $this->cache_->getOrSet('ReportService::sqlInjectionsPerCountry_users' . md5($sql), function() use($sql, $db) {
				return $db->getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
			});
	
			$temp = array();
			foreach ($questions as $qIndex => $q) {
				$country = null;
				foreach ($users as $u) {
					if ($u['user_id'] == $q['owner_id']) {
						$country = $u['country'];
						break;
					}
				}
				if (!$country) continue;

				$q['country'] = $country;
				$temp[] = $q;
			}
			$questions = $temp;

			foreach ($questions as $q) {
				if (!array_key_exists($q['country'], $output)) $output[$q['country']] = array('has_sql' => 0, 'has_sql_injection' => 0);
				$output[$q['country']]['has_sql']++;
				$output[$q['country']]['has_sql_injection'] += (int)$q['has_sql_injection'] ? 1 : 0;
			}
		}

		$topCountries = Country::topCountries();
		$topCountryCodes = array();
		foreach ($topCountries as $c) $topCountryCodes[] = $c['country'];

		$temp = array();
		foreach ($output as $country => $o) {
			if (!in_array($country, $topCountryCodes)) continue;
			$o['ratio'] = $o['has_sql'] ? $o['has_sql_injection'] / $o['has_sql'] : 0;
			$o['code'] = $country;
			$o['country_name'] = Country::countryName($country);
			$temp[] = $o;
		}
		$output = $temp;

		usort($output, function($a, $b) {
			return $a['ratio'] > $b['ratio'] ? -1 : +1;
		});

		return $output;
	}

}