<?php
namespace SeanMorris\Dromez\Socket;
class Server
{
	const
		MAX              = 1
		, FREQUENCY      = 120
		, MESSAGE_TYPES  = [
			'continuous' => 0
			, 'text'     => 1
			, 'binary'   => 2
			, 'close'    => 8
			, 'ping'     => 9
			, 'pong'     => 10
		];

	protected
		$id              = NULL
		, $newClientId   = 0
		, $socket        = NULL
		, $clients       = []
		, $sockets       = []
		, $secure        = TRUE
		, $channels      = []
		, $subscriptions = [];

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
			$this->onConnect($newClient);
		}

		$this->onTick();

		foreach($this->clients as $client)
		{
			if(!$client)
			{
				continue;
			}

			try
			{
				while($message = $client->read(2**16))
				{
					$received = $this->decode($message, $client);
					$type     = $this->dataType($message);

					switch($type)
					{
						case(static::MESSAGE_TYPES['text']):
						case(static::MESSAGE_TYPES['binary']):
							if($received !== FALSE)
							{
								$this->onReceive($received, $client, $type);
							}
							break;
						case(static::MESSAGE_TYPES['close']):
							if($client)
							{
								$this->onDisconnect($client);

								unset( $this->clients[$client->id] );

								$client->close();

								return FALSE;
							}
							break;
						// case(static::MESSAGE_TYPES['ping']):
						// case(static::MESSAGE_TYPES['pong']):
							break;
					}
				}
			}
			catch(\Exception $e)
			{
				\SeanMorris\Ids\Log::logException($e);

				$this->onDisconnect($client);

				unset( $this->clients[$client->id] );
				$client->close();
			}
		}
	}

	public function send($content, $client, $origin = NULL, $channel = NULL, $originalChannel = NULL)
	{
		if(is_numeric($channel) || preg_match('/^\d+-\d+$/', $channel))
		{
			$typeByte = static::MESSAGE_TYPES['binary'];
			
			$length   = strlen($content);
		}
		else
		{
			$typeByte = static::MESSAGE_TYPES['text'];

			$length   = strlen($content);
		}

		$typeByte += 128;

		if($length < 126)
		{
			$response = pack('CC', $typeByte, $length) . $content;
		}
		else if($length < 65536)
		{
			$response = pack('CCn', $typeByte, 126, $length) . $content;
		}
		else
		{
			$response = pack('CCNN', $typeByte, 127, 0, $length) . $content;
		}

		try
		{
			$client->write($response);
		}
		catch(\Exception $e)
		{
			\SeanMorris\Ids\Log::logException($e);

			if($client)
			{
				unset( $this->clients[$client->id] );
				$client->close();
			}
		}
	}

	public function channels()
	{
		return ['*' => 'SeanMorris\Dromez\Socket\Channel'];
	}

	public function getChannels($name, $client = NULL)
	{
		$channelClasses = $this->channels();

		if(($channelClasses[$name] ?? FALSE) && !($this->channels[$name] ?? FALSE))
		{
			if(!$channelClasses[$name]::isWildcard($name))
			{
				$this->channels[$name] = new $channelClasses[$name]($this, $name);
			}
		}

		if($this->channels[$name] ?? FALSE)
		{
			return [$name => $this->channels[$name]];
		}

		$channels = [];

		foreach($this->channels() as $channelName => $channelClass)
		{
			if(!$channelClass)
			{
				continue;
			}			

			if($comboName = $channelClass::compareNames($name, $channelName))
			{
				if($range = $channelClass::deRange($comboName))
				{
					foreach($range as $numChannel)
					{
						$channels += $this->getChannels($numChannel, $client);
					}
					continue;
				}
				else if($channelClass::isWildcard($comboName))
				{
					continue;
				}

				if($client
					&& $channelClass::create($client)
					&& !isset($this->channels[$comboName])
				){

					$this->channels[$comboName] = new $channelClass($this, $comboName);
				}

				$channels[$comboName] = $this->channels[$comboName];
			}
		}

		foreach($this->channels as $channelName => $channel)
		{
			if($channel::compareNames($name, $channelName))
			{
				$channels[$channelName] = $this->channels[$channelName];
			}
		}

		return $channels;
	}

	public function channelExists($name)
	{
		return $this->channels[$name] ?? FALSE;
	}

	public function broadcast($content, $origin = NULL)
	{
		foreach($this->clients as $client)
		{
			if(!$client)
			{
				continue;
			}

			$this->send(
				$content
				, $client
				, $origin
				, $origin ? $origin->id : NULL
			);
		}
	}

	public function subscribe($channelName, ...$clients)
	{
		foreach($clients as $client)
		{
			if($channels = $this->getChannels($channelName, $client))
			{
				foreach($channels as $_channelName => $channel)
				{
					$channel->subscribe($client);

					$this->subscriptions[$client->id][$_channelName] = TRUE;
				}
			}

			// $this->subscriptions[$client->id][$channelName] = TRUE;
		}
	}

	public function unsubscribe($channelName, ...$clients)
	{
		foreach($clients as $client)
		{
			if($channels = $this->getChannels($channelName))
			{
				foreach($channels as $_channelName => $channel)
				{
					$channel->unsubscribe($client);

					unset($this->subscriptions[$client->id][$_channelName]);
				}

				unset($this->subscriptions[$client->id]['*']);
			}

			unset($this->subscriptions[$client->id][$channelName]);
		}
	}

	public function subscriptions($client)
	{
		return $this->subscriptions[$client->id] ?? [];
	}

	public function publish($content, $channelName, $origin = NULL)
	{
		if(!$channels = $this->getChannels($channelName))
		{
			fwrite(STDERR, sprintf(
				"Channel %s does not exist!\n"
				, $channelName
			));

			return;
		}

		foreach($channels as $channel)
		{
			$channel->send(
				$content
				, $origin
				, $channelName
			);
		}
	}

	public function socket()
	{
		if(!$this->socket)
		{
			fwrite(STDERR, "Creating socket...\n");

			$address = \SeanMorris\Ids\Settings::read('websocket', 'address');

			if($this->secure)
			{
				$chainFile  = \SeanMorris\Ids\Settings::read('websocket', 'chainFile');
				$keyFile    = \SeanMorris\Ids\Settings::read('websocket', 'keyFile');
				$caFile     = \SeanMorris\Ids\Settings::read('websocket', 'caFile');
				$passphrase = \SeanMorris\Ids\Settings::read('websocket', 'passphrase');

				$context = stream_context_create([
					'ssl'=>[
						'local_cert'          => $chainFile
						, 'local_pk'          => $keyFile
						// , 'cafile'            => $caFile
						, 'passphrase'        => $passphrase
						, 'allow_self_signed' => TRUE
						, 'verify_peer'       => FALSE
 					]
 				]);
			}
			else
			{
				$context = stream_context_create();
			}

			\SeanMorris\Ids\Log::debug(sprintf('Listening on "%s"', $address));

			$this->socket = stream_socket_server(
				$address
				, $errorNumber
				, $errorString
				, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN
    			, $context
			);
		}

		return $this->socket;
	}

	public function secure()
	{
		return $this->secure;
	}

	protected function dataType($message)
	{
		$type = ord($message[0]);

		if($type > 128)
		{
			$type -= 128;
		}

		return $type;
	}

	protected function decode($message)
	{
		$type = $this->dataType($message);

		$return = FALSE;

		switch($type)
		{
			case(static::MESSAGE_TYPES['close']):
				break;
			case(static::MESSAGE_TYPES['text']):
			case(static::MESSAGE_TYPES['binary']):
				$length = ord($message[1]) & 127;

				if($length == 126)
				{
					$masks = substr($message, 4, 4);
					$data = substr($message, 8);
				}
				else if($length == 127)
				{
					$masks = substr($message, 10, 4);
					$data = substr($message, 14);
				}
				else
				{
					$masks = substr($message, 2, 4);
					$data = substr($message, 6);
				}

				$return = '';

				for ($i = 0; $i < strlen($data); ++$i)
				{
					$return .= $data[$i] ^ $masks[$i%4];
				}
				break;
			case(static::MESSAGE_TYPES['ping']):
				fwrite(STDERR, 'Received a ping!');
				break;
			case(static::MESSAGE_TYPES['pong']):
				fwrite(STDERR, 'Received a ping!');
				break;
		}

		return $return;
	}

	protected function getClient()
	{
		$socket = $this->socket();

		try
		{
			$stream = stream_socket_accept($socket, 0);
		}
		catch(\ErrorException $e)
		{
			if(!$e->getMessage() == 'stream_socket_accept(): accept failed: Connection timed out"')
			{
				throw $e;
			}

			return FALSE;
		}

		$client = new \SeanMorris\Dromez\Socket\Client(
			$stream
			, $this->newClientId++
			, $this->secure
		);

		static::handshake($client);

		if(count($this->clients) >= static::MAX)
		{
			$client->write("Rejected\r\n");
			$this->onReject($client);
			$client->close();

			return FALSE;
		}

		$this->clients[] = $client;

		return $client;
	}

	protected static function handshake($client)
	{
		$client->blocking(TRUE);

		$headers = $client->read(2**16);

		$client->blocking(FALSE);

		if(!preg_match('#^Sec-WebSocket-Key: (\S+)#mi', $headers, $match))
		{
			return;
		}

		$headers = $client->write(
			"HTTP/1.1 101 Switching Protocols\r\n"
				. "Upgrade: websocket\r\n"
				. "Connection: Upgrade\r\n"
				. "Sec-WebSocket-Accept: " . base64_encode(
					sha1($match[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', TRUE)
				)
				. "\r\n\r\n"
		);
	}

	protected function onConnect($client)
	{
		fwrite(STDERR, sprintf(
			"Accepting client #%d...\n"
			, $client->id
		));
	}

	protected function onReject($client)
	{
		fwrite(STDERR, "Rejecting client...\n");
	}

	protected function onReceive($message, $client, $type)
	{
		
	}

	protected function onDisconnect($client)
	{
		fwrite(STDERR, sprintf(
			"Disconnecting client #%d...\n"
			, $client->id
		));
	}

	protected function onTick()
	{
		$this->broadcast('Now: ' . microtime(TRUE));
	}

	protected function onError($error)
	{

	}

	public function __get($name)
	{
		return $this->$name;
	}
}
