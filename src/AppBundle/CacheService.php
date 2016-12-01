<?php

namespace AppBundle;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;

// Wrapper over the over-complicated Symfony cache component
class CacheService {

	private $adapter_ = null;

	private function adapter() {
		if (!$this->adapter_) $this->adapter_ = new FilesystemAdapter();
		return $this->adapter_;
	}

	public function get($k) {
		$entry = $this->adapter()->getItem(md5($k));
		return $entry->isHit() ? json_decode($entry->get(), true) : null;
	}

	public function set($k, $v, $expiryTime = null) {
		$entry = $this->adapter()->getItem(md5($k));
		$entry->set(json_encode($v));
		if ($expiryTime) $entry->expiresAfter($expiryTime);
		$this->adapter()->save($entry);
	}

	public function getOrSet($k, $func, $expiryTime = null) {
		$v = $this->get($k);
		if ($v === null) {
			$v = $func();
			$this->set($k, $v, $expiryTime);
		}
		return $v;
	}

}