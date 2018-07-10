<?php
namespace SeanMorris\Dromez\Socket;
class ChatChannel extends \SeanMorris\Dromez\Socket\Channel
{
	public function send($content, $origin, $originalChannel = NULL)
	{
		parent::send(
			[
				'content' => $content
				, 'time'  => microtime(TRUE)
				, 'nick'  => $origin->context['__nickname'] ?? NULL
			]
			, $origin
			, $originalChannel
		);
	}

	public static function create($user)
	{
		return TRUE;
	}
}
