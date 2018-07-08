<?php
namespace SeanMorris\Dromez\Idilic\Route;
class RootRoute implements \SeanMorris\Ids\Routable
{
	public function index()
	{
		$server = new \SeanMorris\Dromez\Socket\DromezServer;

		$server->listen();
	}

	public function xml()
	{
		$client = new \SeanMorris\Dromez\XmlRpc\Client(
			'http://localhost:9002/RPC2'
		);

		var_dump( $client->{'system.listMethods'}() );
	}
}
