<?php
/**
 * SSHinPHP :: SSH and PHP bastardisation of the highest grade
 * 
 * supports only SSHv1, but SSHv2 implementation will be done - at some day
 *
 * @author nexus <nexus@smoula.net>
 * @see http://www.universe-people.com
 *
 * be sure to take some really strong drug before opening @see link or reading
 * this source code
 */
function blob2integer($b) {
	$integer = 0;
	for ($i = 0; $i < strlen($b); $i++) {
		$integer = bcmul($integer,256);
		$integer = bcadd($integer,ord($b[$i]));
	}
	return $integer;
}

function integer2blob($integer) {
	$blob = "";
	while ($integer > 0) {
		$blob = chr(bcmod($integer,256)).$blob;
		$integer = bcdiv($integer,256);
	}
	return $blob;
}

function integer2dword ($int) {
	if (($int < pow(2,32)) && ($int >= 0)) {
		$dword = "";
		while ($int > 0) {
			$dword = chr(bcmod ($int,256)).$dword;
			$int = bcdiv ($int,256);
		}
		return str_pad($dword,4,chr(0),STR_PAD_LEFT);
	} else {
		throw new SSHException('integer must be >= 0 and < 2^32 for dword conversion');
	}
}


function dword2integer ($s) {
	if (strlen($s) == 4) {
		return bcadd(bcmul(bcadd(bcmul(bcadd(bcmul(ord($s[0]),256),ord($s[1])),256),ord($s[2])),256),ord($s[3]));
	} else {
		throw new SSHException('dword must be exactly 4 bytes long for integer conversion');
	}
}


function hex2int ($hex) {
	if (strlen($hex) % 2 == 1) {
		$hex = "0".$hex;
	}

	$int = 0;
	for ($i = 0; $i < strlen($hex); $i += 2) {
		$int = bcmul($int,256);
		$int = bcadd($int,hexdec(substr($hex,$i,2)));
	}
	return $int;
}

function int2hex ($int) {
	$hex = "";
	while ($int > 0) {
		$hex = dechex(bcmod ($int,256)).$hex;
		$int = bcdiv ($int,256);
	}
	return $hex;
}

function ul_and ($a,$b) {
	if (strlen($a) > 4 && strlen($b) > 4) {
		throw new Exception("a and b must be unsigned long interpreted as binary string shorter (or equal) than 4 bytes");
	}
	$a = str_pad($a,4,chr(0),STR_PAD_LEFT);
	$b = str_pad($b,4,chr(0),STR_PAD_LEFT);

	return chr(ord($a[0]) & ord($b[0])).chr(ord($a[1]) & ord($b[1])).chr(ord($a[2]) & ord($b[2])).chr(ord($a[3]) & ord($b[3]));
}

function ul_or ($a,$b) {
	if (strlen($a) > 4 && strlen($b) > 4) {
		throw new Exception("a and b must be unsigned long interpreted as binary string shorter (or equal) than 4 bytes");
	}
	$a = str_pad($a,4,chr(0),STR_PAD_LEFT);
	$b = str_pad($b,4,chr(0),STR_PAD_LEFT);

	return chr(ord($a[0]) | ord($b[0])).chr(ord($a[1]) | ord($b[1])).chr(ord($a[2]) | ord($b[2])).chr(ord($a[3]) | ord($b[3]));
}

function ul_xor ($a,$b) {
	if (strlen($a) > 4 && strlen($b) > 4) {
		throw new Exception("a and b must be unsigned long interpreted as binary string shorter (or equal) than 4 bytes");
	}
	$a = str_pad($a,4,chr(0),STR_PAD_LEFT);
	$b = str_pad($b,4,chr(0),STR_PAD_LEFT);

	return chr(ord($a[0]) ^ ord($b[0])).chr(ord($a[1]) ^ ord($b[1])).chr(ord($a[2]) ^ ord($b[2])).chr(ord($a[3]) ^ ord($b[3]));
}

