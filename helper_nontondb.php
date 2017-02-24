<?php
function CalculateDistance($lat1, $lon1, $lat2, $lon2, $unit) {
global $return;
	if( $lat1 == $lat2 && $lon1 == $lon2 )
		return 0;
		
  	$theta = $lon1 - $lon2;
  	if( isset($theta ) )
  	{
  		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
  		if( isset($dist ) )
  		{
  			$dist = acos($dist);
  			if( isset($dist) )
  			{
  				$dist = rad2deg($dist);
  				if( isset($dist) )
  				{
  					$miles = $dist * 60 * 1.1515;
  					if ($unit == "K") {
  	    				return ($miles * 1.609344);
  					} else if ($unit == "M") {
  	    				return ($miles * 1609.344);
  					} else if ($unit == "N") {
  	   					return ($miles * 0.8684);
  					} else {
  	    				return $miles;
  					}
  				}
  			}
  		}
  	}
  	
  	return 0;
}

function IsNullOrEmptyString($question)
{
    return (!isset($question) || trim($question)==='');
}

/** 
 * recursively create a long directory path
 */
function CreateDirectory($directoryName)
{
	global $return;
	if(!is_dir($directoryName))
	{
   	 	//Directory does not exist, so lets create it.
    	mkdir($directoryName, 0777, true);
    	$return["ret"]["msg"] = "creating";
    }
    else
    {
    	$return["ret"]["msg"] = "exists";
    }
}

function SplitString($theDelimiter, $theString)
{
	return explode($theDelimiter, $theString);
}

function GetVideoTypes($conn)
{
	global $return;
	$the_video_types = array();
	
	$jsonList = $conn->doQuery("select `t_id`,`t_name`,`t_desc`,`t_is_active` from `n_type`;",NULL,'json');
	$objUserList = json_decode($jsonList);
	foreach($objUserList->data->query_result as $key => $value)
	{
		$the_type_id = (string)$value->t_id;
		$the_type_name = (string)$value->t_name;
		$the_type_description = (string)$value->t_desc;
		$the_type_is_active = (string)$value->t_is_active;
		
		if( strcmp($the_type_name,"") == 0 )
		{
			continue;
		}
		
		if( strcmp($the_type_description,"") == 0 )
		{
			$the_type_description = "n/a";
		}
		
		$the_video_types[] = $the_type_id;
		$the_video_types[] = $the_type_name;
		$the_video_types[] = $the_type_description;
		$the_video_types[] = $the_type_is_active;
	}
	$return["ret"]["dat"]["gen"]["types"] = $the_video_types;
}

function GetVideoGenres($conn)
{
	global $return;
	$the_video_genres = array();
	
	$jsonList = $conn->doQuery("select `g_id`,`g_name`,`g_desc`,`g_is_active` from `n_genre`;",NULL,'json');
	$objUserList = json_decode($jsonList);
	foreach($objUserList->data->query_result as $key => $value)
	{
		$the_genre_id = (string)$value->g_id;
		$the_genre_name = (string)$value->g_name;
		$the_genre_description = (string)$value->g_desc;
		$the_genre_is_active = (string)$value->g_is_active;
		
		if( strcmp($the_genre_name,"") == 0 )
		{
			continue;
		}
		
		if( strcmp($the_genre_description,"") == 0 )
		{
			$the_genre_description = "n/a";
		}
		
		$the_video_genres[] = $the_genre_id;
		$the_video_genres[] = $the_genre_name;
		$the_video_genres[] = $the_genre_description;
		$the_video_genres[] = $the_genre_is_active;
	}
	$return["ret"]["dat"]["gen"]["genres"] = $the_video_genres;
}

function SplitGenre($the_genre, &$theGenre1, &$theGenre2, &$theGenre3, &$theGenre4, &$theGenre5)
{
	$the_genre_array = SplitString('|', $the_genre);
	
	if( count($the_genre_array) >= 1 )
	{
		$theGenre1 = $the_genre_array[0];
	}
	
	if( count($the_genre_array) >= 2 )
	{
		$theGenre2 = $the_genre_array[1];
	}
	
	if( count($the_genre_array) >= 3 )
	{
		$theGenre3 = $the_genre_array[2];
	}
	
	if( count($the_genre_array) >= 4 )
	{
		$theGenre4 = $the_genre_array[3];
	}
	
	if( count($the_genre_array) >= 5 )
	{
		$theGenre5 = $the_genre_array[4];
	}
}

