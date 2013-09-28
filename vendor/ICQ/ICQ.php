<?php
/**
 * 
 */
class Icq extends Icq_FLAP {
	
	public function __construct($uin, $pass)
	{
		$this->connect($uin, $pass);
	}

	
	public function is_connected()
	{
		if( ! $this->socket || socket_last_error($this->socket))
		{
			$this->error = socket_strerror(socket_last_error($socket));
			return FALSE;
		}
		return TRUE;
	}

	
	public function connect($uin, $pass)
	{
		if ( ! $this->open()) 
		{
			return FALSE;
		}
		return $this->login($uin, $pass);
	}

}