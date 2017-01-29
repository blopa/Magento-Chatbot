<?php
class Werules_Chatbot_ChatdataController extends Mage_Core_Controller_Front_Action {
	public function indexAction()
	{
		$this->loadLayout();
		$this->renderLayout();
	}

	public function telegramAction()
	{
		$this->loadLayout();
		$this->renderLayout();
	}

	public function facebookAction()
	{
		$this->loadLayout();
		$this->renderLayout();
	}
}