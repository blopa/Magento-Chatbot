<?php
class Werules_Chatbot_Helper_Data extends Mage_Core_Helper_Abstract
{
	// ----- CONSTANTS ----------

	// STRINGS
	public $_tgBot = "telegram";
	public $_fbBot = "facebook";
	public $_wappBot = "whatsapp";
	public $_wechatBot = "wechat";

	// CONVERSATION STATES
	public $_startState = 0;
	public $_listCategoriesState = 1;
	public $_listProductsState = 2;
	public $_searchState = 3;
	public $_loginState = 4;
	public $_listOrdersState = 5;
	public $_reorderState = 6;
	public $_add2CartState = 7;
	public $_checkoutState = 9;
	public $_trackOrderState = 10;
	public $_supportState = 11;
	public $_sendEmailState = 12;
	public $_clearCartState = 13;

	// ADMIN STATES
	public $_replyToSupportMessageState = 14;

	// COMMANDS
	public $_cmdList =
		"
			start,
			list_categories,
			search,
			login,
			list_orders,
			reorder,
			add2cart,
			checkout,
			clear_cart,
			track_order,
			support,
			send_email,
			cancel,
			help,
			about,
			logout,
			register
		";
	public $_startCmd = array();
	public $_listCategoriesCmd = array();
	public $_searchCmd = array();
	public $_loginCmd = array();
	public $_listOrdersCmd = array();
	public $_reorderCmd = array();
	public $_add2CartCmd = array();
	public $_checkoutCmd = array();
	public $_clearCartCmd = array();
	public $_trackOrderCmd = array();
	public $_supportCmd = array();
	public $_sendEmailCmd = array();
	public $_cancelCmd = array();
	public $_helpCmd = array();
	public $_aboutCmd = array();
	public $_logoutCmd = array();
	public $_registerCmd = array();

	// admin cmds
//		protected $adminCmdList =
//		"
//			messagetoall,
//			endsupport,
//			blocksupport
//		";
	public $_admSendMessage2AllCmd = "messagetoall";
	public $_admEndSupportCmd = "endsupport";
	public $_admBlockSupportCmd = "blocksupport";
	public $_admEnableSupportCmd = "enablesupport";

	// REGEX
	public $_unallowedCharacters = "/[^A-Za-z0-9 _]/";

	// DEFAULT MESSAGES
	public $_errorMessage = "";
	public $_cancelMessage = "";
	public $_canceledMessage = "";
	public $_loginFirstMessage = "";
	public $_positiveMessages = array();

	// URLS
	public $_tgUrl = "https://t.me/";
	public $_fbUrl = "https://m.me/";
//		protected $_wappUrl = "";
//		protected $_wechatUrl = "";

//	public function transcribeAudio()
//	{
//		$googleSpeechURL = "https://speech.googleapis.com/v1beta1/speech:syncrecognize?key=xxxxxxxxxxxx";
//		$upload = file_get_contents("1.wav");
//		$fileData = base64_encode($upload);
//
//		$data = array(
//			"config" => array(
//				"encoding" => "LINEAR16",
//				"sample_rate" => 16000,
//				"language_code" => "pt-BR"
//			),
//			"audio" => array(
//				"content" => base64_encode($fileData)
//			)
//		);
//
//		$dataString = json_encode($data);
//
//		$ch = curl_init($googleSpeechURL);
//		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
//		curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
//		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//				'Content-Type: application/json',
//				'Content-Length: ' . strlen($dataString))
//		);
//
//		$result = curl_exec($ch);
//
//		return json_decode($result, true);
//	}

	public function getContent($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		curl_close($ch);
		if ($data)
			return $data;

		return null;
	}

	public function convertOggToMp3($filePath, $fileName)
	{
		// install ffmpeg by typoing sudo apt-get install ffmpeg
		$output = $filePath . "output.mp3";
		if (!file_exists($filePath . $fileName))
			return null;

		$ffmpeg = exec('which ffmpeg');
		$ffmpegCmd = $ffmpeg . " -i " . $filePath . $fileName . " -acodec libmp3lame " . $output;
		exec($ffmpegCmd);

		if (!file_exists($output))
			return null;

		return $output;
	}

	// excerpt text
	public function excerpt($text, $size)
	{
		if (strlen($text) > $size)
		{
			$text = substr($text, 0, $size);
			$text = substr($text, 0, strrpos($text, " "));
			$etc = " ...";
			$text = $text . $etc;
		}
		return $text;
	}

	// get a value from a command, eg.: /add_product123 -> 123 is the ID of the product
	public function getCommandValue($text, $cmd)
	{
		if (strlen($text) > strlen($cmd))
			return substr($text, strlen($cmd), strlen($text));
		return null;
	}

	// check if the message sent by the customer is a valid/enabled command
	public function checkCommand($text, $cmd)
	{
		if ($cmd['command'])
		{
			$t = strtolower($text);
			if ($t == $cmd['command'])
				return true;
			else if ($cmd['alias'])
			{
				//$alias = explode(",", $cmd['alias']);
				$alias = $cmd['alias'];
				if (is_array($alias))
				{
					foreach ($alias as $al)
					{
						if (!empty($al))
							if (strpos($t, $al) !== false)
								return true;
					}
				}
			}
		}

		return false;
	}

