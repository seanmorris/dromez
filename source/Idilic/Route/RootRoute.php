<?php
namespace SeanMorris\Dromez\Idilic\Route;
class RootRoute implements \SeanMorris\Ids\Routable
{
	public function index()
	{
		$server = new \SeanMorris\Dromez\Socket\Server;

		$server->listen();
	}
}