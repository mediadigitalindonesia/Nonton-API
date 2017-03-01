<?php
header('Content-type: application/json');
DEFINE("DBHOST","172.31.0.154");
DEFINE("DBNAME","nl_ver4");
DEFINE("DBUSER","dev");
DEFINE("DBPASS","MnbvcxZ321");
DEFINE("DBAPIKEY","201701091029384756");

date_default_timezone_set("Asia/Jakarta");

include_once("database.php");
include_once("function_nontondb.php"); 
include_once("helper_nontondb.php");
include_once("client_nontondb.php");
include_once("clientextra_nontondb.php");
include_once("geoip/geoip.inc");
include_once("geoip/geoipcity.inc");
include_once("geoip/geoipregionvars.php");

$jsondata = (string)$_POST['data'];
if ($jsondata === null && json_last_error() !== JSON_ERROR_NONE) 
{
	die ("INVALID JSON FORMAT - NO DATA PROVIDED");
}
$data = json_decode($jsondata);

$jsonapikey = (string)$_POST['api_key'];
if ($jsonapikey === null && json_last_error() !== JSON_ERROR_NONE) 
{
	die ("INVALID JSON FORMAT - NO KEY PROVIDED");
}

$jsonaction = (string)$_POST['action'];
if ($jsonaction === null && json_last_error() !== JSON_ERROR_NONE) 
{
	die ("INVALID JSON FORMAT - NO ACTION PROVIDED");
}           		

$return = array();
$the_limit_per_page = 50;

if(strcmp($jsonapikey,DBAPIKEY)==0)
{
	switch( (string)$jsonaction )
	{
		default:
		{
			GetClientAPI($jsonaction, $data);
			break;
		}	
	}
}
else
{
	die ("WRONG KEY PROVIDED");
}

exit(0);

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

function array_key_exists_r($keys, $search_r) {
	$keys_r = split('\|',$keys);
	foreach($keys_r as $key)
	if(!array_key_exists($key,$search_r))
		return false;
	return true;
}

/*
class DataEncryptor
{
    const MY_MCRYPT_CIPHER        = MCRYPT_RIJNDAEL_256;
    const MY_MCRYPT_MODE          = MCRYPT_MODE_CBC;
    const MY_MCRYPT_KEY_STRING    = "1234567890-PheroesHUzyxwvutsMITI"; // This should be a random string, recommended 32 bytes

    public  $lastIv               = '';


    public function __construct()
    {
        // do nothing
    }


    /**
     * Accepts a plaintext string and returns the encrypted version
     */
    /*public function mcryptEncryptString( $stringToEncrypt, $base64encoded = true )
    {
        // Set the initialization vector
            $iv_size      = mcrypt_get_iv_size( self::MY_MCRYPT_CIPHER, self::MY_MCRYPT_MODE );
            $iv           = mcrypt_create_iv( $iv_size, MCRYPT_RAND );
            $this->lastIv = $iv;

        // Encrypt the data
            $encryptedData = mcrypt_encrypt( self::MY_MCRYPT_CIPHER, self::MY_MCRYPT_KEY_STRING, $stringToEncrypt , self::MY_MCRYPT_MODE , $iv );

        // Data may need to be passed through a non-binary safe medium so base64_encode it if necessary. (makes data about 33% larger)
            if ( $base64encoded ) {
                $encryptedData = base64_encode( $encryptedData );
                $this->lastIv  = base64_encode( $iv );
            } else {
                $this->lastIv = $iv;
            }

        // Return the encrypted data
            return $encryptedData;
    }


    /**
     * Accepts a plaintext string and returns the encrypted version
     */
   /* public function mcryptDecryptString( $stringToDecrypt, $iv, $base64encoded = true )
    {
        // Note: the decryption IV must be the same as the encryption IV so the encryption IV must be stored during encryption

        // The data may have been base64_encoded so decode it if necessary (must come before the decrypt)
            if ( $base64encoded ) {
                $stringToDecrypt = base64_decode( $stringToDecrypt );
                $iv              = base64_decode( $iv );
            }

        // Decrypt the data
            $decryptedData = mcrypt_decrypt( self::MY_MCRYPT_CIPHER, self::MY_MCRYPT_KEY_STRING, $stringToDecrypt, self::MY_MCRYPT_MODE, $iv );

        // Return the decrypted data
            return rtrim( $decryptedData ); // the rtrim is needed to remove padding added during encryption
    }


}*/

