<?php
$action=$_POST['action'];
$data=$_POST['data'];

switch( (string)$action )
{
	case 'decrypt':
	{
		echo decrypt($data);
		break;
	}
	
	default: 
	{
		echo encrypt($data);
		break;
	}
}

function encrypt($str, $isBinary = false)
{
		$iv = 'D3D1KAD1TY0G4L1H'; 
		$key = '4D1TYOD3D1KG4L1H'; 
        $iv = $iv;
		$str=base64_encode($str);
        $str = $isBinary ? $str : utf8_decode($str);
        $td = mcrypt_module_open('rijndael-128', ' ', 'cbc', $iv);
        mcrypt_generic_init($td, $key, $iv);
        $encrypted = mcrypt_generic($td, $str);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $isBinary ? $encrypted : bin2hex($encrypted);
}

function decrypt($code, $isBinary = false)
    {
		$iv = 'D3D1KAD1TY0G4L1H'; 
		$key = '4D1TYOD3D1KG4L1H';
		//$code=base64_decode($code);
        $code = $isBinary ? $code : hexTobin($code);
        $iv = $iv;
        $td = mcrypt_module_open('rijndael-128', ' ', 'cbc', $iv);
        mcrypt_generic_init($td, $key, $iv);
        $decrypted = mdecrypt_generic($td, $code);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $isBinary ? base64_decode(trim($decrypted)) : base64_decode(utf8_encode(trim($decrypted)));
    }
	
function hexTobin($hexdata)
{
        $bindata = '';
        for ($i = 0; $i < strlen($hexdata); $i += 2) {
            $bindata .= chr(hexdec(substr($hexdata, $i, 2)));
        }
        return $bindata;
}  
?>