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

	public function endsWith($haystack, $needle)
	{
		$length = strlen($needle);
		if ($length == 0)
			return true;

		return (substr($haystack, -$length) === $needle);
	}
}