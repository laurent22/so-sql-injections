<?php

namespace AppBundle\Model;

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Support\Facades\DB;

class Question extends \Illuminate\Database\Eloquent\Model {

	protected $primaryKey = 'question_id';

	public function fromApiArray($a) {
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

}