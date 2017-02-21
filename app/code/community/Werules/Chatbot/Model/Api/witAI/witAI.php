<?php
	class witAI {

		private $api_key = "";

		/// Class constructor
		public function __construct($api_key) {
			$this->api_key = $api_key;
		}

		// get witAI response
		function getWitAIResponse($query)
		{
			$options = array(
				'http' => array(
					'method' => 'GET',
					'header' => "Authorization: Bearer " . $this->api_key . "\r\n" .
						"Accept: appliation/vnd.wit.20141022+json\r\n"
				)
			);
			$context = stream_context_create($options);
			$url = 'https://api.wit.ai/message?q=' . urlencode($query);
			$result = file_get_contents($url, false, $context);
			$result = json_decode($result);

			return $result;
		}
	}
?>