function GetAccessLevelCodes()
{
	global $return;
	$the_access_level_code = array();
						
	$temp_string = "SUPER ADMIN";
	$the_access_level_code[] = $temp_string;
	$the_access_level_code[] = GetAccessLevelNumberCode($temp_string);
	
	$temp_string = "ADMIN";
	$the_access_level_code[] = $temp_string;
	$the_access_level_code[] = GetAccessLevelNumberCode($temp_string);
	
	$temp_string = "ADD VIDEO";
	$the_access_level_code[] = $temp_string;
	$the_access_level_code[] = GetAccessLevelNumberCode($temp_string);
	
	$temp_string = "VIEW ONLY";
	$the_access_level_code[] = $temp_string;
	$the_access_level_code[] = GetAccessLevelNumberCode($temp_string);
	
	$temp_string = "REVIEWS & COMMENTS ONLY";
	$the_access_level_code[] = $temp_string;
	$the_access_level_code[] = GetAccessLevelNumberCode($temp_string);
						
	$return["ret"]["dat"]["gen"]["acclvl"] = $the_access_level_code;
}

function GetAccessLevelNumberCode($string_name)
{
	switch( $string_name )
	{
		case "SUPER ADMIN":
		{
			return "99";
		}
		
		case "ADMIN":
		{
			return "90";
		}
		
		case "ADD VIDEO":
		{
			return "60";
		}
		
		case "VIEW ONLY":
		{
			return "30";
		}
		
		case "REVIEWS & COMMENTS ONLY":
		{
			return "10";
		}
		
		default:
		{
			break;
		}
	}
	
	return "0";
}

function AccessLevelValid($type_action, $access_level)
{
	if( $type_action == "acc_update" )
	{
		return true;
	}
	else if( $access_level == GetAccessLevelNumberCode("SUPER ADMIN") )
	{
		return true;
	}
	else if( $access_level == GetAccessLevelNumberCode("ADMIN") )
	{
		if( $type_action == "a_create" || $type_action == "a_edit" || $type_action == "a_list" )
		{
			return false;
		}
		else
		{
			return true;
		}
	} 
	else if( $access_level == GetAccessLevelNumberCode("ADD VIDEO") )
	{
		if( $type_action == "ac_create" || $type_action == "ac_search" || $type_action == "ac_list" || $type_action == "ac_edit" ||
		 	$type_action == "b_create" || $type_action == "b_list" || $type_action == "b_edit" ||
		 	$type_action == "t_create" || $type_action == "t_list" || $type_action == "t_edit" ||
		 	$type_action == "g_create" || $type_action == "g_list" || $type_action == "g_edit" ||
		 	$type_action == "f_create" || $type_action == "f_search" || $type_action == "f_list" || $type_action == "f_edit" ||
		 	$type_action == "v_create" || $type_action == "v_list" || $type_action == "v_edit" )
		{
			return true;
		}
		else
		{
			return false;
		}
	} 
	else if( $access_level == GetAccessLevelNumberCode("VIEW ONLY") )
	{
		if( $type_action == "ac_list" ||
		 	$type_action == "b_list" || 
		 	$type_action == "t_list" || 
		 	$type_action == "g_list" || 
			$type_action == "f_list" ||
		 	$type_action == "v_list" )
		{
			return true;
		}
		else
		{
			return false;
		}
	} 
	else if( $access_level == GetAccessLevelNumberCode("REVIEWS & COMMENTS ONLY") )
	{
		return false;
	} 
	
	switch( $type_action )
	{
		case "a_create":
		{
			if( $access_level == GetAccessLevelNumberCode("SUPER ADMIN") )
			{
				return true;
			}
		}
		
		case "t_create":
		case "g_create":
		case "f_create":
		case "v_create":
		{
			if( $access_level == GetAccessLevelNumberCode("SUPER ADMIN") || $access_level == GetAccessLevelNumberCode("ADMIN") || $access_level == GetAccessLevelNumberCode("ADD VIDEO") )
			{
				return true;
			}
		}
		
		case "a_login":
		{
			return true;
		}
		
		default:
		{
			break;
		}
	}
	
	return false;
}

?>