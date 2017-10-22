<?php
namespace Werules\Chatbot\Controller\Webhook;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action
{
	/**
	 * Dispatch request
	 *
	 * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
	 * @throws \Magento\Framework\Exception\NotFoundException
	*/
	protected $_resultFactory;

	public function __construct(Context $context)
	{
		parent::__construct($context);
	}

	public function execute()
	{
		return $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
	}
}