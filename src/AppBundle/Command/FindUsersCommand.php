<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class FindUsersCommand extends ContainerAwareCommand {
	
	protected function configure() {
		$this->setName('app:find-users');
		$this->setDescription('Extract users from the questions stored in the database.');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$crawler = $this->getContainer()->get('app.user_finder');
		$crawler->setOutput($output);
		$crawler->execute();
	}

}