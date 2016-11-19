<?php

namespace AppBundle;

class BaseService {

	protected $output_ = null;

	public function setOutput($output) {
		$this->output_ = $output;
	}

	protected function writeln($s) {
		if (!$this->output_) return;
		$this->output_->writeln($s);
	}

	protected function write($s) {
		if (!$this->output_) return;
		$this->output_->write($s);
	}

}