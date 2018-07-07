<?php
namespace SeanMorris\Dromez\Idilic\Route;
class RootRoute implements \SeanMorris\Ids\Routable
{
	public function index()
	{
		$server = new \SeanMorris\Dromez\Socket\Server;

		$server->listen();
	}

	public function xml()
	{
		$server = new \SeanMorris\Dromez\XmlRpc\Server(
			'http://localhost:9002/RPC2'
		);

		var_dump( $server->{'system.listMethods'}() );
	}
}
