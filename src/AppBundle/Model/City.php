<?php

namespace AppBundle\Model;

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Support\Facades\DB;

class City extends \Illuminate\Database\Eloquent\Model {

	public $timestamps = false;
	protected $primaryKey = 'geoname_id';

}