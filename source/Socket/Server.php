<?php
namespace SeanMorris\Dromez\Socket;
class Server
{
	const
		ADDRESS          = '0.0.0.0:9999'
		, MAX            = 1
		, PEM_PASSPHRASE = 'password'
		, FREQUENCY      = 120;

	protected
		$socket    = NULL
		, $clients = []
		, $sockets = []
		, $secure  = TRUE;

	public function listen()
	{
		fwrite(STDERR, "Listening...\n");

		while(TRUE)
		{
			usleep( 1000000 / static::FREQUENCY );

			$this->tick();			
		}
	}

	public function tick()
	{
		if($newClient = $this->getClient())
		{
			$this->clients[] = $newClient;

			end($this->clients);

			$this->onConnect($newClient, key($this->clients));

			reset($this->clients);
		}

		$this->onTick();

		foreach($this->clients as $clientId => $client)
		{
			if(!$client)
			{
				continue;
			}

			while($message = fread($client, 2**16))
			{
				$this->onReceive(
					static::decode($message)
					, $clientId
				);
			}
		}
	}

	protected static function decode($socketData)
	{
		$length = ord($socketData[1]) & 127;

		if($length == 126)
		{
			$masks = substr($socketData, 4, 4);
			$data = substr($socketData, 8);
		}
		elseif($length == 127)
		{
			$masks = substr($socketData, 10, 4);
			$data = substr($socketData, 14);
		}
		else
		{
			$masks = substr($socketData, 2, 4);
			$data = substr($socketData, 6);
		}

		$socketData = '';

		for ($i = 0; $i < strlen($data); ++$i)
		{
			$socketData .= $data[$i] ^ $masks[$i%4];
		}

		return $socketData;
	}

	public function send($content, $client)
	{
		$response = chr(129) . chr(strlen($content)) . $content;
		try
		{
			fwrite($client, $response);
		}
		catch(\Exception $e)
		{
			foreach($this->clients as $_clientId => $_client)
			{
				if($client === $_client)
				{
					$this->onDisconnect($client, $_clientId);

					unset($this->clients[$_clientId]);
				}
			}
			fclose($client);
		}
	}

	public function broadcast($content)
	{
		foreach($this->clients as $client)
		{
			if(!$client)
			{
				continue;
			}

			$this->send($content, $client);
		}
	}

	protected function getClient()
	{
		if(!$this->socket)
		{
			fwrite(STDERR, "Creating socket...\n");
			$context = stream_context_create();

			$address = 'tcp://' . static::ADDRESS;

			if($this->secure)
			{
				// $address = 'ssl://' . static::ADDRESS;
				$pem     = static::generateCert();
				$pemFile = '/tmp/ws_test_pem';
				
				file_put_contents($pemFile, $pem);

				stream_context_set_option($context, 'ssl', 'local_cert', $pemFile);
				stream_context_set_option($context, 'ssl', 'passphrase', static::PEM_PASSPHRASE);
				stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
				stream_context_set_option($context, 'ssl', 'verify_peer', false);
			}

			$this->socket = stream_socket_server(
				$address
				, $errorNumber
				, $errorString
				, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN
    			, $context
			);
		}

		try
		{
			$client = stream_socket_accept($this->socket, 0);
		}
		catch(\ErrorException $e)
		{
			return FALSE;
		}

		if($this->secure)
		{
			stream_socket_enable_crypto($client, TRUE, STREAM_CRYPTO_METHOD_SSLv23_SERVER);
		}

		stream_set_blocking($client, FALSE);

		static::handshake($client);

		if(count($this->clients) >= static::MAX)
		{
			fwrite($client, "Rejected\r\n");
			$this->onReject($client);
			fclose($client);
			return FALSE;
		}

		return $client;
	}

	protected static function generateCert()
	{
		$certificateData = array(
			"countryName"            => "US",
			"stateOrProvinceName"    => "New York",
			"localityName"           => "Valley Stream",
			"organizationName"       => "localhost",
			"organizationalUnitName" => "Development",
			"commonName"             => "localhost",
			"subjectAltName"         => "localhost",
			"emailAddress"           => "inquire@seanmorr.is"
		);
		$privkey = openssl_pkey_new();
		$cert    = openssl_csr_new($certificateData, $privkey);
		$cert    = openssl_csr_sign($cert, null, $privkey, 365);

		$pem_passphrase = static::PEM_PASSPHRASE;
		$pem            = array();
		openssl_x509_export($cert, $pem[0]);
		openssl_pkey_export($privkey, $pem[1], $pem_passphrase);
		$pem = implode($pem);

		return $pem;
	}

	protected static function handshake($client)
	{
		stream_set_blocking($client, TRUE);

		$headers = fread($client, 2**16);

		stream_set_blocking($client, FALSE);
		
		if (!preg_match('#^Sec-WebSocket-Key: (\S+)#mi', $headers, $match))
		{
			return;
		}

		fwrite(
			$client
			, "HTTP/1.1 101 Switching Protocols\r\n"
				. "Upgrade: websocket\r\n"
				. "Connection: Upgrade\r\n"
				. "Sec-WebSocket-Accept: " . base64_encode(
					sha1($match[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', TRUE)
				)
				. "\r\n\r\n"
		);
	}

	protected function onConnect($client, $clientId)
	{
		fwrite(STDERR, sprintf(
			"Accepting client #%d...\n"
			, count($this->clients)
		));
	}

	protected function onReject($client)
	{
		fwrite(STDERR, "Rejecting client...\n");
	}

	protected function onReceive($message, $clientId)
	{
		fwrite(STDERR, sprintf(
			"[#%d][%s] Message Received:\n\t%s\n"
			, $clientId
			, date('Y-m-d H:i:s')
			, $message
		));
	}

	protected function onDisconnect($client, $clientId)
	{
		fwrite(STDERR, sprintf(
			"Disconnecting client #%d...\n"
			, $clientId
		));
	}

	protected function onTick()
	{
		$this->broadcast('Now: ' . microtime(TRUE));
	}

	protected function onError($error, $clientId)
	{

	}
}
