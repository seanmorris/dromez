<?php
namespace SeanMorris\Dromez\Socket;
class Route implements \SeanMorris\Ids\Routable
{
	public function echo($router)
	{
		$server = $router->contextGet('__server');
		$client = $router->contextGet('__client');
		$line   = $router->path()->consumeNodes();

		$server->send(
			implode(' ', $line)
			, $client
			, 'user'
			, $client->id
		);
	}

	public function time($router)
	{
		return ['time' => microtime(TRUE)];
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

	public function pub($router)
	{
		$args   = $router->path()->consumeNodes();
		$server = $router->contextGet('__server');
		$client = $router->contextGet('__client');

		if(!$router->contextGet('__authed'))
		{
			return;
		}

		if(count($args) < 1)
		{
			return;
		}

		$channelName = array_shift($args);

		$server->publish(implode(' ', $args), $channelName, $client);
	}

	public function sub($router)
	{
		$args   = $router->path()->consumeNodes();
		$server = $router->contextGet('__server');
		$client = $router->contextGet('__client');
		
		if(!$router->contextGet('__authed'))
		{
			return;
		}

		if(count($args) < 1)
		{
			return;
		}

		$channels = $server->channels();

		foreach($channels as $channelName => $channel)
		{
			if(!$channel)
			{
				continue;
			}

			if($channel::isWildcard($channelName))
			{
				if(!($channels[$args[0]] ?? FALSE))
				{
					if($channel::create($client))
					{
						$server->subscribe($args[0], $client);
					}
				}
				continue;
			}

			if($channel::compareNames($args[0], $channelName))
			{
				$server->subscribe($channelName, $client);
			}
		}

		return $this->subs($router);
	}


	public function subs($router)
	{
		$args   = $router->path()->consumeNodes();
		$server = $router->contextGet('__server');
		$client = $router->contextGet('__client');
		
		if(!$router->contextGet('__authed'))
		{
			return;
		}

		return [
			'subscriptions' => array_keys(array_filter(
				$server->subscriptions($client)
			))
		];
	}

	public function unsub($router)
	{
		$args   = $router->path()->consumeNodes();
		$server = $router->contextGet('__server');
		$client = $router->contextGet('__client');

		if(count($args) < 1)
		{
			return;
		}

		$channels = $server->channels();

		foreach($channels as $channel => $channelClass)
		{
			if(!$channelClass)
			{
				continue;
			}

			if($channelClass::isWildcard($channel))
			{
				continue;
			}

			if($channelClass::compareNames($args[0], $channel))
			{
				$server->unsubscribe($channel, $client);
			}
		}

		return sprintf('You\'ve unsubscribed from %s', $args[0]);
	}

	public function channels($router)
	{
		$args     = $router->path()->consumeNodes();
		$server   = $router->contextGet('__server');

		$channels = $server->channels();

		// unset($channels['*']);

		return [
			'channels' => array_keys($channels)
		];
	}

	public function commands()
	{
		$reflection = new \ReflectionClass(get_class());
		$methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
		
		return ['commands' => array_map(
			function($method)
			{
				return $method->name;
			}
			, $methods
		)];
	}
}
