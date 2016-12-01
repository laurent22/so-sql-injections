<?php

namespace AppBundle;

use AppBundle\Model\Question;
use AppBundle\BaseService;

class InjectionFinder extends BaseService {

	private $db_ = null;

	public function __construct($eloquent) {
		$this->db_ = $eloquent->connection();
	}

	public function execute() {
		$limit = 1000;

		while (true) {
			$questions = Question::where('is_processed', '=', '0')
			                     ->orderBy('question_id', 'asc')
			                     ->limit($limit)
			                     ->get();

			$this->writeln(sprintf('Processing %s questions', count($questions)));

			if (!count($questions)) break;

			$this->db_->beginTransaction();
			try {
				foreach ($questions as $question) {
					$result = $this->findSqlInjection($question->body_markdown);
					$question->sql_injection_line = $result['lineNum'];
					$question->has_sql_injection = $result['found'];
					$question->is_processed = 1;
					$question->has_sql = $result['hasSql'];
					$question->save();
				}
			} catch (\Exception $e) {
				$this->db_->rollback();
				throw $e;
			}
			$this->db_->commit();
		}
	}

	public function findSqlInjection($body) {
		// $body = '    INSERT INTO `table_name` (`field1`, `field2`) VALUES (\'a\', \'b\'), (\'c\', \'d\', $post)';
		// $body = '    SELECT * FROM `table_name` WHERE username = $username';
		// $body = '    select * FROM `table_name` WHERE username = $username';
		// $body = '    UPDATE table_name SET username = $username WHERE ...';

		$lines = Question::bodyToLines($body);

		$sqlRegexes = array(
			'/INSERT\s+INTO.*/i',
			'/SELECT\s+.*?\sFROM.*/i',
			'/UPDATE\s+.*?\sSET.*/i',
			'/DELETE\s+FROM.*/i',
		);

		$sqlInjectionRegexes = array(
			'/SELECT\s+.*?\sFROM\s.*?\sWHERE\s.*?\$[a-zA-Z0-9].*?/i', // SELECT ... FROM ... WHERE email = "$email"
			'/SELECT\s+.*?\$[a-zA-Z0-9].*?\sFROM.*?/i', // SELECT ... $email ... FROM...
			'/INSERT\s+INTO\s.*?\$[a-zA-Z0-9].*?/i', // INSERT INTO ... $email ...
			'/UPDATE\s+.*?\sSET\s.*?\$[a-zA-Z0-9].*?/i', // UPDATE ... SET ... email = $email ...
			'/UPDATE\s+.*?\$[a-zA-Z0-9].*?\sSET.*?/i', // UPDATE $table SET ...
			'/DELETE\s+FROM\s+.*?\$[a-zA-Z0-9].*?/i', // DELETE FROM ... $somevar ...
		);

		$injectionLine = -1;
		$hasSql = false;
		for ($lineIndex = 0; $lineIndex < count($lines); $lineIndex++) {
			$line = $lines[$lineIndex];
			if (strpos($line, "\t") !== 0 && strpos($line, "    ") !== 0) continue; // Skip non-code lines
			$line = trim($line);
			if (strpos($line, '//') === 0) continue; // Skip comments

			foreach ($sqlRegexes as $regex) {
				$ok = preg_match($regex, $line);
				if ($ok === 1) {
					$hasSql = true;
					break;
				}
			}

			foreach ($sqlInjectionRegexes as $regex) {
				$ok = preg_match($regex, $line);
				if ($ok === 1) {
					$injectionLine = $lineIndex;
					break;
				}
			}

			if ($injectionLine >= 0) break;
		}

		return array(
			'found' => $injectionLine >= 0,
			'lineNum' => $injectionLine,
			'line' => $injectionLine >= 0 ? trim($lines[$injectionLine]) : null,
			'hasSql' => $hasSql,
		);
	}

}