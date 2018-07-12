<?php
namespace SeanMorris\Dromez\Home;
class Route implements \SeanMorris\Ids\Routable
{
	public function index()
	{
		return '';
	}

	public function auth()
	{
		return new \SeanMorris\Dromez\Jwt\Token([
			'time'  => microtime(TRUE)
			, 'uid' => 1
		]);
	}

	public function __construct()
	{
		if(isset($_SERVER['HTTP_REFERER']) && $corsDomains = \SeanMorris\Ids\Settings::read('corsDomains'))
		{
			$referrer = parse_url($_SERVER['HTTP_REFERER']);
			$referrerDomain = sprintf('%s://%s', $referrer['scheme'], $referrer['host']);

			if(isset($referrer['port']))
			{
				$referrerDomain .= ':' . $referrer['port'];
			}

			$corsDomainsIndex = array_flip($corsDomains);

			if(isset($corsDomainsIndex[$referrerDomain]))
			{
				$index = $corsDomainsIndex[$referrerDomain];

				header(sprintf('Access-Control-Allow-Origin: %s', $corsDomains[$index]));
				header('Access-Control-Allow-Credentials: true');
				header('Access-Control-Allow-Methods: GET,POST');
				header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
			}
		}
	}
}
