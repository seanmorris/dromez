<?php
namespace SeanMorris\Dromez\Socket;
class Server
{
	const
		ADDRESS = '0.0.0.0'
		, PORT  = 60606
		, MAX   = 10;

	protected
		$socket    = NULL
		, $clients = []
		, $sockets = [];

	public function __construct()
	{

	}

	public function listen()
	{
		fwrite(STDERR, "Listening...\n");

		while(TRUE)
		{
			// fwrite(STDERR, "Looping...\n");

			sleep(1);

			if($newClient = $this->getClient())
			{
				fwrite(STDERR, "Accepting client...\n");

				static::handshake($newClient);
				$this->clients[] = $newClient;

				$this->send('H!', $newClient);
			}

			foreach($this->clients as $clientId => $client)
			{
				// fwrite(STDERR, sprintf("Checking client #%d...\n", $clientId));

				if(!$client)
				{
					continue;
				}

				$message = '';

				while(($chunk = socket_read($client, 1024)) !== FALSE)
				{
					if(empty($chunk) && empty($message))
					{
						$this->clients[$clientId] = FALSE;
						break;
					}

					if(empty($chunk))
					{
						break;
					}

					$message .= $chunk;
				}
				
				$this->receive($message, $clientId);
			}

			$this->broadcast('Now: ' . time());
		}
	}

	public function receive($content)
	{
		var_dump($content);
	}

	public function send($content, $client)
	{
		$response = chr(129) . chr(strlen($content)) . $content;
		socket_write($client, $response);
	}

	public function broadcast($content)
	{
		// printf(
		// 	"%d: %d clients connected.\n"
		// 	, time()
		// 	, count(array_filter($this->clients))
		// );

		foreach($this->clients as $client)
		{
			if(!$client)
			{
				continue;
			}

			$response = chr(129) . chr(strlen($content)) . $content;
			socket_write($client, $response);
		}
	}

	protected function getClient()
	{
		if(!$this->socket)
		{
			$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

			socket_set_option(
				$this->socket
				, SOL_SOCKET
				, SO_REUSEADDR
				, 1
			);
			
			socket_bind(
				$this->socket
				, static::ADDRESS
				, static::PORT
			);
			
			socket_listen($this->socket);

			socket_set_nonblock($this->socket);
		}

		$client = socket_accept($this->socket);

		return $client;
	}

	protected static function handshake($client)
	{
		socket_set_block($client);
		$request = socket_read($client, 5000);
		socket_set_nonblock($client);
		// var_dump($request);
		if(preg_match('/Sec-WebSocket-Key: (.*)\r\n/', $request, $matches))
		{
			$key = base64_encode(pack(
			'H*',
			sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')
			));
			$headers = "HTTP/1.1 101 Switching Protocols\r\n";
			$headers .= "Upgrade: websocket\r\n";
			$headers .= "Connection: Upgrade\r\n";
			$headers .= "Sec-WebSocket-Version: 13\r\n";
			$headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";
			socket_write($client, $headers, strlen($headers));
		}
	}
}