function ul_shift_l ($a,$amount) {
	if (strlen($a) > 4) {
		throw new Exception("a must be unsigned long interpreted as binary string shorter (or equal) than 4 bytes");
	}
	$a = str_pad($a,4,chr(0),STR_PAD_LEFT);

	if ($amount >= 0 && $amount < 8) {
		$o[0] = ((ord($a[0]) << $amount) & 255) | ((ord($a[1]) >> (8-$amount)) & 255);
		$o[1] = ((ord($a[1]) << $amount) & 255) | ((ord($a[2]) >> (8-$amount)) & 255);
		$o[2] = ((ord($a[2]) << $amount) & 255) | ((ord($a[3]) >> (8-$amount)) & 255);
		$o[3] = ((ord($a[3]) << $amount) & 255);
	} elseif ($amount >= 8 && $amount < 16) {
		$a[0] = $a[1]; $a[1] = $a[2]; $a[2] = $a[3]; $a[3] = chr(0);
		$amount -= 8;
		$o[0] = ((ord($a[0]) << $amount) & 255) | ((ord($a[1]) >> (8-$amount)) & 255);
		$o[1] = ((ord($a[1]) << $amount) & 255) | ((ord($a[2]) >> (8-$amount)) & 255);
		$o[2] = ((ord($a[2]) << $amount) & 255);
		$o[3] = $a[3];
	} elseif ($amount >= 16 && $amount < 24) {
		$a[0] = $a[2]; $a[1] = $a[3]; $a[2] = chr(0); $a[3] = chr(0);
		$amount -= 16;
		$o[0] = ((ord($a[0]) << $amount) & 255) | ((ord($a[1]) >> (8-$amount)) & 255);
		$o[1] = ((ord($a[1]) << $amount) & 255);
		$o[2] = $a[2];
		$o[3] = $a[3];
	} elseif ($amount >= 24 && $amount < 32) {
		$a[0] = $a[3]; $a[1] = chr(0); $a[2] = chr(0); $a[3] = chr(0);
		$amount -= 24;
		$o[0] = ((ord($a[0]) << $amount) & 255);
		$o[1] = $a[1];
		$o[2] = $a[2];
		$o[3] = $a[3];
	} else {
		throw new Exception("amount must be >=0 and < 32");
	}
	return chr($o[0]).chr($o[1]).chr($o[2]).chr($o[3]);
}

function ul_shift_r ($a,$amount) {
	if (strlen($a) > 4) {
		throw new Exception("a must be unsigned long interpreted as binary string shorter (or equal) than 4 bytes");
	}
	$a = str_pad($a,4,chr(0),STR_PAD_LEFT);

	if ($amount >= 0 && $amount < 8) {
		$o[0] = ((ord($a[0]) >> $amount) & 255);
		$o[1] = ((ord($a[1]) >> $amount) & 255) | ((ord($a[0]) << (8-$amount)) & 255);
		$o[2] = ((ord($a[2]) >> $amount) & 255) | ((ord($a[1]) << (8-$amount)) & 255);
		$o[3] = ((ord($a[3]) >> $amount) & 255) | ((ord($a[2]) << (8-$amount)) & 255);
	} elseif ($amount >= 8 && $amount < 16) {
		$a[3] = $a[2]; $a[2] = $a[1]; $a[1] = $a[0]; $a[0] = chr(0);
		$amount -= 8;
		$o[0] = $a[0];
		$o[1] = ((ord($a[1]) >> $amount) & 255);
		$o[2] = ((ord($a[2]) >> $amount) & 255) | ((ord($a[1]) << (8-$amount)) & 255);
		$o[3] = ((ord($a[3]) >> $amount) & 255) | ((ord($a[2]) << (8-$amount)) & 255);
	} elseif ($amount >= 16 && $amount < 24) {
		$a[3] = $a[1]; $a[2] = $a[0]; $a[1] = chr(0); $a[0] = chr(0);
		$amount -= 16;
		$o[0] = $a[0];
		$o[1] = $a[1];
		$o[2] = ((ord($a[2]) >> $amount) & 255);
		$o[3] = ((ord($a[3]) >> $amount) & 255) | ((ord($a[2]) << (8-$amount)) && 255);
	} elseif ($amount >= 24 && $amount < 32) {
		$a[3] = $a[0]; $a[2] = chr(0); $a[1] = chr(0); $a[0] = chr(0);
		$amount -= 24;
		$o[0] = $a[0];
		$o[1] = $a[1];
		$o[2] = $a[2];
		$o[3] = ((ord($a[3]) >> $amount) & 255);
	} else {
		throw new Exception("amount must be >=0 and < 32");
	}
	return chr($o[0]).chr($o[1]).chr($o[2]).chr($o[3]);
}

/**
 * RSA
 */

function modexp ($a, $x, $n) {
	$r = 1;
	while ( bccomp($x, 0 ) > 0) {
		if ( bcmod($x, 2) == 1 ) {
			$r = bcmod(bcmul($r, $a) , $n);
		}
		$a = bcmod(bcmul( $a, $a ) , $n);
		$x = bcdiv($x, 2);
	}
	return($r);
}


function rsacrypt ($a,$x,$n) {
	$a_blob = integer2blob($a);
	$a_blob_len = strlen($a_blob);
	$key_len = strlen(integer2blob($n));

	$padded_data = chr(0).chr(2);
	for ($i = 0; $i < ($key_len - $a_blob_len - 1 - 2) ;$i++) {
		$padded_data .= chr(rand(1,255));
	}
	$padded_data .= chr(0);
	$padded_data .= $a_blob;

	$enc_data = modexp(blob2integer($padded_data),$x,$n);
	return $enc_data;
}

