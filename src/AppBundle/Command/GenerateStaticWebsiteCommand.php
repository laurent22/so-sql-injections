<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class GenerateStaticWebsiteCommand extends ContainerAwareCommand {
	
	protected function configure() {
		$this->setName('app:generate-static-website');
		$this->setDescription('Generate static website.');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$service = $this->getContainer()->get('app.report_service');
		$service->setOutput($output);
		
		$rootDir = $this->getContainer()->get('kernel')->getRootDir();
		$content = file_get_contents(dirname($rootDir) . '/web/index_template.html');
		$outputFile = dirname($rootDir) . '/web/index.html';

		$content = str_replace('[MONTHLY_REPORT]', json_encode($service->monthlyInjections()), $content);
		$content = str_replace('[COUNTRY_REPORT]', json_encode($service->sqlInjectionsPerCountry()), $content);
		$content = str_replace('[LATEST_INJECTIONS]', json_encode($service->latestInjections()), $content);
		$content = str_replace('[GENERATION_TIMESTAMP]', json_encode(time()), $content);

		file_put_contents($outputFile, $content);
	}

}