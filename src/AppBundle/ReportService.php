<?php

// - Latest injections
// - Injections over time

namespace AppBundle;

use AppBundle\Model\Question;
use AppBundle\Model\Country;

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
			FROM questions
			WHERE has_sql = 1
			GROUP BY month
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

	public function sqlInjectionsPerCountry() {
		$countryIds = array();
		$countryCodeToUserCount = array();
		foreach (self::$countryStats_ as $row) {
			$n = $row[1];
			$country = Country::byName($n);
			$countryIds[] = $country->geoname_id;
			$countryCodeToUserCount[$country->code] = self::parseNum($row[4]);
		}

		$results = $this->db_->getPdo()->query('
			SELECT
				countries.geoname_id,
				country AS country_code,
				count(country) AS total
			FROM questions
			JOIN users ON users.user_id = questions.owner_id
			JOIN countries ON users.country = countries.code
			WHERE has_sql_injection = 1 AND country IS NOT NULL
			GROUP BY country
			ORDER BY total DESC
		');
		$results = $results->fetchAll(\PDO::FETCH_ASSOC);

		$output = array();
		foreach ($results as $row) {
			if (!in_array($row['geoname_id'], $countryIds)) continue;
			$row['country_name'] = Country::countryName($row['country_code']);
			$row['score'] = ($row['total'] / $countryCodeToUserCount[$row['country_code']] * 1000000);
			$output[] = $row;
		}

		usort($output, function($a, $b) {
			return $a['score'] < $b['score'] ? +1 : -1;
		});

		return $output;
	}

	static private function parseNum($s) {
		if (strpos($s, 'M') !== false) return 1000000 * str_replace('M', '', $s);
		if (strpos($s, 'K') !== false) return 1000 * str_replace('K', '', $s);
		return $s;
	}

	// https://www.quantcast.com/stackoverflow.com#/geographicCard
	// COUNTRIES, AFFINITY, COMPOSITION, UNIQUES
	static private $countryStats_ = array(
		array('1', 'United States', '0.53x', '25.18%', '12.9M'),
		array('2', 'India', '4.71x', '11.50%', '5.9M'),
		array('3', 'United Kingdom', '1.79x', '5.48%', '2.8M'),
		array('4', 'Germany', '1.69x', '4.36%', '2.2M'),
		array('5', 'China', '5.27x', '3.85%', '2M'),
		array('6', 'Canada', '1.52x', '3.21%', '1.6M'),
		array('7', 'Brazil', '0.94x', '2.56%', '1.3M'),
		array('8', 'France', '1.1x', '2.45%', '1.3M'),
		array('9', 'Russia', '1.87x', '2.27%', '1.2M'),
		array('10', 'Australia', '1.85x', '1.87%', '958.2K'),
		array('11', 'Japan', '0.87x', '1.68%', '861.7K'),
		array('12', 'Spain', '1.21x', '1.63%', '836.3K'),
		array('13', 'Italy', '1.13x', '1.62%', '828.5K'),
		array('14', 'Netherlands', '2.36x', '1.59%', '815.5K'),
		array('15', 'Korea, Republic Of', '1.87x', '1.32%', '678.9K'),
		array('16', 'Poland', '1.55x', '1.22%', '627K'),
		array('17', 'Indonesia', '1.22x', '1.18%', '603.5K'),
		array('18', 'Philippines', '1.76x', '1.07%', '549.6K'),
		array('19', 'Mexico', '0.67x', '1.05%', '538.4K'),
		array('20', 'Ukraine', '2.62x', '0.94%', '479.9K'),
	);

}