function blob_shift_l ($blob,$amount,$preserve_len = true) {
	if ($amount >= 8 || $amount < 0) {
		throw new Exception('when doing blob_shift_l amount must be 0 < amount <= 8');
	}

	$result = "";
	$len = strlen($blob);
	for ($i = 0; $i < $len; $i++) {
		if ($i == 0 && !$preserve_len) {
			$result .= chr(ord($blob[$i]) >> (8-$amount));
		}
		if ($i > 0) {
			$result[$i-1] = chr(ord($result[$i-1]) | ord($blob[$i] >> (8-$amount)));
		}
		$result .= chr(ord($blob[$i]) << $amount);
	}

	return blob2integer($result);
}

/**
 * crc
 */

define ('SSH_CRC_CACHE_FILE','/tmp/crc.cache');
function crc_table($index) {
	static $crctable = array();

	if (count($crctable) < 256) {
		/**
		 * preload crc_table
		 */
		if (!file_exists(SSH_CRC_CACHE_FILE)) {
			/**
			 * if file doesn't exist, generate it
			 */
			$crc_file = fopen(SSH_CRC_CACHE_FILE,'w+');
			if (!$crc_file) {
				throw new Exception('Couldn\'t create CRC cache file!');
			}

			$crcword = integer2dword(0); // unsigned long

			for ($i = 0; $i < 256; $i++) {
				$newbyte = integer2dword(0); // unsigned long
				$x32term = integer2dword(0); // unsigned long
				$j = 0; // int;

				$crcword = integer2dword(0);
				$newbyte = integer2dword($i);
				for ($j = 0; $j < 8; $j++) {
					$x32term = ul_and(ul_xor($crcword,$newbyte), integer2dword(1));
					$crcword = ul_xor(ul_shift_r($crcword, 1), (ord($x32term[3]) == 1 ? integer2dword(hex2int("EDB88320")) : integer2dword(0)));
					$newbyte = ul_shift_r($newbyte, 1);
				}
				$crctable[$i] = $crcword;
			}
			
			fwrite($crc_file,serialize($crctable));
			fclose($crc_file);
		} else {
			$crc_file = fopen(SSH_CRC_CACHE_FILE,'r');
			$crctable = unserialize(fread($crc_file,filesize(SSH_CRC_CACHE_FILE)));
			fclose($crc_file);
		}
	}
	
	return $crctable[$index];
}

function crc($buf) {
	$crcword = integer2dword(0); // unsigned long
	$p = 0;	// pointer to the buf
	$len = strlen($buf); // len of sa buf
	while ($len--) {
		$newbyte = $buf[$p++];
		$newbyte = ul_xor($newbyte,ul_and($crcword, chr(0xFF)));
		$crcword = ul_xor(ul_shift_r($crcword, 8),crc_table(dword2integer($newbyte)));
	}
	return $crcword;
}


/**
 * 3des implementation 'cos of some wierdness in mcrypt implementation
 */

class DESException extends Exception {
}

class triple_des_ctx {
	public $key;
	public $iv0, $iv1;

	public function __construct($key=null,$iv0=null,$iv1=null) {
		$this->key = $key;
		$this->iv0 = $iv0;
		$this->iv1 = $iv1;
	}
}

class triple_des {
	private $enc_m;
	private $dec_m;

	private $enc_ctxs;
	private $dec_ctxs;

	public function __construct() {
		$this->enc_m = mcrypt_module_open('des','','cbc','');
		$this->dec_m = mcrypt_module_open('des','','cbc','');
	}

	public function __destruct() {
		mcrypt_module_close($this->enc_m);
		mcrypt_module_close($this->dec_m);
	}

	public function set_keys ($key) {
		$this->enc_ctxs[0] = new triple_des_ctx(substr($key,0,8),integer2dword(0),integer2dword(0));
		$this->enc_ctxs[1] = new triple_des_ctx(substr($key,8,8),integer2dword(0),integer2dword(0));
		$this->enc_ctxs[2] = new triple_des_ctx(substr($key,16,8),integer2dword(0),integer2dword(0));

		$this->dec_ctxs[0] = new triple_des_ctx(substr($key,0,8),integer2dword(0),integer2dword(0));
		$this->dec_ctxs[1] = new triple_des_ctx(substr($key,8,8),integer2dword(0),integer2dword(0));
		$this->dec_ctxs[2] = new triple_des_ctx(substr($key,16,8),integer2dword(0),integer2dword(0));
	}

	private function des_encipher (&$data,&$ctx) {
		mcrypt_generic_init($this->enc_m,$ctx->key,str_repeat(chr(0),8));
		$enc = mcrypt_generic($this->enc_m,$data);
		mcrypt_generic_deinit($this->enc_m);
		return $enc;
	}

	private function des_decipher ($data,&$ctx) {
		mcrypt_generic_init($this->dec_m,$ctx->key,str_repeat(chr(0),8));
		$dec = mdecrypt_generic($this->dec_m,$data);
		mcrypt_generic_deinit($this->dec_m);
		return $dec;
	}

