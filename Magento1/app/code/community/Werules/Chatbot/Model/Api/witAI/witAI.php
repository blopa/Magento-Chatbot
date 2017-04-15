<?php
	class witAI
	{
		protected $_token;
		protected $_version = '20170415';
		protected $_data;

		/// Class constructor
		public function __construct($token) {
			$this->_token = $token;
		}

		function getTextResponse($query)
		{
			$content = "&q=" . urlencode($query);
			return $this->getWitAIResponse("message", $content);
		}

		function getWitAIResponse($endpoint, $content)
		{
			if (!isset($this->_data))
			{
				$accessToken = $this->_token;
				$options = array(
					'http' => array(
						'method' => 'GET',
						'header' => "Authorization: Bearer " . $accessToken . "\r\n"
					)
				);
				$context = stream_context_create($options);
				$url = 'https://api.wit.ai/' . $endpoint . '?v=' . $this->_version . $content;
				$result = file_get_contents($url, false, $context);
				$result = json_decode($result);
				$this->_data = $result;
			}
			return $this->_data;
		}
	}
?>