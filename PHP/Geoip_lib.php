<?php 

   
     
    if (!defined('GEOIP_FILEDATA')) define('GEOIP_FILEDATA', dirname(__FILE__)."/geoip/GeoIPISP.dat");

    global $GEOIP_REGION_NAME;
    include("geoip/geoipisp.inc");
    require_once 'geoip/geoipregionvars.php';
    
    class Geoip_lib{
        
		function ISP_info($ip) {
		
		
			$giisp = geoip_open(GEOIP_FILEDATA, GEOIP_STANDARD);	
			$isp = geoip_org_by_addr($giisp, $ip);
			geoip_close($giisp);
			
			return $isp;

			
			
			
		}
		
    }


    
?>