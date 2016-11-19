<?php

namespace AppBundle\Model;

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Support\Facades\DB;

class Question extends \Illuminate\Database\Eloquent\Model {

	protected $primaryKey = 'question_id';

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