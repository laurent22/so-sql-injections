<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use AppBundle\Model\Country;
use AppBundle\Model\City;
use AppBundle\Model\Place;

class PopulatePlaceTablesCommand extends ContainerAwareCommand {
	
	protected function configure() {
		$this->setName('app:populate-place-tables');
		$this->setDescription('Populate place tables with data.');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$e = $this->getContainer()->get('app.eloquent');
		$con = $e->connection();

		$rootDir = $this->getContainer()->get('kernel')->getRootDir();
		$placesDir = dirname($rootDir) . '/var/places';

		$con->getPdo()->query('TRUNCATE countries');
		$con->getPdo()->query('TRUNCATE cities');
		$con->getPdo()->query('TRUNCATE places');

		// ---------------------------------------------------------------------------
		// Countries
		// ---------------------------------------------------------------------------

		$con->beginTransaction();

		$countryGeonameIds = array();
		$countryCodeToIds = array();
		$file = fopen($placesDir . '/countryInfo.txt', 'r');
		if (!$file) throw new \Exception('Cannot open file');
		while (($line = fgets($file)) !== false) {
			$line = trim($line);
			if ($line == '' || $line[0] == '#') continue;
			$items = explode("\t", trim($line));
			$isoCode = $items[0];
			$geonameId = $items[16];
			$c = new Country();
			$c->code = $isoCode;
			$c->geoname_id = $geonameId;
			$c->save();

			$countryGeonameIds[] = $geonameId;
			$countryCodeToIds[$isoCode] = $geonameId;
		}
		fclose($file);

		$con->commit();

		// $s = '';
		// foreach ($countryGeonameIds as $id) {
		// 	if ($s != '') $s .= '|';
		// 	$s .= $id;
		// }
		// $s = sprintf('grep -P \'^(%s)\t\' allCountries.txt > countries_only.txt', $s);
		// echo $s . "\n"; die();

		// ---------------------------------------------------------------------------
		// Country names
		// ---------------------------------------------------------------------------

		$con->beginTransaction();

		$file = fopen($placesDir . '/countries_only.txt', 'r');
		if (!$file) throw new \Exception('Cannot open file');
		while (($line = fgets($file)) !== false) {
			$line = trim($line);
			if ($line == '' || $line[0] == '#') continue;
			$items = explode("\t", trim($line));

			$geonameId = trim($items[0]);
			$names = trim($items[3]);
			if (!empty($names)) {
				$names = explode(',', $names);
				$names = array_merge(array($items[1]), $names);
			} else {
				$names = array($items[1]);
			}

			$names = array_unique($names);

			foreach ($names as $name) {
				$name = trim($name);
				if (empty($name)) continue;
				$place = new Place();
				$place->city_id = null;
				$place->country_id = $geonameId;
				$place->name = $name;
				$place->save();
			}
		}
		fclose($file);

		$con->commit();

		// ---------------------------------------------------------------------------
		// Cities
		// ---------------------------------------------------------------------------

		$con->beginTransaction();

		$addCount = 0;
		$commitCount = 0;
		$file = fopen($placesDir . '/cities1000.txt', 'r');
		if (!$file) throw new \Exception('Cannot open file');
		while (($line = fgets($file)) !== false) {
			$line = trim($line);
			if ($line == '' || $line[0] == '#') continue;
			$items = explode("\t", trim($line));

			$geonameId = trim($items[0]);
			$names = trim($items[3]);
			if (!empty($names)) {
				$names = explode(',', $names);
				$names = array_merge(array($items[1]), $names);
			} else {
				$names = array($items[1]);
			}

			$names = array_unique($names);

			$countryCode = $items[8];
			if (!isset($countryCodeToIds[$countryCode])) throw new \Exception('Cannot find country code: '  . $countryCode);
			$countryId = $countryCodeToIds[$countryCode];

			$city = new City();
			$city->geoname_id = $geonameId;
			$city->country_id = $countryId;
			$city->save();

			foreach ($names as $name) {
				$place = new Place();
				$place->city_id = $geonameId;
				$place->country_id = null;
				$place->name = $name;
				$place->save();
			}

			$addCount++;
			if ($addCount >= 10000) {
				$addCount = 0;
				$commitCount++;
				echo $commitCount . "\n";
				$con->commit();
				$con->beginTransaction();
			}
		}
		fclose($file);

		$con->commit();
	}

}