<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class FindWinnersCommand extends ContainerAwareCommand {
	
	protected function configure() {
		$this->setName('app:find-winners');
		$this->setDescription('Searches in the database for questions that contain SQL injections.');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$service = $this->getContainer()->get('app.injection_finder');
		$service->setOutput($output);
		$service->execute();
	}

}