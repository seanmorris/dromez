<?php
namespace SeanMorris\Dromez\Socket;
class ServerChannel extends \SeanMorris\Dromez\Socket\Channel
{
	public function send($content, $origin, $originalChannel = NULL)
	{
		if(!$origin instanceof \SeanMorris\Dromez\Socket\Server)
		{
			return;
		}

		parent::send($content, $origin, $originalChannel);
	}
}
