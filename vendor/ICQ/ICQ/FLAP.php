<?php
/**
 * 
 */
class ICQ_FLAP extends ICQ_SNAC {

	public $socket;
	public $command = 0x2A;
	public $channel;
	public $sequence;
	public $body;
	public $info = array();

	
	public function __construct()
	{
		$this->sequence = rand(1, 30000);
	}

	
	public function get_flap()
	{
		if( ! empty($this->socket) AND ! socket_last_error($this->socket))
		{
			$header = socket_read($this->socket, 6);
			if ($header) 
			{
				$header = unpack('c2channel/n2size', $header);
				$this->channel = $header['channel2'];
				$this->body = socket_read($this->socket, $header['size2']);
				return TRUE;
			}
			else 
			{
				return FALSE;
			}
		}
	}

	public function parce_cookie_flap()
	{
		$this->get_flap();
		$this->info = array();
		while ($this->body != '')
		{
			$info = $this->get_tlv($this->body);
			$key = array_search($this->type, $this->types);
			if ($key)
			{
				$this->info[$key] = $info;
			}
			$this->body = substr($this->body, ($this->size+4));
		}
	}

	
	public function parse_answer_flap()
	{
		$this->get_flap();
		$array = unpack('n3int/Nint', $this->body);
		while ($array['int'] != $this->request_id) 
		{
			$this->get_flap();
			$array = unpack('n3int/Nint', $this->body);
		}
		
		$this->error = 'Unknown serwer answer';
		if ($array['int1'] == 4) 
		{
			switch ($array['int2']) 
			{
				case 1:
					$this->error = 'Error to sent message';
					return FALSE;
				case 0x0c:
					return TRUE;
			}
		}
		$this->error = 'Unknown serwer answer';
		return FALSE;
	}

	
	public function prepare()
	{
		$this->sequence++;
		$out = pack('ccnn', $this->command, $this->channel, $this->sequence, strlen($this->body)).$this->body;
		return $out;
	}

	
	public function login($uin, $password)
	{
		$this->get_flap();
		$this->uin = $uin;
		
		$this->body .= $this->set_tlv('UIN',              $uin);
		$this->body .= $this->set_tlv('DATA',             $this->xorpass($password));
		$this->body .= $this->set_tlv('CLIENT',           'ICQBasic');
		$this->body .= $this->set_tlv('CLIENT_ID',        266, 2);
		$this->body .= $this->set_tlv('CLI_MAJOR_VER',    20, 2);
		$this->body .= $this->set_tlv('CLI_MINOR_VER',    34, 2);
		$this->body .= $this->set_tlv('CLI_LESSER_VER',   0, 2);
		$this->body .= $this->set_tlv('CLI_BUILD_NUMBER', 2321, 2);
		$this->body .= $this->set_tlv('DISTRIB_NUMBER',   1085, 4);
		$this->body .= $this->set_tlv('CLIENT_LNG',       'en');
		$this->body .= $this->set_tlv('CLIENT_COUNTRY',   'us');
		
		$this->channel = 1;
		$pack = $this->prepare();
		socket_write($this->socket, $pack, strlen($pack));
		$this->parce_cookie_flap();
		
		$this->body = 0x0000;
		$pack = $this->prepare();
		socket_write($this->socket, $pack, strlen($pack));
		$this->close();
		
		if (isset($this->info['RECONECT_HERE']))
		{
			$url = explode(':', $this->info['RECONECT_HERE']);
			if ( ! $this->open($url))
			{
				$this->error = isset($this->info['DISCONECT_REASON']) ? $this->info['DISCONECT_REASON'] : 'Unable to reconnect';
				return FALSE;
			}
		}
		else
		{
			$this->error = isset($this->info['DISCONECT_REASON']) ? $this->info['DISCONECT_REASON'] : 'UIN blocked, please try again 5 min later.';
			return FALSE;
		}
		
		$this->get_flap();
		$this->body .= $this->set_tlv('COOKIE', $this->info['COOKIE']);
		$pack = $this->prepare();
		if ( ! socket_write($this->socket, $pack, strlen($pack)))
		{
			$this->error = 'Can`t send cookie, server close connection';
			return FALSE;
		}
		$this->get_flap();
		$this->body = $this->set_snac_0102();
		$pack = $this->prepare();
		if ( ! socket_write($this->socket, $pack, strlen($pack)))
		{
			$this->error = 'Can`t send ready signal, server close connection';
			return FALSE;
		}
		return TRUE;
	}

	
	public function write_message($uin, $message)
	{
		$this->body = $this->set_snac_0406($uin, $message);
		$pack = $this->prepare();
		if ( ! socket_write($this->socket, $pack, strlen($pack)))
		{
			$this->error = 'Can`t send message, server close connection';
			return FALSE;
		}
		if ( ! $this->parse_answer_flap())
		{
			// Try to send offline message
			$this->body = $this->set_snac_0406_offline($uin, $message);
			$pack = $this->prepare();
			if ( ! socket_write($this->socket, $pack, strlen($pack)))
			{
				$this->error = 'Can`t send offline message, server close connection';
				return FALSE;
			}
			if ( ! $this->parse_answer_flap()) 
			{
				return FALSE;
			}
			else
			{
				$this->error = 'Client is offline. Message sent to server.';
				return FALSE;
			}
		}
		return TRUE;
	}

	
	public function read_message()
	{
		while ($this->get_flap())
		{
			$message = $this->get_snac_0407($this->body);
			if ($message)
			{
				return $message;
			}
		}
		return FALSE;
	}

	
	public function xorpass($pass)
	{
		$roast = array(0xF3, 0x26, 0x81, 0xC4, 0x39, 0x86, 0xDB, 0x92, 0x71, 0xA3, 0xB9, 0xE6, 0x53, 0x7A, 0x95, 0x7c);
		$roasting_pass = '';
		for ($i = 0; $i < strlen($pass); $i++) 
		{
			$roasting_pass .= chr($roast[$i] ^ ord($pass{$i}));
		}
		return $roasting_pass;
	}

	
	public function open($url = array('login.icq.com', 5190))
	{
		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if ($this->socket < 0 OR $this->socket === FALSE) 
		{
			$this->error = 'socket_create() failed: reason: '.socket_strerror($this->socket);
			return FALSE;
		}
		$result = socket_connect($this->socket, gethostbyname($url[0]), $url[1]);
		if ($result < 0 OR $result === FALSE) 
		{
			$this->error = "socket_connect() failed.\nReason: ($result) ".socket_strerror(socket_last_error($socket));
			return FALSE;
		}
		return TRUE;
	}

	
	public function close()
	{
		return socket_close($this->socket);
	}
}

