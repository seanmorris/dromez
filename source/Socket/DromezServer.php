<?php
namespace SeanMorris\Dromez\Socket;
class DromezServer extends Server
{
	const ADDRESS   = 'dromez:9999'
		, FREQUENCY = 120
		, MAX       = 100;

	protected $userContext = [];

	protected function onConnect($client, $clientId)
	{
		fwrite(STDERR, sprintf(
			"Accepting client #%d...\n"
			, $clientId
		));

		$this->send(json_encode([
			'message'  => sprintf(
				'Hi, #%d!'
				, $clientId
			)
			, 'origin' => 'server'
		]), $client);
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

		$defaultContext = [
			'__server'     => $this
			, '__client'   => $this->clients[$clientId]
			, '__clientId' => $clientId
			, '__authed'   => FALSE
		];

		if(\SeanMorris\Dromez\Jwt\Token::verify($message))
		{
			$this->send(json_encode([
				'message'  => sprintf(
					'You\'re authenticated, #%d!'
					, $clientId
				)
				, 'origin' => 'server'
			]), $this->clients[$clientId]);

			fwrite(STDERR, sprintf(
				"Client #%d authentiated!\n"
				, $clientId
			));

			$this->userContext[$clientId] = $defaultContext;
			$this->userContext[$clientId]['__authed'] = TRUE;
		}
		else
		{
			fwrite(STDERR, sprintf(
				"Message Received from %d!\n\t%s\n"
				, $clientId
				, $message
			));

			$context = [];
			
			$path = new \SeanMorris\Ids\Path(...preg_split('/[\s\/]/', $message));

			if(isset($this->userContext[$clientId]))
			{
				$context =& $this->userContext[$clientId];

				if(isset($context['__currentPath']))
				{
					$path = $path->unshift($context['__currentPath']);
				}
			}
			else
			{
				$context = $defaultContext;
			}

			if($message == '\kill')
			{
				unset($context['__currentPath']);
				return;
			}

			if($message == '\unsub')
			{
				$this->subscriptions[$clientId] = [];
				return;
			}

			$routes   = new Route;
			$request  = new \SeanMorris\Ids\Request(['path' => $path]);
			$router   = new \SeanMorris\Ids\Router($request, $routes);

			$router->setContext($context);

			$response = $router->route();

			$response = json_encode([
				'message'  => $response
				, 'origin' => 'server'
			]);

			if(isset($this->clients[$clientId]))
			{
				if($response === FALSE)
				{

					$this->send(sprintf(
						'Command "%s" not valid.'
						, $message
					), $this->clients[$clientId]);
				}
				else
				{
					$this->send($response, $this->clients[$clientId]);
				}
			}
		}
	}

	protected function onDisconnect($client, $clientId)
	{
		fwrite(STDERR, sprintf(
			"Disconnecting client #%d...\n"
			, $clientId
		));

		unset($this->userContext[$clientId]);
	}

	protected function onTick()
	{
		static $time;
		$this->publish(microtime(TRUE), 'time:heavy');

		if($time != time())
		{
			$this->publish(microtime(TRUE), 'time:light');
			$time = time();
		}

		$this->broadcast(NULL);
	}

	protected function onError($error, $clientId)
	{

	}
}
