<?php
	class witAI
	{
		protected $_token;
		protected $_version = '20170415';
		//protected $_data;

		/// Class constructor
		public function __construct($token) {
			$this->_token = $token;
		}

		function getTextResponse($query)
		{
			$options = array(
				'http' => array(
					'method' => 'GET',
					'header' => "Authorization: Bearer " . $this->_token . "\r\n"
				)
			);
			$content = "&q=" . urlencode($query);
			return $this->getWitAIResponse("message", $content, $options);
		}

		function getAudioResponse($audioFile)
		{
			$options = array(
				'http' => array(
					'method' => 'POST',
					'header' => "Authorization: Bearer " . $this->_token . "\n" .
						"Content-Type: audio/mpeg3" . "\r\n",
					'content' => file_get_contents($audioFile)
				)
			);
			return $this->getWitAIResponse("speech", "", $options);
		}

		function getWitAIResponse($endPoint, $content, $options)
		{
			$context = stream_context_create($options);
			$url = 'https://api.wit.ai/' . $endPoint . '?v=' . $this->_version . $content;
			$result = file_get_contents($url, false, $context);
			$result = json_decode($result);

			if ($result)
				return $result;

			return null;
		}
	}
?>