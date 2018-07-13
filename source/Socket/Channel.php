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
		return preg_match('/\*/', $name)
			|| static::isRange($name);
	}

	public static function isRange($name)
	{
		return preg_match('/\d-\d/', $name);
	}

	public static function deRange($name)
	{
		if(!static::isRange($name))
		{
			return [];
		}
		else
		{
			list($start, $end) = explode('-', $name);

			return range($start, $end);
		}
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

			$returnNode = $cmpA !== '*' ? $cmpA : $cmpB;

			if($cmpA !== $cmpB)
			{
				if($cmpA !== '*' && $cmpB !== '*')
				{
					$rangeForm = '/^(\d+)\-?(\d+)?$/';

					$mA = preg_match($rangeForm, $cmpA, $groupA);
					$mB = preg_match($rangeForm, $cmpB, $groupB);

					if($mA && $mB)
					{
						$a1 = $groupA[1];
						$a2 = $groupA[1];
						$b1 = $groupB[1];
						$b2 = $groupB[1];

						if(isset($groupA[2]))
						{
							$a2 = $groupA[2];
						}

						if(isset($groupB[2]))
						{
							$b2 = $groupB[2];
						}

						if($a1 >= $b1 && $a2 <= $b2)
						{
							$returnNode = "$a1-$a2";

							if($a1 == $a2)
							{
								$returnNode = (int) $a1;
							}
						}
						else if($a1 <= $b1 && $a2 >= $b2)
						{
							$returnNode = "$b1-$b2";

							if($b1 == $b2)
							{
								$returnNode = (int) $b2;
							}
						}
						else if($a2 <= $b2 && $a2 >= $b1)
						{
							$returnNode = "$b1-$a2";

							if($b1 == $a2)
							{
								$returnNode = (int) $b1;
							}
						}
						else if($a1 <= $b2 && $a1 >= $b1)
						{
							$returnNode = "$a1-$b2";

							if($a1 == $b2)
							{
								$returnNode = (int) $a1;
							}
						}
						else if($b2 <= $a2 && $b2 >= $a1)
						{
							$returnNode = "$a1-$b2";

							if($a1 == $b2)
							{
								$returnNode = (int) $a1;
							}
						}
						else if($b1 <= $a2 && $b1 >= $a1)
						{
							$returnNode = "$b1-$a2";

							if($b1 == $a2)
							{
								$returnNode = (int) $b1;
							}
						}
						else
						{
							return FALSE;
						}
					}
					else
					{
						return FALSE;
					}

				}
			}

			$result[] = $returnNode;
		}

		if(!$result)
		{
			return FALSE;
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