	private function des_cbc_encrypt(&$data,&$ctx) {
		if ((strlen($data) & 7) != 0) {
			throw new DESException('block length must be multiple of 8');
		}

		$iv0 = $ctx->iv0;
		$iv1 = $ctx->iv1;

		$result = "";

		for ($i = 0; $i < strlen($data); $i += 8) {
			$iv0 = ul_xor($iv0,substr($data,$i,4));
			$iv1 = ul_xor($iv1,substr($data,$i+4,4));
			$iv = $iv0.$iv1;
			$out = $this->des_encipher($iv,$ctx);
			$iv0 = substr($out,0,4);
			$iv1 = substr($out,4,4);

			$result.= $out;
		}

		$ctx->iv0 = $iv0;
		$ctx->iv1 = $iv1;
		$data = $result;
	}

	private function des_cbc_decrypt (&$data,&$ctx) {
		if ((strlen($data) & 7) != 0) {
			throw new DESException('block length must be multiple of 8');
		}

		$iv0 = $ctx->iv0;
		$iv1 = $ctx->iv1;

		$result = "";

		for ($i = 0; $i < strlen($data); $i += 8) {
			$l = substr($data,$i,4);
			$r = substr($data,$i+4,4);
			$out = $this->des_decipher($l.$r,$ctx);
			$iv0 = ul_xor($iv0,substr($out,0,4));
			$iv1 = ul_xor($iv1,substr($out,4,4));
			$result.= $iv0.$iv1;
			$iv0 = $l;
			$iv1 = $r;
		}
		$ctx->iv0 = $iv0;
		$ctx->iv1 = $iv1;
		$data = $result;
	}

	private function des_3cbc_encrypt(&$data,&$ctxs) {
		$this->des_cbc_encrypt($data,$ctxs[0]);
		$this->des_cbc_decrypt($data,$ctxs[1]);
		$this->des_cbc_encrypt($data,$ctxs[2]);
	}

	private function des_3cbc_decrypt(&$data,&$ctxs) {
		$this->des_cbc_decrypt($data,$ctxs[2]);
		$this->des_cbc_encrypt($data,$ctxs[1]);
		$this->des_cbc_decrypt($data,$ctxs[0]);
	}

	public function encrypt (&$data) {
		$this->des_3cbc_encrypt($data,$this->enc_ctxs);
	}

	public function decrypt (&$data) {
		$this->des_3cbc_decrypt($data,$this->dec_ctxs);
	}
}

class SSHException extends Exception {
}

function mpint_read ($data,&$pos) {
	$bits_binary = substr($data,$pos,2);
	$bits = ord($bits_binary[0])*256 + ord($bits_binary[1]);
	$pos+=2;

	$len = floor(($bits + 7) / 8);
	$result = "0";
	for ($i = 0; $i < $len; $i++) {
		$result = bcmul($result,256);
		$result = bcadd($result,ord(substr($data,$pos,1))); $pos++;
	}

	return $result;
}

function mpint_create($int) {
	$out = integer2blob($int);
	$bits = strlen($out)*8;
	$out = chr(($bits >> 8) & 255 ).chr($bits & 255).$out;
	return $out;
}

function bits_set($b) {
	$bits_set = 0;
	for ($i = 0; $i < strlen($b); $i++) {
		$byte = decbin(ord($b[$i]));
		for ($bit = 0; $bit < strlen($byte); $bit++) {
			if ($byte[$bit] == 1) {
				$bits_set++;
			}
		}
	}
	return $bits_set;
}

function hex_dump ($s) {
	$line_pos = 0;
	for ($i = 0; $i < strlen($s); $i++) {
		$line_pos++;
		echo str_pad(dechex(ord($s[$i])),2,'0',STR_PAD_LEFT)." ";
		if ($line_pos % 8 == 0 && $line_pos % 16 != 0) {
			echo "- ";
		}
		if ($line_pos % 16 == 0) {
			echo "| ".strtr(substr($s,$line_pos-16,16),"\r\n","  ")."\n";
		}

		if (($i == strlen($s) - 1) && ($line_pos % 16 != 0)) {
			echo str_repeat(" ",(($line_pos % 16 <= 8) ? ((16 - ($line_pos % 16))*3+2) : ((16 - ($line_pos % 16))*3) ))."| ".strtr(substr($s,$line_pos-($line_pos%16),16),"\r\n","  ")."\n";
		}
	}
	echo "\n";
}

function hex_dump_openssh ($s) {
	$line_pos = 0;
	for ($i = 0; $i < strlen($s); $i++) {
		$line_pos++;
		echo str_pad(dechex(ord($s[$i])),2,'0',STR_PAD_LEFT).":";

		if ($line_pos % 15 == 0) {
			echo "\n";
		}
	}
	echo "\n";
}

/**
 * support class for creating ssh packet
 *
 */
class SSH_packet_forge {
	private $mac_algo = 'hmac-md5';
	private $cipher = '3des-cbc'; private $cipher_block_size = 8; // bytes 64 bit for 3des-cbc

	private $key = null;

	private $packet = "";

	private $packet_seq_no = 0;

