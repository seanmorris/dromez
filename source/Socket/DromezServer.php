<?php
namespace SeanMorris\Dromez\Socket;
class DromezServer extends Server
{
	protected function onConnect($client, $clientId)
	{
		fwrite(STDERR, sprintf(
			"Accepting client #%d...\n"
			, count($this->clients)
		));

		$this->send('Hi!', $client);

		$token = new \SeanMorris\Dromez\Jwt\Token([
			'time'  => microtime(TRUE)
			, 'uid' => 0
		]);

		$this->send($token, $client);
	}

	protected function onReject($client)
	{
		fwrite(STDERR, "Rejecting client...\n");
	}

	protected function onReceive($message, $clientId)
	{
		// fwrite(STDERR, sprintf(
		// 	"[#%d][%s] Message Received:\n\t%s\n"
		// 	, $clientId
		// 	, date('Y-m-d H:i:s')
		// 	, $message
		// ));

		if(\SeanMorris\Dromez\Jwt\Token::verify($message))
		{
			fwrite(STDERR, sprintf(
				"Client #%d authentiated!\n"
				, $clientId
			));
		}
		else
		{
			var_dump(json_decode($message));
		}
	}

	protected function onDisconnect($client, $clientId)
	{
		fwrite(STDERR, sprintf(
			"Disconnecting client #%d...\n"
			, $clientId
		));
	}

	protected function onTick()
	{
		$this->broadcast('Now: ' . microtime(TRUE));
	}

	protected function onError($error, $clientId)
	{

	}
}
