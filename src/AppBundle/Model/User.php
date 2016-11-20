<?php

namespace AppBundle\Model;

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Support\Facades\DB;
use AppBundle\CountryUtils;

class User extends \Illuminate\Database\Eloquent\Model {

	protected $primaryKey = 'user_id';

	public function fromApiArray($a) {
		$location = isset($a['location']) ? trim($a['location']) : null;
		$country = Place::guessCountry($location);

		$this->user_id = $a['user_id'];
		$this->age = isset($a['age']) ? $a['age'] : 0;
		$this->reputation = isset($a['reputation']) ? $a['reputation'] : 0;
		$this->is_employee = isset($a['is_employee']) ? (int)$a['is_employee'] : 0;
		$this->location = $location;
		$this->country = $country ? $country->code : null;
		$this->raw_json = json_encode($a);
	}

}