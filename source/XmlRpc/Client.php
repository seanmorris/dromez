<?php
namespace SeanMorris\Dromez\XmlRpc;
class Client
{
	protected $url;

	public function __construct($url)
	{
		$this->url = $url;
	}

	public function __call($method, $args)
	{
		$request = xmlrpc_encode_request($method, $args);
	
		$context = stream_context_create(['http' => [
			'method'    => 'POST'
			, 'header'  => 'Content-Type: text/xml'
			, 'content' => $request
		]]);

		$stream = fopen(
			$this->url
			, 'r'
			, FALSE
			, $context
		);

		$rawResponse = NULL;

		while(!feof($stream))
		{
			$rawResponse .= fread($stream, 2**10);
		}

		$response = xmlrpc_decode($rawResponse);

		return $response;
	}
}