	private $packet_length; 	// uint32
	private $padding_length;	// byte
	private $data = "";			// byte[n1] n1 = packet_length - padding_length - 1
	private $padding = "";		// byte[n2] n2 = padding_length
	private $mac;				// byte[m] m = mac_length

	private $fd;

	private $tdes;

	public function __construct($fd) {
		$this->fd = $fd;
	}

	private function compute_packet_length () {
		$this->packet_length = strlen($this->data); 	// payload length
		$this->packet_length += 4; // CRC

		// compute padding
		$padding_growth = 8-(($this->packet_length) % 8);
		$this->padding_length = $padding_growth;
		$this->padding = str_repeat(chr(0),$this->padding_length);
	}

	public function fill_data ($data) {
		$this->data = $data;
	}


	public function build_packet() {
		$this->compute_packet_length();
		$packet = integer2dword($this->packet_length);

		$data = $this->padding;
		$data.= $this->data;
		$data.= crc($this->padding.$this->data);
		if ($this->key != null) {
			/**
			 * encryption is turned on
			 * encrypt data
			 */
			//			echo "enc. debug: plain: \n"; hex_dump($data);
			$this->tdes->encrypt($data);
			//			echo "enc. debug: encrypt: \n"; hex_dump($data);
		}
		$packet .= $data;

		$this->packet = $packet;

		if (!feof($this->fd)) {
			fputs($this->fd,$this->packet);
		} else {
			throw new SSHException('Not connected!');
		}

		$this->increment_seq_no();
	}

	public function get_packet() {
		return $this->packet;
	}

	private function increment_seq_no() {
		if ($this->packet_seq_no == pow(2,32)) {
			$this->packet_seq_no = 0;
		} else {
			$this->packet_seq_no++;
		}
	}

	public function set_key($key,&$tdes) {
		$this->key = $key;
		$this->tdes = $tdes;
	}
}

class SSH_packet_disolver {
	private $fd;

	private $content;
	private $packet_type;

	private $key = null;

	private $tdes;

	public function set_key($key,&$tdes) {
		$this->key = $key;
		$this->tdes = $tdes;
	}

	public function get_data() {
		return $this->content;
	}

	public function get_packet_type() {
		return ord($this->packet_type);
	}

	public function __construct($fd) {
		$this->fd = $fd;
	}

	private $raw_inbuff = null;

	public function read_packet () {
		/**
		 * handle raw read buffer
		 * make sure we know expected length of packet
		 */
		if ($this->raw_inbuff == null || strlen($this->raw_inbuff) < 4) {
			/**
			 * it's empty
			 */
			$this->raw_inbuff = fread($this->fd,4-strlen($this->raw_inbuff));
			if (strlen($this->raw_inbuff) < 4) {
				$this->content = "";
				return ;
			}
		}
		/**
		 * read into raw buffer whole packet
		 */
		if (dword2integer(substr($this->raw_inbuff,0,4)) > strlen($this->raw_inbuff) - 4 - (8-((dword2integer(substr($this->raw_inbuff,0,4))) % 8))) {
			$bytes_missing = dword2integer(substr($this->raw_inbuff,0,4)) - (strlen($this->raw_inbuff) - 4 - (8-((dword2integer(substr($this->raw_inbuff,0,4))) % 8)));
			$this->raw_inbuff.= fread($this->fd,$bytes_missing);
			if (dword2integer(substr($this->raw_inbuff,0,4)) > strlen($this->raw_inbuff) - 4) {
				/**
				 * packet is still not completed, try it next time
				 */
				$this->content = "";
				return ;
			} elseif (dword2integer(substr($this->raw_inbuff,0,4)) < strlen($this->raw_inbuff) - 4 - (8-((dword2integer(substr($this->raw_inbuff,0,4))) % 8))) {
				throw new SSHException('Something really wierd happend!!!');
			}
		}

		/**
		 * at this point $this->raw_inbuff should contain whole packet
		 */
		/**
		 * how long packet is
		 */
		$pos = 0;
		$data = substr($this->raw_inbuff,$pos,4); $pos += 4;
		$packet_len = dword2integer($data);

		$padding_len = 8-(($packet_len) % 8);
		if ($this->key == null) {
			/**
			 * read padding
			 */
			$padding = substr($this->raw_inbuff,$pos,$padding_len); $pos+=$padding_len;

			/**
			 * now packet type
			 */
			$packet_type = substr($this->raw_inbuff,$pos,1); $pos+=1;

			/**
			 * data
			 */
			$content = substr($this->raw_inbuff,$pos,$packet_len-5); $pos+=$packet_len-5;

			$crc = substr($this->raw_inbuff,$pos,4);$pos+=4;
		} else {
			/**
			 * encrypted data CRYPT
			 */
			$enc_content = substr($this->raw_inbuff,$pos,$packet_len+$padding_len);$pos+=$packet_len+$padding_len;

			/**
			 * decrypt packet
			 */
			$this->tdes->decrypt($enc_content);
			$dec_content = $enc_content;

			$xpos = 0;
			$padding = substr($dec_content,$xpos,$padding_len); $xpos += $padding_len;
			$packet_type = substr($dec_content,$xpos,1); $xpos += 1;
			$content = substr($dec_content,$xpos,$packet_len-5); $xpos += $packet_len-5;
			$crc = substr($dec_content,$xpos,4);

		}

		/**
		 * CRC - TODO: checks
		 */
		if ($crc != crc($padding.$packet_type.$content)) {
			echo "!!! Warning: Bad CRC in packet from server\n";
		}

		$this->packet_type = $packet_type;
		if ($packet_type == SSH_SMSG_EXITSTATUS) {
			throw new Exception('Connection closed by peer.');
		}
		$this->content = $content;

		/**
		 * null raw read buffer
		 */
		$this->raw_inbuff = null;
	}

}

