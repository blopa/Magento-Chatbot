<?php
namespace Werules\Chatbot\Cron;

class Worker {

	protected $_logger;

	public function __construct(\Psr\Log\LoggerInterface $logger) {
		$this->_logger = $logger;
	}

	/**
	 * Method executed when cron runs in server
	*/
	public function execute() {
//		if (shell_exec('ps aux | grep ' . __FILE__ . ' | wc  -l') > 1) {
//			exit('already running...');
//		}
//		OR
//		$f = fopen('lock', 'w') or die ('Cannot create lock file');
//		if (flock($f, LOCK_EX | LOCK_NB)) {
//			// yay
//		}
		$this->_logger->debug('Running Cron from Worker class');
		return $this;
	}
}