<?php
namespace SeanMorris\Dromez\Socket;
class PingChannel extends \SeanMorris\Dromez\Socket\Channel
{
	public function send($content, $origin, $originalChannel = NULL)
	{
		$response = ['time' => microtime(TRUE)];

		if(Channel::compareNames($this->name, 'ping:*:pong'))
		{
			$response['nick'] = $origin->context['__nickname'] ?? NULL;
		}

		parent::send(
			$response
			, $origin
			, $originalChannel
		);
	}
}