define ('SSH_VERSION','0.1');

define ('SSH_SMSG_PUBLIC_KEY',2);
define ('SSH_SMSG_SUCCESS',14);
define ('SSH_SMSG_FAILURE',15);

define ('SSH_CMSG_SESSION_KEY',3);
define ('SSH_CMSG_USER',4);
define ('SSH_CMSG_AUTH_PASSWORD',9);
define ('SSH_CMSG_REQUEST_PTY',10);
define ('SSH_CMSG_EXEC_SHELL',12);
define ('SSH_CMSG_STDIN_DATA',16);
define ('SSH_CMSG_EOF',19);

define ('SSH_SMSG_STDOUT_DATA',17);
define ('SSH_SMSG_STDERR_DATA',18);
define ('SSH_SMSG_EXITSTATUS',20);

define('SSH_CIPHER_3DES',3);

class SSH_in_PHP {
	/**
	 * host to which we want to connect
	 *
	 * @var string
	 */
	private $host;
	/**
	 * port to which we are connected
	 *
	 * @var integer
	 */
	private $port;

	/**
	 * file descriptor of our ssh connection
	 *
	 * @var integer
	 */
	private $fd;

	private $ssh_version;
	private $ssh_server;

	/**
	 * packet forge class
	 *
	 * @var SSH_packet_forge
	 */
	private $packet_forge;

	/**
	 * packet disolver class
	 *
	 * @var SSH_packet_disolver
	 */	
	private $packet_disolver;

	private $session_id;

	private $session_key;

	private $login;
	private $passwd;

	private $logged = false;

	private $inbuffer, $outbuffer;

	/**
	 * constructor, setup all important variables
	 *
	 * @param string $host
	 * @param integer $port
	 */

	public function __construct($host,$port=22) {
		$this->host = $host;
		$this->port = $port;
	}

	/**
	 * connect to the specified host and do all important work
	 * that is needed after succesfull connection
	 *
	 */
	public function connect ($login,$passwd) {
		/**
		 * open connection to host
		 */
		$this->fd = fsockopen($this->host,$this->port,$errno,$errstr);
		if (!$this->fd) {
			/**
			 * if connection was unsuccesfull, throw an exception
			 */
			throw new SSHException($errstr,$errno);
		}

		/**
		 * remember login and password
		 */
		$this->login = $login;
		$this->passwd = $passwd;

		/**
		 * create our packet forge
		 */
		$this->packet_forge = new SSH_packet_forge($this->fd);
		$this->packet_disolver = new SSH_packet_disolver($this->fd);

		/**
		 * get info about ssh on the other side
		 */
		$this->connect_get_peer_info();
		$this->connect_send_our_info();
		//		$this->negotiate_algo();
		//		$this->kex();
		$this->ex_pub_key();
		try {
			$this->login();
		} catch (Exception $e) {
			$this->disconnect();
			throw new SSHException('Unable to authenticate - disconnecting!');
		}
		try {
			$this->req_pty_and_shell();
		} catch (Exception $e) {
			$this->disconnect();
			throw new SSHException($e->getMessage());
		}
		/**
		 * set nonblocking mode for unlimited reading
		 */
		$this->set_non_blocking();
	}

	private function set_non_blocking() {
		socket_set_blocking($this->fd,false);
	}

	private function read_update_inbuff() {
		$this->packet_disolver->read_packet();
		if (($this->packet_disolver->get_packet_type() == SSH_SMSG_STDOUT_DATA) ||
		($this->packet_disolver->get_packet_type() == SSH_SMSG_STDOUT_DATA)) {
			$newdata = $this->packet_disolver->get_data();
			if (strlen($newdata) > 0) {
				$str_len = dword2integer(substr($newdata,0,4));
				$this->inbuffer .= substr($newdata,4,$str_len);
			}
		} else {
			//			throw new SSHException('Unknown data packet type: '.$this->packet_disolver->get_packet_type());
		}
	}

