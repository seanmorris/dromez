<?php
namespace SeanMorris\Dromez\Socket;
class DromezServer extends Server
{
	const FREQUENCY = 60
		, MAX       = 100;

	protected $userContext = [];

	protected function onConnect($client)
	{
		fwrite(STDERR, sprintf(
			"Accepting client 0x%04X (%d/%d)...\n"
			, $client->id
			, count($this->clients)
			, static::MAX
		));

		$this->send(
			sprintf('Hi, 0x%04X!', $client->id)
			, $client
			, $this
		);
	}

	protected function onReject($client)
	{
		fwrite(STDERR, "Rejecting client...\n");
	}

	protected function onReceive($message, $client, $type)
	{
		parent::onReceive($message, $client, $type);

		$defaultContext = [
			'__server'     => $this
			, '__client'   => $client
			, '__clientId' => $client->id
			, '__authed'   => FALSE
		];

		if(!isset($this->userContext[$client->id]))
		{
			$this->userContext[$client->id] = $defaultContext;

			$client->setContext($this->userContext[$client->id]);
		}

		$response = FALSE;

		if($type == static::MESSAGE_TYPES['text'])
		{
			$path = new \SeanMorris\Ids\Path(...preg_split('/\s+/', $message));
			$routes   = new Route;
			$request  = new \SeanMorris\Ids\Request(['path' => $path]);
			$router   = new \SeanMorris\Ids\Router($request, $routes);

			$router->setContext($this->userContext[$client->id]);

			$response = $router->route();
		}
		else if($type == static::MESSAGE_TYPES['binary'])
		{
			$channels  = $this->channels();
			$channelId = unpack('vchan', $message, 0)['chan'];

			$finalMessage = '';

			for($i = 2; $i < strlen($message); $i++)
			{
				$finalMessage .= $message[$i];
			}

			$channels = $this->getChannels($channelId);

			foreach($channels as $channel)
			{
				$channel->send($finalMessage, $client);
			}

			$response = NULL;
		}

		if($response === FALSE)
		{
			$this->send(
				sprintf('Command "%s" not valid.', $message)
				, $client
				, $this
			);
		}
		else
		{
			$this->send($response, $client, $this);
		}
	}

	protected function onDisconnect($client)
	{
		fwrite(STDERR, sprintf(
			"Disconnecting client #%d...\n"
			, $client->id
		));

		unset($this->userContext[$client->id]);
	}

	protected function onTick()
	{
		static $time, $slowTime, $medTime = 0;

		$this->publish(
			['time' => microtime(TRUE)]
			, 'time:heavy'
			, $this
		);

		$this->publish(
			microtime(TRUE)
			, 12300
			, $this
		);

		if($time != time())
		{
			$this->publish(
				['time' => microtime(TRUE)]
				, 'time:light'
				, $this
			);

			$this->publish(
				microtime(TRUE)
				, 12301
				, $this
			);

			$time = time();
		}

		$_medTime = microtime(TRUE);

		if(($_medTime - $medTime) > 0.25)
		{
			$this->publish(
				['time' => microtime(TRUE)]
				, 'time:medium'
				, $this
			);

			$medTime = $_medTime;
		}

		$_slowTime = (int)(time() / 10);

		if($slowTime != $_slowTime)
		{
			$this->publish(
				['time' => microtime(TRUE)]
				, 'time:slow'
				, $this
			);

			$slowTime = $_slowTime;
		}
	}

	protected function onError($error)
	{

	}

	public function send($content, $client, $origin = NULL, $channel = NULL, $originalChannel = NULL)
	{
		if(!is_numeric($channel)
			&& $content !== FALSE
			&& $content !== NULL
		){
			$originType = NULL;

			if($origin instanceof \SeanMorris\Dromez\Socket\Server)
			{
				$originType = 'server';
				$originId   = NULL;
			}
			else if($origin instanceof \SeanMorris\Dromez\Socket\Client)
			{
				$originType = 'user';
				$originId   = $origin->id;
			}

			$message = [
				'message'  => $content
				, 'origin' => $originType
			];

			if(isset($originId))
			{
				$message['originId'] = $originId;
			}

			if(isset($channel))
			{
				$message['channel'] = $channel;

				if(isset($originalChannel) && $channel !== $originalChannel)
				{
					$message['originalChannel'] = $originalChannel;
				}
			}

			parent::send(json_encode($message), $client, $origin, $channel);
		}
		else if($content !== NULL)
		{
			if(is_numeric($channel))
			{
				$header = pack(
					'vvv'
					, $origin instanceof \SeanMorris\Dromez\Socket\Server
						? 0
						: 1
					, $origin instanceof \SeanMorris\Dromez\Socket\Server
						? 0
						: $origin->id
					, $channel
				);

				if(is_numeric($content))
				{
					if(is_int($content))
					{
						$content = pack('l', $content);
					}
					else if(is_float($content))
					{
						$content = pack('e', $content);
					}
				}

				$content = $header . $content;
			}

			parent::send($content, $client, $origin, $channel);
		}
	}

	public function channels()
	{
		return [
			'ping:announce'        => 'SeanMorris\Dromez\Socket\PingChannel'
			, 'ping:announce:pong' => 'SeanMorris\Dromez\Socket\PingChannel'
			, 'game:*:chat'        => 'SeanMorris\Dromez\Socket\ChatChannel'
			, 'game:*:stream'      => 'SeanMorris\Dromez\Socket\ChatChannel'

			, 'chat:main'   => 'SeanMorris\Dromez\Socket\ChatChannel'
			, 'chat:alt'    => 'SeanMorris\Dromez\Socket\ChatChannel'
			, 'chat:*'      => 'SeanMorris\Dromez\Socket\ChatChannel'
			, 'time:slow'   => 'SeanMorris\Dromez\Socket\ServerChannel'
			, 'time:light'  => 'SeanMorris\Dromez\Socket\ServerChannel'
			, 'time:medium' => 'SeanMorris\Dromez\Socket\ServerChannel'
			, 'time:heavy'  => 'SeanMorris\Dromez\Socket\ServerChannel'
			, 'time:*'      => 'SeanMorris\Dromez\Socket\ServerChannel'

			, 0x0000 => 'SeanMorris\Dromez\Socket\DataChannel'
			, 0x0001 => 'SeanMorris\Dromez\Socket\DataChannel'

			, 80    => 'SeanMorris\Dromez\Socket\DataChannel'
			, 97    => 'SeanMorris\Dromez\Socket\DataChannel'
			, 0x29A   => 'SeanMorris\Dromez\Socket\DataChannel'
			, 12300 => 'SeanMorris\Dromez\Socket\DataChannel'
			, 12301 => 'SeanMorris\Dromez\Socket\DataChannel'

			// , 0x0500 => 'SeanMorris\Dromez\Socket\DataChannel'
			, 0x0500 . '-' . 0x0600
				 => 'SeanMorris\Dromez\Socket\DataChannel'

			, 0xFFFF => 'SeanMorris\Dromez\Socket\DataChannel'

			, '*'   => FALSE
		];
	}
}

