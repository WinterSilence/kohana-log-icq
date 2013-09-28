<?php
/**
 * 
 */
class ICQ_TLV {

	public $type;
	public $size;
	public $error;

	public $types = array(
		'UIN'              => 1,  // 0x01
		'DATA'             => 2,  // 0x02
		'CLIENT'           => 3,  // 0x03
		'ERROR_URL'        => 4,  // 0x04
		'RECONECT_HERE'    => 5,  // 0x05
		'COOKIE'           => 6,  // 0x06
		'SNAC_VERSION'     => 7,  // 0x07
		'ERROR_SUBCODE'    => 8,  // 0x08
		'DISCONECT_REASON' => 9,  // 0x09
		'RECONECT_HOST'    => 10, // 0x0A
		'URL'              => 11, // 0x0B
		'DEBUG_DATA'       => 12, // 0x0C
		'SERVICE'          => 13, // 0x0D
		'CLIENT_COUNTRY'   => 14, // 0x0E
		'CLIENT_LNG'       => 15, // 0x0F
		'SCRIPT'           => 16, // 0x10
		'USER_EMAIL'       => 17, // 0x11
		'OLD_PASSWORD'     => 18, // 0x12
		'REG_STATUS'       => 19, // 0x13
		'DISTRIB_NUMBER'   => 20, // 0x14
		'PERSONAL_TEXT'    => 21, // 0x15
		'CLIENT_ID'        => 22, // 0x16
		'CLI_MAJOR_VER'    => 23, // 0x17
		'CLI_MINOR_VER'    => 24, // 0x18
		'CLI_LESSER_VER'   => 25, // 0x19
		'CLI_BUILD_NUMBER' => 26, // 0x1A
		// 'PASSWORD'      => 37
	);

	
	function set_tlv($type, $value, $length = FALSE)
	{
		switch ($length) 
		{
			case 1:
				$format = 'c';
				break;
			case 2:
				$format = 'n';
				break;
			case 4:
				$format = 'N';
				break;
			default:
				$format = 'a*';
		}
		if ($length === FALSE) 
		{
			$length = strlen($value);
		}
		return pack('nn'.$format, $this->types[$type], $length, $value);
	}

	
	function get_tlv($data)
	{
		$arr = unpack('n2', substr($data, 0, 4));
		$this->type = $arr[1];
		$this->size = $arr[2];
		return substr($data, 4, $this->size);
	}

	
	function get_tlv_fragment($data)
	{
		$frg = unpack('cid/cversion/nsize', substr($data, 0, 4));
		$frg['data'] = substr($data, 4, $frg['size']);
		return $frg;
	}

}