	public function read($max_len = null) {
		if (!feof($this->fd)) {
			if ($this->logged) {
				$this->read_update_inbuff();
				if ($max_len != null && $max_len < strlen($this->inbuffer)) {
					$ret = substr($this->inbuffer,0,$max_len);
					$this->inbuffer = substr($this->inbuffer,$max_len,strlen($this->inbuffer)-$max_len);
				} else {
					$ret = $this->inbuffer;
					$this->inbuffer = "";
				}
				return $ret;

			} else {
				throw new SSHException('You need to login first before reading and writing data');
			}
		} else {
			throw new SSHException('Disconnected!');
		}
	}

	public function write($data) {
		if (!feof($this->fd)) {
			if ($this->logged) {
				$content = chr(SSH_CMSG_STDIN_DATA);
				$content.=integer2dword(strlen($data));
				$content.=$data;
				$this->packet_forge->fill_data($content);
				$this->packet_forge->build_packet();
			} else {
				throw new SSHException('You need to login first before reading and writing data');
			}
		} else {
			throw new SSHException('Disconnected!');
		}
	}

	private function req_pty_and_shell() {
		/**
		 * request pty vt100 80x24, that supports really nothing ;))
		 */
		$content = chr(SSH_CMSG_REQUEST_PTY);
		$term_type = "vt100";
		$content.= integer2dword(strlen($term_type));
		$content.= $term_type;
		$content.= integer2dword(24); // height
		$content.= integer2dword(80); // width
		$content.= integer2dword(0); // graph. height
		$content.= integer2dword(0); // graph. width
		$content.= chr(0); // terminal modes, we support nothing, we pass end of args immediatly

		$this->packet_forge->fill_data($content);
		$this->packet_forge->build_packet();

		$this->packet_disolver->read_packet();
		if ($this->packet_disolver->get_packet_type() != SSH_SMSG_SUCCESS) {
			throw new Exception('Unable to allocate pty - disconnecting!');
		}

		/**
		 * request interactive shell
		 */
		$content = chr(SSH_CMSG_EXEC_SHELL);
		$this->packet_forge->fill_data($content);
		$this->packet_forge->build_packet();
		if ($this->packet_disolver->get_packet_type() != SSH_SMSG_SUCCESS) {
			throw new Exception('Unable to start interactive shell - disconnecting!');
		}
	}

	private function login() {
		$content = chr(SSH_CMSG_USER);
		$content.= integer2dword(strlen($this->login));
		$content.= $this->login;
		$this->packet_forge->fill_data($content);
		$this->packet_forge->build_packet();

		$this->packet_disolver->read_packet();
		if ($this->packet_disolver->get_packet_type() == SSH_SMSG_SUCCESS) {
			echo "login OK, no pass needed"; flush();
		} elseif ($this->packet_disolver->get_packet_type() == SSH_SMSG_FAILURE) {
			/**
			 * maybe user is ok, but needs passwd
			 * try to send passwd
			 */
			$content = chr(SSH_CMSG_AUTH_PASSWORD);
			$content.= integer2dword(strlen($this->passwd));
			$content.= $this->passwd;
			$this->packet_forge->fill_data($content);
			$this->packet_forge->build_packet();

			/**
			 * read reply
			 */
			$this->packet_disolver->read_packet();
			if ($this->packet_disolver->get_packet_type() == SSH_SMSG_SUCCESS) {
				echo "login OK\n";
				$this->logged = true;
				$this->inbuffer = $this->outbuffer = "";
			} else {
				throw new Exception('Password auth rejected!');
			}
		} else {
			throw new Exception("Bad response, expecting SSH_MSG_SUCCESS or SSH_SMSG_FAILURE");
		}
	}

