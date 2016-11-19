<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class RefreshQuestionsCommand extends ContainerAwareCommand {
	
	protected function configure() {
		$this->setName('app:refresh-questions');
		$this->setDescription('Refresh local questions based on stored JSON object.');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$crawler = $this->getContainer()->get('app.question_crawler');
		$crawler->setOutput($output);
		$crawler->refreshQuestions();
	}

}