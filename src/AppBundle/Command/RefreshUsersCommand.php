<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class RefreshUsersCommand extends ContainerAwareCommand {
	
	protected function configure() {
		$this->setName('app:refresh-users');
		$this->setDescription('Refresh local users based on stored JSON object.');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$crawler = $this->getContainer()->get('app.user_finder');
		$crawler->setOutput($output);
		$crawler->refreshUsers();
	}

}