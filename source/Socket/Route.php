<?php
namespace SeanMorris\Dromez\Socket;
class Route implements \SeanMorris\Ids\Routable
{
	public function echo($router)
	{
		$line = $router->path()->consumeNodes();

		return implode(' ', $line);
	}

	public function time($router)
	{
		return date('Y-m-d G:i:s');
	}

	public function inc($router)
	{
		$userInt = $router->contextGet('userInt');

		$userInt++;

		$router->contextSet('userInt', $userInt);

		return $userInt;
	}

	public function dec($router)
	{
		$userInt = $router->contextGet('userInt');

		$userInt--;

		$router->contextSet('userInt', $userInt);

		return $userInt;
	}

	public function pecho($router)
	{
		$router->contextSet('__currentPath', 'pecho');

		$server = $router->contextGet('__server');
		$client = $router->contextGet('__client');

		$line = $router->path()->consumeNodes();

		$server->send(implode(' ', $line), $client);
	}

	public function chat($router)
	{
		$router->contextSet('__currentPath', 'chat');

		$server   = $router->contextGet('__server');
		$client   = $router->contextGet('__client');
		$clientId = $router->contextGet('__clientId');

		if(!$router->contextGet('chat:channel'))
		{
			$router->contextSet('chat:channel', 'main');
			$server->subscribe(
				'chat:' . $router->contextGet('chat:channel')
				, $clientId
			);
		}

		$line = $router->path()->consumeNodes();

		$message = implode(' ', array_filter($line));

		if($message)
		{
			if($message == '\quit')
			{
				$router->contextSet('__currentPath', NULL);
				$router->contextSet('chat:channel', NULL);
				$server->unsubscribe(
					'chat:' . $router->contextGet('chat:channel')
					, $clientId
				);
				return;
			}

			if($line[0] == '\join')
			{
				$server->unsubscribe(
					'chat:' . $router->contextGet('chat:channel')
					, $clientId
				);

				$router->contextSet('chat:channel', $line[1]);

				$server->subscribe(
					'chat:' . $router->contextGet('chat:channel')
					, $clientId
				);

				return;
			}

			$server->publish(
				sprintf(
					'<%s::%d>: %s'
					, $router->contextGet('chat:channel')
					, $clientId
					, $message
				)
				, 'chat:' . $router->contextGet('chat:channel')
			);
		}
	}

	public function pub($router)
	{
		$args     = $router->path()->consumeNodes();
		$server   = $router->contextGet('__server');
		$clientId = $router->contextGet('__clientId');

		if(count($args) < 2)
		{
			return;
		}

		$channel = array_shift($args);

		$server->publish(implode(' ', $args), $channel);
	}

	public function sub($router)
	{
		$args     = $router->path()->consumeNodes();
		$server   = $router->contextGet('__server');
		$clientId = $router->contextGet('__clientId');

		if(count($args) < 1)
		{
			return;
		}

		$server->subscribe($args[0], $clientId);

		var_dump($server, $args);

		return sprintf('You\'ve subscribed to %s', $args[0]);
	}

	public function unsub($router)
	{
		$args     = $router->path()->consumeNodes();
		$server   = $router->contextGet('__server');
		$clientId = $router->contextGet('__clientId');

		if(count($args) < 1)
		{
			return;
		}

		$server->unsubscribe($args[0], $clientId);

		var_dump($server, $args);

		return sprintf('You\'ve unsubscribed from %s', $args[0]);
	}
}
