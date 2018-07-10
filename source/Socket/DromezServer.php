<?php
namespace SeanMorris\Dromez\Socket;
class DromezServer extends Server
{
	const ADDRESS   = 'dromez:9999'
		, FREQUENCY = 60
		, MAX       = 100;

	protected $userContext = [];

	protected function onConnect($client)
	{
		fwrite(STDERR, sprintf(
			"Accepting client #%d...\n"
			, $client->id
		));

		$this->send(
			sprintf('Hi, #%d!', $client->id)
			, $client
			, $this
		);
	}

	protected function onReject($client)
	{
		fwrite(STDERR, "Rejecting client...\n");
	}

	protected function onReceive($message, $client)
	{
		fwrite(STDERR, sprintf(
			"[#%d][%s] Message Received:\n\t%s\n"
			, $client->id
			, date('Y-m-d H:i:s')
			, $message
		));

		$defaultContext = [
			'__server'     => $this
			, '__client'   => $client
			, '__clientId' => $client->id
			, '__authed'   => FALSE
		];

		$context = [];
		
		$path = new \SeanMorris\Ids\Path(...preg_split('/\s/', $message));

		if(!isset($this->userContext[$client->id]))
		{
			$this->userContext[$client->id] = $defaultContext;

			$client->setContext($this->userContext[$client->id]);
		}

		$context =& $this->userContext[$client->id];

		if($message == '\unsub')
		{
			$this->subscriptions[$client->id] = [];

			foreach($this->channels as $channel)
			{
				$channel->unsubscribe($client);
			}

			return;
		}

		$routes   = new Route;
		$request  = new \SeanMorris\Ids\Request(['path' => $path]);
		$router   = new \SeanMorris\Ids\Router($request, $routes);

		$router->setContext($context);

		$response = $router->route();

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
			$this->send(
				$response
				, $client
				, $this
			);
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

		// $this->broadcast(NULL);

		$this->publish(
			['time' => microtime(TRUE)]
			, 'time:heavy'
			, $this
		);

		if($time != time())
		{
			$this->publish(
				['time' => microtime(TRUE)]
				, 'time:light'
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
		if($content !== FALSE && $content !== NULL)
		{
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


			parent::send(json_encode($message), $client);
		}
		else
		{
			parent::send($content, $client);
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
			, '*'           => FALSE
		];
	}
}

