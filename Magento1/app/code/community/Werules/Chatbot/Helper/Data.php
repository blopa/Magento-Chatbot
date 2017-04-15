<?php
class Werules_Chatbot_Helper_Data extends Mage_Core_Helper_Abstract
{
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

	public function getCommandValue($text, $cmd)
	{
		if (strlen($text) > strlen($cmd))
			return substr($text, strlen($cmd), strlen($text));
		return null;
	}
}