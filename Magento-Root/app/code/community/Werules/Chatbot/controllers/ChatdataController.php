<?php
class Werules_Chatbot_ChatdataController extends Mage_Core_Controller_Front_Action {
	public function indexAction()
	{
		$this->loadLayout();
		$this->getLayout()->getBlock('root')->setTemplate("werules_chatbot_view.phtml"); // use root block to output pure values without html tags
		$this->renderLayout();
	}

	public function telegramAction()
	{
		$this->loadLayout();
		$this->getLayout()->getBlock('root')->setTemplate("werules_chatbot_view.phtml"); // use root block to output pure values without html tags
		$this->renderLayout();
	}

	public function facebookAction()
	{
		$this->loadLayout();
		$this->getLayout()->getBlock('root')->setTemplate("werules_chatbot_view.phtml"); // use root block to output pure values without html tags
		$this->renderLayout();
	}
}