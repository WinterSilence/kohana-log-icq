<?php
/**
 * 
 */
class ICQ_SNAC extends ICQ_TLV {
	
	public $request_id = 0;
	public $uin;

	
	public function set_snac_0102()
	{
		$this->request_id++;
		$out = pack('nnnN', 1, 2, 0, $this->request_id);
		$out .= pack('n*', 1, 3, 272, 650);
		$out .= pack('n*', 2, 1, 272, 650);
		$out .= pack('n*', 3, 1, 272, 650);
		$out .= pack('n*', 21, 1, 272, 650);
		$out .= pack('n*', 4, 1, 272, 650);
		$out .= pack('n*', 6, 1, 272, 650);
		$out .= pack('n*', 9, 1, 272, 650);
		$out .= pack('n*', 10, 1, 272, 650);
		
		return $out;
	}

	
	public function set_snac_0406($uin, $message)
	{
		$this->request_id++;
		$cookie = microtime();
		
		$out = pack('nnnNdnca*', 4, 6, 0, $this->request_id, $cookie, 2, strlen($uin), $uin);
		
		$capabilities = pack('H*', '094613494C7F11D18222444553540000'); // UTF-8 support
		// '97B12751243C4334AD22D6ABF73F1492' RTF support
		
		$data = pack('nd', 0, $cookie).$capabilities;
		$data .= pack('nnn', 10, 2, 1);
		$data .= pack('nn', 15, 0);
		$data .= pack('nnvvddnVn', 10001, strlen($message) + 62, 27, 8, 0, 0, 0, 3, $this->request_id);
		$data .= pack('nndnn', 14, $this->request_id, 0, 0, 0); //45
		$data .= pack('ncvnva*', 1, 0, 0, 1, (strlen($message) + 1), $message);
		$data .= pack('H*', '0000000000FFFFFF00');
		$out .= $this->set_tlv('RECONECT_HERE', $data);
		$out .= $this->set_tlv('CLIENT', '');
		
		return $out;
	}

	
	public function set_snac_0406_offline($uin, $message)
	{
		$this->request_id++;
		$cookie = microtime();
		$out = pack('nnnNdnca*', 4, 6, 0, $this->request_id, $cookie, 1, strlen($uin), $uin);
		
		$data = pack('ccnc', 5, 1, 1, 1);
		$data .= pack('ccnnna*', 1, 1, strlen($message)+4, 3, 0, $message);
		$out .= $this->set_tlv('DATA', $data);
		$out .= $this->set_tlv('CLIENT', '');
		$out .= $this->set_tlv('COOKIE', '');
		return $out;
	}

	
	public function get_snac_0407($body)
	{
		if (strlen($body)) 
		{
			$msg = unpack('nfamily/nsubtype/nflags/Nrequestid/N2msgid/nchannel/cnamesize', $body);
			if ($msg['family'] == 4 AND $msg['subtype'] == 7) 
			{
				$body = substr($body, 21);
				$from = substr($body, 0, $msg['namesize']);
				$channel = $msg['channel'];
				$body = substr($body, $msg['namesize']);
				$msg = unpack('nwarnlevel/nTLVnumber', $body);
				$body = substr($body, 4);
				for ($i = 0; $i <= $msg['TLVnumber']; $i++)
				{
					$part = $this->get_tlv($body);
					$body = substr($body, 4 + $this->size);
					if ($channel == 1 AND $this->type == 2) 
					{
						while (strlen($part)) 
						{
							$frg = $this->get_tlv_fragment($part);
							if ($frg['id'] == 1 AND $frg['version'] == 1)
							{
								return array('from' => $from, 'message' => substr($frg['data'], 4));
							}
							$part = substr($part, 4 + $frg['size']);
						}
						return FALSE;
					}
				}
			}
		}
		return FALSE;
	}

	
	public function dump($str)
	{
		$f = fopen('dump', 'a');
		fwrite($f, $str);
		fclose($f);
	}
}