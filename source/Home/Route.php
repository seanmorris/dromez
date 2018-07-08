<?php
namespace SeanMorris\Dromez\Home;
class Route implements \SeanMorris\Ids\Routable
{
	public function index()
	{
		$token = new \SeanMorris\Dromez\Jwt\Token([
			'uid' => 1
		]);

		$strReToken = (string)$token;

		$parts      = explode('.', $strReToken);
		
		$replace    = json_decode(base64_decode($parts[1]));

		$replace->uid = 2;

		$parts[1]   = base64_encode(json_encode($replace));

		$reToken    = implode('.', $parts);

		var_dump((string)$token, $reToken);

		var_dump(\SeanMorris\Dromez\Jwt\Token::verify($token));
		var_dump(\SeanMorris\Dromez\Jwt\Token::verify($reToken));

		die;
	}
}
