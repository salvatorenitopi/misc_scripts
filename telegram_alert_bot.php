<?php

	
	$TELEGRAM_BOT_TOKEN = "";             // Telegram BOT token here (string)
	$RECIPIENTS = array("", "");          // chat_id of recipient users here (array of strings)

	$WEBAPI_TOKEN = "";                   // token used to authenticate to this endpoint (arbitrary string)

  // EXAMPLE: curl 127.0.0.1:8888/telegram_alert_bot.php -H 'token: my_webapi_token' -X POST -d 'message=hello world'



	// Preliminar checks - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		die ('{"status": -1, "error": "invalid request method"}');
	}
	
	if(!extension_loaded("curl")) {
		die('{"status": -1, "error": "cURL extension not loaded"}');
	}



	// Get headers - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	$token = "";
	$headers = apache_request_headers();

	foreach ($headers as $header => $value) {
	    if ($header === "token") {
	    	$token = $value;
	    };
	}

	// Check token
	if ($token != $WEBAPI_TOKEN) {
		die ('{"status": -1, "error": "invalid token"}');
	}



	// Get message - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	$message = $_POST['message'];

	if (strlen($message) < 1) {
		die ('{"status": -1, "error": "invalid message"}');
	}

	if (strlen($message) > 2000) {
		$message = mb_substr($message, 0, 2000) . " [TRUNCATED at 2000 bytes]";
	}



	foreach ($RECIPIENTS as &$chat_id) {
		
		// ---------------------------------------------------------------------------------------------------------
		// https://reqbin.com/req/php/c-1n4ljxb9/curl-get-request-example
		// https://stackoverflow.com/questions/8227909/curl-exec-always-returns-false

		$url = 'https://api.telegram.org/bot' . $TELEGRAM_BOT_TOKEN . '/sendMessage?chat_id=' . $chat_id . '&parse_mode=html&text=' . $message;

		try {
			$curl = curl_init();

			// Check if initialization had gone wrong*    
			if ($curl === false) {
				throw new Exception('{"status": -1, "error": "failed to initialize cURL request"}');
			}

			// Better to explicitly set URL
			curl_setopt($curl, CURLOPT_URL, $url);
			// That needs to be set; content will spill to STDOUT otherwise
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

			//for debug only!

			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

			
			$content = curl_exec($curl);

			// Check the return value of curl_exec(), too
			if ($content === false) {
				throw new Exception(curl_error($curl), curl_errno($curl));
			}

			// Check HTTP return code, too; might be something else than 200
			$httpReturnCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			if ($httpReturnCode != 200) {
				die ('{"status": -1, "error": "telegram request returned HTTP code ' . $httpReturnCode . '"}');
			}


			// Process $content - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

			$decoded_content = json_decode($content, true);
			//var_dump($content);
			//var_dump($decoded_content);

			if (!isset( $decoded_content['ok'] )) {
				die ('{"status": -1, "error": "telegram request FAILED (\'ok\' key not found)"}');
			}

			if ($decoded_content['ok'] === false) {
				die ('{"status": -1, "error": "telegram request FAILED (\'ok\' key is \'false\')"}');
			}


		} catch(Exception $e) {
			// trigger_error(sprintf( 'Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()), E_USER_ERROR);
			die ('{"status": -1, "error": "request failed"}');

		} finally {
			// Close curl handle unless it failed to initialize
			if (is_resource($curl)) {
				curl_close($curl);
			}
		}
		// ---------------------------------------------------------------------------------------------------------

		sleep(1);

	}

?>
