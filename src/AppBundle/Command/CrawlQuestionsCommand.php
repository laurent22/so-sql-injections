<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class CrawlQuestionsCommand extends ContainerAwareCommand {
	
	protected function configure() {
		$this->setName('app:crawl-questions');
		$this->setDescription('Crawl PHP questions from Stack Overflow and save them to the database.');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$crawler = $this->getContainer()->get('app.question_crawler');
		$crawler->setOutput($output);
		$crawler->execute();
		// $crawler->refreshQuestions();
	}

}