	// check if a message starts with something
	public function startsWith($haystack, $needle)
	{
		if ($needle)
			return substr($haystack, 0, strlen($needle)) == $needle;
		return false;
	}

	// check if a message ends with something
	public function endsWith($haystack, $needle)
	{
		$length = strlen($needle);
		if ($length == 0)
			return true;

		return (substr($haystack, -$length) === $needle);
	}

	// return all orders id from a customer id
	public function getOrdersIdsFromCustomer($customerId)
	{
		$ids = array();
		$orders = Mage::getResourceModel('sales/order_collection')
			->addFieldToSelect('*')
			->addFieldToFilter('customer_id', $customerId) // not a problem if customer dosen't exist
			->setOrder('created_at', 'desc');
		foreach ($orders as $_order)
		{
			array_push($ids, $_order->getId());
		}
		if ($ids)
			return $ids;
		return false;
	}

	// return all ids from a product search by text
	public function getProductIdsBySearch($searchString)
	{
		// Code to Search Product by $searchstring and get Product IDs
		$productCollection = Mage::getResourceModel('catalog/product_collection')
			->addAttributeToSelect('*')
			->addAttributeToFilter('visibility', 4)
			->addAttributeToFilter('type_id', 'simple')
			->addAttributeToFilter(
				array(
					array('attribute' => 'sku', 'like' => '%' . $searchString .'%'),
					array('attribute' => 'name', 'like' => '%' . $searchString .'%')
				)
			);
		//->getAllIds();
		Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($productCollection);
		$productIDs = $productCollection->getAllIds();

		if (!empty($productIDs))
			return $productIDs;

		return false;
	}

	// return image content to be sent as message
	public function loadImageContent($productID)
	{
		$imagepath = Mage::getModel('catalog/product')->load($productID)->getSmallImage();
		if ($imagepath && $imagepath != "no_selection")
		{
			$absolutePath =
				Mage::getBaseDir('media') .
				DS . "catalog" . DS . "product" .
				$imagepath;

			return curl_file_create($absolutePath, 'image/jpg');
		}
		return null;
	}

	// TELEGRAM FUNCTIONS
	public function prepareTelegramOrderMessages($orderID) // TODO add link to product name
	{
		$order = Mage::getModel('sales/order')->load($orderID);
		if ($order->getId())
		{
			$message = Mage::helper('core')->__("Order") . " # " . $order->getIncrementId() . "\n\n";
			$items = $order->getAllVisibleItems();
			foreach($items as $item)
			{
				$message .= (int)$item->getQtyOrdered() . "x " .
					$item->getName() . "\n" .
					Mage::helper('core')->__("Price") . ": " . Mage::helper('core')->currency($item->getPrice(), true, false) . "\n\n";
			}
			$message .= Mage::helper('core')->__("Total") . ": " . Mage::helper('core')->currency($order->getGrandTotal(), true, false) . "\n" .
				Mage::helper('core')->__("Zipcode") . ": " . $order->getShippingAddress()->getPostcode();
			return $message;
		}
		return null;
	}

	public function prepareTelegramProdMessages($productID) // TODO add link to product name
	{
		$product = Mage::getModel('catalog/product')->load($productID);
		if ($product->getId())
		{
			if ($product->getStockItem()->getIsInStock() > 0)
			{
				$mageHelper = Mage::helper('core');
				$chatbotHelper = Mage::helper('werules_chatbot');
				$message = $product->getName() . "\n" .
					$mageHelper->__("Price") . ": " . Mage::helper('core')->currency($product->getPrice(), true, false) . "\n" .
					$chatbotHelper->excerpt($product->getShortDescription(), 60);
				return $message;
			}
		}
		return null;
	}

	// check if it's a valid Telegram command
	public function validateTelegramCmd($cmd)
	{
		if ($cmd == "/")
			return null;
		return $cmd;
	}

	// FACEBOOK FUNCTIONS
	public function prepareFacebookProdMessages($productID) // TODO add link to product name
	{
		$product = Mage::getModel('catalog/product')->load($productID);
		if ($product->getId())
		{
			if ($product->getStockItem()->getIsInStock() > 0)
			{
				$chatbotHelper = Mage::helper('werules_chatbot');
				$message = $product->getName() . "\n" .
					$chatbotHelper->excerpt($product->getShortDescription(), 60);
				return $message;
			}
		}
		return null;
	}

	public function prepareFacebookOrderMessages($orderID) // TODO add link to product name
	{
		$order = Mage::getModel('sales/order')->load($orderID);
		if ($order->getId())
		{
			$message = Mage::helper('core')->__("Order") . " # " . $order->getIncrementId() . "\n\n";
			$items = $order->getAllVisibleItems();
			foreach($items as $item)
			{
				$message .= (int)$item->getQtyOrdered() . "x " .
					$item->getName() . "\n" .
					Mage::helper('core')->__("Price") . ": " . Mage::helper('core')->currency($item->getPrice(), true, false) . "\n\n";
			}
			$message .= Mage::helper('core')->__("Total") . ": " . Mage::helper('core')->currency($order->getGrandTotal(), true, false) . "\n" .
				Mage::helper('core')->__("Zipcode") . ": " . $order->getShippingAddress()->getPostcode();

			return $message;
		}
		return null;
	}
}