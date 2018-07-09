<?php
namespace SeanMorris\Dromez\Socket;
class PingChannel extends \SeanMorris\Dromez\Socket\Channel
{
	public function send($content, $origin, $originalChannel = NULL)
	{
		parent::send(
			['time' => microtime(TRUE)]
			, $origin
			, $originalChannel
		);
	}
}
