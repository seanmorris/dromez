<?php
namespace SeanMorris\Dromez\Socket;
class Channel
{
	const SEPARATOR = ':';
	protected
		$server
		, $name
		, $subscribers = []
	;

	public function __construct($server, $name)
	{
		$this->server = $server;
		$this->name   = $name;
	}

	public static function isWildcard($name)
	{
		return preg_match('/\*/', $name);
	}

	public static function compareNames($a, $b)
	{
		$result = [];
		$splitA = explode(static::SEPARATOR, $a);
		$splitB = explode(static::SEPARATOR, $b);
		$countA = count($splitA);
		$countB = count($splitB);
		$nodes  = $countA;

		if($nodes < $countB)
		{
			$nodes = $countB;
		}

		for($i = 0; $i < $nodes; $i++)
		{
			if(count($splitA) > $i)
			{
				$cmpA = $splitA[$i];
			}
			else if($splitA[ $countA - 1] == '*')
			{
				$cmpA = $splitA[ $countA - 1];
			}
			else
			{
				return FALSE;
			}

			if(count($splitB) > $i)
			{
				$cmpB = $splitB[$i];
			}
			else if($splitB[ $countB - 1] == '*')
			{
				$cmpB = $splitB[ $countB - 1];
			}
			else
			{
				return FALSE;
			}

			if($cmpA !== $cmpB)
			{
				if($cmpA !== '*' && $cmpB !== '*')
				{
					return FALSE;
				}
			}

			$result[] = $cmpA !== '*' ? $cmpA : $cmpB;
		}

		return implode(static::SEPARATOR, $result);
	}

	public function subscribe($client)
	{
		foreach($this->subscribers as $index => $subscriber)
		{
			if($subscriber === $client)
			{
				return;
			}
		}

		$this->subscribers[] = $client;
	}

	public function unsubscribe($client)
	{
		foreach($this->subscribers as $index => $subscriber)
		{
			if($subscriber === $client)
			{
				unset($this->subscribers[$index]);
			}
		}
	}

	public function send($content, $origin, $originalChannel = NULL)
	{
		foreach($this->subscribers as $client)
		{
			$this->server->send(
				$content
				, $client
				, $origin
				, $this->name
				, $originalChannel
			);
		}
	}

	public static function create($user)
	{
		return FALSE;
	}
}
