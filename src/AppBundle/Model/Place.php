<?php

namespace AppBundle\Model;

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Support\Facades\DB;

class Place extends \Illuminate\Database\Eloquent\Model {

	public $timestamps = false;
	protected $primaryKey = 'id';

	static public function guessCountry($location) {
		if (!$location) return null;
		$location = trim($location);
		if (empty($location)) return null;

		$s = explode(',', $location);
		$s = strtolower(trim($s[count($s) - 1]));

		// Country Name

		$place = Place::where('name', '=', $s)->first();
		if ($place) return $place->country();
		
		// US state
		// Lots of special cases for US since when no country is
		// specified, it's often assumed it's in the US.

		$countryUs = Country::where('code', '=', 'US')->first();

		if (self::usStateExists($location)) return $countryUs;

		foreach (self::$usStates_ as $code => $state) {
			if ($code == strtoupper($s)) {
				return $countryUs;
			}
			if (strtolower($state) == strtolower($s)) {
				return $countryUs;
			}
		}

		// Country code

		$country = Country::where('code', '=', $s)->first();
		if ($country) return $country;

		// Search the last words of the location as a country
		// eg. To find "United States" in "Fayetteville, North Carolina United States"

		$s = explode(' ', $location);
		for ($i = count($s); $i >= 1; $i--) {

			$name = implode(' ', array_slice($s, count($s) - $i, $i));
			$place = Place::where('name', '=', $name)->where('country_id', '>', 0)->first();
			if ($place) return $place->country();

			if (self::usStateExists($name)) return $countryUs;
		}

		// Search the whole name
		$place = Place::where('name', '=', $location)->first();
		if ($place) return $place->country();

		$s = str_replace(',', ' ', $location);
		$s = str_replace('.', ' ', $s);
		$s = str_replace('    ', ' ', $s);
		$s = str_replace('   ', ' ', $s);
		$s = str_replace('  ', ' ', $s);
		$s = explode(' ', $s);

		for ($i = count($s); $i >= 1; $i--) {

			$name = trim(implode(' ', array_slice($s, 0, $i)));
			if (empty($name)) continue;
			// Check it's not a fake name like "In front of my seat" or "The earth"
			// since "in" and "the" are valid city names in some countries.
			if ($i == 1 && self::isStopWord($name)) continue;

			$place = Place::where('name', '=', $name)->first();
			// if ($place) echo $place->name . "\n";

			if ($place) return $place->country();

			if (self::usStateExists($name)) return $countryUs;
		}

		// echo 'NOT FOUND: ' . $location . "\n";

		return null;
	}

	public function country() {
		if ($this->country_id) return Country::where('geoname_id', '=', $this->country_id)->first();
		$city = City::where('geoname_id', '=', $this->city_id)->first();
		return Country::where('geoname_id', '=', $city->country_id)->first();
	}

	static private function usStateExists($name) {
		foreach (self::$usStates_ as $code => $state) {
			if (strtolower($name) == strtolower($state)) return true;
		}
		return false;
	}

	static private function isStopWord($word) {
		return in_array(strtolower($word), self::$stopWords_);
	}

	static private $usStates_ = array(
		'AL'=>"Alabama",  
		'AK'=>"Alaska",  
		'AZ'=>"Arizona",  
		'AR'=>"Arkansas",  
		'CA'=>"California",  
		'CO'=>"Colorado",  
		'CT'=>"Connecticut",  
		'DE'=>"Delaware",  
		'DC'=>"District Of Columbia",  
		'FL'=>"Florida",  
		'GA'=>"Georgia",  
		'HI'=>"Hawaii",  
		'ID'=>"Idaho",  
		'IL'=>"Illinois",  
		'IN'=>"Indiana",  
		'IA'=>"Iowa",  
		'KS'=>"Kansas",  
		'KY'=>"Kentucky",  
		'LA'=>"Louisiana",  
		'ME'=>"Maine",  
		'MD'=>"Maryland",  
		'MA'=>"Massachusetts",  
		'MI'=>"Michigan",  
		'MN'=>"Minnesota",  
		'MS'=>"Mississippi",  
		'MO'=>"Missouri",  
		'MT'=>"Montana",
		'NE'=>"Nebraska",
		'NV'=>"Nevada",
		'NH'=>"New Hampshire",
		'NJ'=>"New Jersey",
		'NM'=>"New Mexico",
		'NY'=>"New York",
		'NC'=>"North Carolina",
		'ND'=>"North Dakota",
		'OH'=>"Ohio",  
		'OK'=>"Oklahoma",  
		'OR'=>"Oregon",  
		'PA'=>"Pennsylvania",  
		'RI'=>"Rhode Island",  
		'SC'=>"South Carolina",  
		'SD'=>"South Dakota",
		'TN'=>"Tennessee",  
		'TX'=>"Texas",  
		'UT'=>"Utah",  
		'VT'=>"Vermont",  
		'VA'=>"Virginia",  
		'WA'=>"Washington",  
		'WV'=>"West Virginia",  
		'WI'=>"Wisconsin",  
		'WY'=>"Wyoming"
	);

	static private $stopWords_ = array('a','able','about','across','after','all','almost','also','am','among','an','and','any','are','as','at','be','because','been','but','by','can','cannot','could','dear','did','do','does','either','else','ever','every','for','from','get','got','had','has','have','he','her','hers','him','his','how','however','i','if','in','into','is','it','its','just','least','let','like','likely','may','me','might','most','must','my','neither','no','nor','not','of','off','often','on','only','or','other','our','own','rather','said','say','says','she','should','since','so','some','than','that','the','their','them','then','there','these','they','this','tis','to','too','twas','us','wants','was','we','were','what','when','where','which','while','who','whom','why','will','with','would','yet','you','your');

}