	/**
	 * recv server and host public keys
	 * exchange session key
	 * begin encryption
	 */
	private function ex_pub_key() {
		$this->packet_disolver->read_packet();
		if ($this->packet_disolver->get_packet_type() != SSH_SMSG_PUBLIC_KEY) {
			throw new SSHException('Expected SSH_SMSG_PUBLIC_KEY!');
		}
		/**
		 * 8 bytes      anti_spoofing_cookie  
		 * 32-bit int   server_key_bits  
		 * mp-int       server_key_public_exponent  
		 * mp-int       server_key_public_modulus  
		 * 32-bit int   host_key_bits  
		 * mp-int       host_key_public_exponent  
		 * mp-int       host_key_public_modulus  
		 * 32-bit int   protocol_flags  
		 * 32-bit int   supported_ciphers_mask  
		 * 32-bit int   supported_authentications_mask  
		*/
		$content = $this->packet_disolver->get_data();

		$pos = 0;
		$cookie = substr($content,$pos,8); $pos += 8;

		$server_key_bits = dword2integer(substr($content,$pos,4)); $pos += 4;
		$server_key_public_exponent = mpint_read($content,$pos);
		$server_key_public_modulus = mpint_read($content,$pos);

		$host_key_bits = dword2integer(substr($content,$pos,4)); $pos += 4;
		$host_key_public_exponent = mpint_read($content,$pos);
		$host_key_public_modulus = mpint_read($content,$pos);

		$protocol_flags = dword2integer(substr($content,$pos,4)); $pos += 4;
		$supported_ciphers_mask = dword2integer(substr($content,$pos,4)); $pos += 4;
		$supported_authentications_mask = dword2integer(substr($content,$pos,4)); $pos += 4;

		/**
		 * compute session_id
		 */
		$session_id = md5(integer2blob($host_key_public_modulus).integer2blob($server_key_public_modulus).$cookie,true);
		$this->session_id = $session_id;

		/**
		 * generate session key
		 * The key is a 256 bit
	 	 * random number, interpreted as a 32-byte key, with the least
	 	 * significant 8 bits being the first byte of the key
		 */
		$rnd = "";
		for ($i = 0; $i < 32; $i++) {
			$rnd .= chr(rand(0,255));
		}
		$this->session_key = $rnd;
		/*
		* According to the protocol spec, the first byte of the session key
		* is the highest byte of the integer.  The session key is xored with
		* the first 16 bytes of the session id.
		*/
		$key = $this->session_key;
		for ($i = 0; $i < 16; $i++) {
			$key[$i] = $key[$i] ^ $this->session_id[$i];
		}

		/*
		* Encrypt the integer using the public key and host key of the
		* server (key with smaller modulus first).
		*/
		if (bccomp($server_key_public_modulus,$host_key_public_modulus) < 0) {
			/**
			 * host key has larger modulus
			 */
			$crypted = rsacrypt(blob2integer($key),$server_key_public_exponent,$server_key_public_modulus);
			$crypted = rsacrypt($crypted,$host_key_public_exponent,$host_key_public_modulus);
		} else {
			/**
			 * host key has smaller modulus
			 */
			$crypted = rsacrypt(blob2integer($key),$host_key_public_exponent,$host_key_public_modulus);
			$crypted = rsacrypt($crypted,$server_key_public_exponent,$server_key_public_modulus);
		}

		/**
		 * reply with session key
		 *
		 * 1 byte       cipher_type  
		 * 8 bytes      anti_spoofing_cookie  
		 * mp-int       double encrypted session key  
		 * 32-bit int   protocol_flags 
		 */
		$data = chr(SSH_CMSG_SESSION_KEY);
		$data.= chr(SSH_CIPHER_3DES);
		$data.= $cookie;
		/**
		 * double encrypted session_key
		 */
		$data .= mpint_create($crypted);

		$data .= integer2dword(0);		// protocol flags - we do not support any additional features ;))

		$this->packet_forge->fill_data($data);
		$this->packet_forge->build_packet();

		/**
		  * CRYPTOOOO
		  */
		$tri = new triple_des();
		$tri->set_keys($this->session_key);

		$this->packet_disolver->set_key($this->session_key,$tri);
		$this->packet_disolver->read_packet();

		$this->packet_forge->set_key($this->session_key,$tri);

		if ($this->packet_disolver->get_packet_type() == SSH_SMSG_SUCCESS) {
			echo "crypto engine started... let's rock!!!\n"; flush();
		} else {
			throw new Exception("fatal: something bad with crypto engine happend! Dying peacefully... peace, love, flowers...");
		}
	}

	private function connect_send_our_info() {
		/**
		 * if connection is estabilished
		 */
		if (!feof($this->fd)) {
			fputs($this->fd,"SSH-1.5-phpSSH_".SSH_VERSION."\r\n",255);
		} else {
			throw new SSHException('Not connected!');
		}
	}
	/**
	 * gets peer information like ssh version, protocol version etc
	 *
	 */
	private function connect_get_peer_info() {
		/**
		 * if connection is estabilished
		 */
		if (!feof($this->fd)) {
			/**
			 * read initial line
			 */
			$init_line = fgets($this->fd,255);
			/**
			 * and parse it
			 */
			if (!ereg("^SSH\-([0-9\.]+)\-([[:print:]]+)",$init_line,$parts)) {
				throw new SSHException('Not an SSH server on the other side.');
			}
			/**
			 * fill in server info variables
			 */
			$this->ssh_version = $parts[1];
			$this->ssh_server = $parts[2];
			/**
			 * and check whether we support this server
			 */
			if (substr($this->ssh_version,0,1) != "1") {
				throw new SSHException("SSH version {$this->ssh_version} is not supported!");
			}
		} else {
			throw new SSHException('Not connected!');
		}
	}

	/**
	 * close connection
	 *
	 */
	public function disconnect() {
		if ($this->fd) {
			if ($this->logged) {
				$this->packet_forge->fill_data(chr(SSH_CMSG_EOF));
				$this->packet_forge->build_packet();
			} else {
				throw new SSHException('You need to login first before reading and writing data');
			}
			fclose($this->fd);
		} else {
			throw new SSHException('You can\'t close unopened connection');
		}
	}

	public function get_server_ssh_version () {
		return $this->ssh_version;
	}

	public function get_server_soft() {
		return $this->ssh_server;
	}
}

?>
