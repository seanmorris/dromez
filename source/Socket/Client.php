<?php
namespace SeanMorris\Dromez\Socket;
class Client
{
	protected $id, $stream;

	public function __construct($server)
	{
		$socket = $server->socket();

		$this->stream = stream_socket_accept($socket, 0);

		if($server->secure())
		{
			stream_socket_enable_crypto(
				$this->stream
				, TRUE
				, STREAM_CRYPTO_METHOD_SSLv23_SERVER
			);
		}

		stream_set_blocking($this->stream, FALSE);

		$this->id = $server->addClient($this);
	}

	public function blocking($val)
	{
		if(!$this->stream)
		{
			return;
		}

		stream_set_blocking($this->stream, $val);
	}

	public function write($bytes)
	{
		if(!$this->stream)
		{
			return;
		}

		fwrite($this->stream, $bytes);
	}

	public function read($bytes)
	{
		if(!$this->stream)
		{
			return;
		}

		return fread($this->stream, $bytes);
	}

	public function close()
	{
		if(!$this->stream)
		{
			return;
		}

		fclose($this->stream);

		$this->stream = FALSE;
	}

	public function __get($name)
	{
		return $this->$name;
	}
}
