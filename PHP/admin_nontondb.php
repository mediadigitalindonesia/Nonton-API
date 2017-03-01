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
		case "a_login":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("uname|pword", $captureddata))
			{
				$the_username = (string)$captureddata->uname;
				$the_password = hash_hmac('sha512', 'salt' . (string)$captureddata->pword, "nontondotcom");
				
				$json = $conn->doQuery("select `a_id`, `a_name`, `a_access_level`, `a_is_active`, `a_last_login` from `n_admin` where `a_username`=? and `a_password`=? LIMIT 1;",array($the_username, $the_password),'json');
				//$json = $conn->doQuery("select `a_id` from `n_admin` LIMIT 1;",null,'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_admin_id = (int)$objUser->data->query_result[0]->a_id;
					$the_admin_name = (string)$objUser->data->query_result[0]->a_name;
					$the_admin_access_level = (int)$objUser->data->query_result[0]->a_access_level;
					$the_admin_is_active = (int)$objUser->data->query_result[0]->a_is_active;
					$the_admin_last_login = (string)$objUser->data->query_result[0]->a_last_login;
					
					if( $the_admin_is_active == 1 )
					{
						$return["ret"]["dat"]["adm"]["id"] = $the_admin_id;
						$return["ret"]["dat"]["adm"]["name"] = $the_admin_name;
						$return["ret"]["dat"]["adm"]["username"] = $the_username;
						$return["ret"]["dat"]["adm"]["acclvl"] = $the_admin_access_level;
						$return["ret"]["dat"]["adm"]["lstlgn"] = $the_admin_last_login;
						
						GetAccessLevelCodes();
						GetVideoTypes($conn);
						GetVideoGenres($conn);

						$return["ret"]["dat"]["popup"]["title"] = "LOGGED IN";
						$return["ret"]["dat"]["popup"]["body"] = "You are now logged in!";
					}
					else
					{
						// not active, tell superadmin
						$return["ret"]["dat"]["popup"]["title"] = "INACTIVE";
						$return["ret"]["dat"]["popup"]["body"] = "User is not active. Ask super admin to fix this issue.";
					}
					
					//error_log("--- ".(string)$conn->getDateTimeNow(), 3, "/home/admin/html/new/php_errors.log");
            		// update the last login date either way
					$json = $conn->doQuery("update `n_admin` set `a_last_login`=? where `a_username`=? and `a_password`=?;",array((string)$conn->getDateTimeNow(),$the_username,$the_password),'json');
					$objUser = json_decode($json);
					
					if(strcmp($objUser->data->result,"ok")==0)
					{
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "ADMIN LOGIN - SUCCESSFUL";
					}
					else
					{
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "ADMIN LOGIN - SUCCESSFUL BUT FAILED TO UPDATE LAST LOGIN";
					}
				}
				else
				{
					// wrong username or password
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Wrong username and password combination";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "ADMIN LOGIN - WRONG USERNAME OR PASSWORD";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
		
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}
		
		case "a_create":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|uname|pword|name|email|acclv", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_username = (string)$captureddata->uname;
				$the_password = hash_hmac('sha512', 'salt' . (string)$captureddata->pword, "nontondotcom");
				$the_name = (string)$captureddata->name;
				$the_email = (string)$captureddata->email;
				$the_access_level = (int)$captureddata->acclv;
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						// check if username or email already exists
						$json = $conn->doQuery("select `a_id` from `n_admin` where `a_username`=? or `a_email`=? LIMIT 1;",array($the_username, $the_email),'json');
						$objUser = json_decode($json);
					
						// if exists, creation failed
						if(!empty($objUser->data->query_result))
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "Username or E-mail already exist in the database!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "ADMIN CREATION - USERNAME OR EMAIL ALREADY EXIST";
						}
						else
						{
							$json = $conn->doQuery("insert into `n_admin` (`a_username`,`a_password`,`a_name`,`a_email`,`a_access_level`,`a_created_by_id`,`a_created_by_name`,`a_time_created`,`a_last_login`) values (?,?,?,?,?,?,?,?,?);",array($the_username,$the_password,$the_name,$the_email,$the_access_level,$the_creator_id,$the_creator_name,$conn->getDateTimeNow(),$conn->getDateTimeNow()),'json');
							$objUser = json_decode($json);
		
							// if creation succeeded
							if(strcmp($objUser->data->result,"ok")==0)
							{
								$return["ret"]["dat"]["popup"]["title"] = "CREATED";
								$return["ret"]["dat"]["popup"]["body"] = "New admin created!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "ADMIN CREATION - SUCCESSFUL";
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "ERROR";
								$return["ret"]["dat"]["popup"]["body"] = "Admin creation failed. Please try again!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "ADMIN CREATION - FAILED TO INSERT NEW ENTRY TO DATABASE";
							}
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "ADMIN CREATION - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "ADMIN CREATION - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
		case "a_list":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|page", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_page_number = (int)$captureddata->page * $the_limit_per_page;
				$the_keyword = "n/a";
				if(array_key_exists_r("kword", $captureddata))
				{
					$the_keyword = (string)$captureddata->kword;
				}
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("select count(*) as `count` from `n_admin` where `a_id`>=?;",array(1),'json');
						$objUser = json_decode($json);
		
						$temp_total_counts = $objUser->data->query_result[0]->count;
						
						$json = $conn->doQuery("select `a_id`,`a_username`,`a_name`,`a_email`,`a_access_level`,`a_created_by_id`,`a_created_by_name`,`a_time_created`,`a_is_active`,`a_last_login` from `n_admin` where `a_id`>=? ORDER BY `a_name` ASC LIMIT ? OFFSET ?;",array(1, $the_limit_per_page,$the_page_number),'json');
						$objUser = json_decode($json);
						
						if( $the_keyword != "n/a" )
						{
							$json = $conn->doQuery("select count(*) as `count` from `n_admin` where `a_username` LIKE ?;",array("%$the_keyword%"),'json');
							$objUser = json_decode($json);
		
							$temp_total_counts = $objUser->data->query_result[0]->count;
						
							$json = $conn->doQuery("select `a_id`,`a_username`,`a_name`,`a_email`,`a_access_level`,`a_created_by_id`,`a_created_by_name`,`a_time_created`,`a_is_active`,`a_last_login` from `n_admin` where `a_username` LIKE ? ORDER BY `a_name` ASC LIMIT ? OFFSET ?;",array("%$the_keyword%", $the_limit_per_page,$the_page_number),'json');
							$objUser = json_decode($json);
						}
						
						// if exists
						if(!empty($objUser->data->query_result))
						{
							$the_search_result = array();
							foreach($objUser->data->query_result as $key => $value)
							{
								$the_search_result[] = (string)$value->a_id;
								$the_search_result[] = (string)$value->a_username;
								$the_search_result[] = (string)$value->a_name;
								$the_search_result[] = (string)$value->a_email;
								$the_search_result[] = (string)$value->a_access_level;
								$the_search_result[] = (string)$value->a_created_by_id;
								$the_search_result[] = (string)$value->a_created_by_name;
								$the_search_result[] = (string)$value->a_time_created;
								$the_search_result[] = (string)$value->a_is_active;
								$the_search_result[] = (string)$value->a_last_login;
							}
							$return["ret"]["dat"]["list"]["admin"] = $the_search_result;
							
							$return["ret"]["dat"]["list"]["total"] = $temp_total_counts;
							
							$return["ret"]["dat"]["list"]["itmlmt"] = $the_limit_per_page;
							
							$return["ret"]["dat"]["popup"]["title"] = "FOUND";
							$return["ret"]["dat"]["popup"]["body"] = "Results found!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "ADMIN LIST - SUCCESSFUL";
						}
						else
						{
							if( $the_keyword != "n/a" )
							{
								$return["ret"]["dat"]["popup"]["title"] = "NOT FOUND";
								$return["ret"]["dat"]["popup"]["body"] = "No results found. Try a different search keyword!";
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "NOT FOUND";
								$return["ret"]["dat"]["popup"]["body"] = "No entry is found. Create a new one first!";
							}
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "ADMIN LIST - NO RESULT";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "ADMIN LIST - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "ADMIN LIST - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}
		
		case "a_edit":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|id|uname|name|email|acclv|actv", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_id = (int)$captureddata->id;
				$the_username = (string)$captureddata->uname;
				$the_name = (string)$captureddata->name;
				$the_email = (string)$captureddata->email;
				$the_access_level = (int)$captureddata->acclv;
				$the_is_active = (int)$captureddata->actv;
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						// check if username or email already exists
						$json = $conn->doQuery("select `a_id` from `n_admin` where `a_id`=? LIMIT 1;",array($the_id),'json');
						$objUser = json_decode($json);
					
						// if doesnt exists, somethings not right
						if(empty($objUser->data->query_result))
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "Username or E-mail doesn't exist in the database!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "ADMIN EDIT - USERNAME OR EMAIL DOESN'T EXIST";
						}
						else
						{
							$json = $conn->doQuery("update `n_admin` set `a_username`=?, `a_name`=?, `a_email`=?, `a_access_level`=?, `a_is_active`=? where `a_id`=?;",array($the_username,$the_name,$the_email,$the_access_level,$the_is_active,$the_id),'json');
							$objUser = json_decode($json);
		
							// if update succeeded
							if(strcmp($objUser->data->result,"ok")==0)
							{
								$return["ret"]["dat"]["popup"]["title"] = "UPDATED";
								$return["ret"]["dat"]["popup"]["body"] = "Admin info updated!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "ADMIN EDIT - SUCCESSFUL";
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "ERROR";
								$return["ret"]["dat"]["popup"]["body"] = "Admin edit failed. Please try again!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "ADMIN EDIT - FAILED TO UPDATE THE DATABASE";
							}
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "ADMIN EDIT - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "ADMIN EDIT - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
		case "acc_update":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|oldpw|uname|newpw", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_old_password = hash_hmac('sha512', 'salt' . (string)$captureddata->oldpw, "nontondotcom");
				$the_username = (string)$captureddata->uname;
				$the_new_password = hash_hmac('sha512', 'salt' . (string)$captureddata->newpw, "nontondotcom");
				
				$json = $conn->doQuery("select `a_name`, `a_access_level`, `a_is_active`, `a_last_login` from `n_admin` where `a_id`=? and `a_password`=? LIMIT 1;",array($the_creator_id, $the_old_password),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_admin_name = (string)$objUser->data->query_result[0]->a_name;
					$the_admin_access_level = (int)$objUser->data->query_result[0]->a_access_level;
					$the_admin_is_active = (int)$objUser->data->query_result[0]->a_is_active;
					$the_admin_last_login = (string)$objUser->data->query_result[0]->a_last_login;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("update `n_admin` set `a_username`=?, `a_password`=? where `a_id`=?;",array($the_username,$the_new_password,$the_creator_id),'json');
						$objUser = json_decode($json);
					
						// if update succeeded
						if(strcmp($objUser->data->result,"ok")==0)
						{
							$return["ret"]["dat"]["popup"]["title"] = "UPDATED";
							$return["ret"]["dat"]["popup"]["body"] = "Account updated!";
							
							$return["ret"]["dat"]["adm"]["id"] = $the_creator_id;
							$return["ret"]["dat"]["adm"]["name"] = $the_admin_name;
							$return["ret"]["dat"]["adm"]["username"] = $the_username;
							$return["ret"]["dat"]["adm"]["acclvl"] = $the_admin_access_level;
							$return["ret"]["dat"]["adm"]["lstlgn"] = $the_admin_last_login;
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "ACCOUNT UPDATE - SUCCESSFUL";
						}
						else
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "Acccount update failed. Please try again!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "ACCOUNT UPDATE - FAILED TO UPDATE THE DATABASE";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "ACCOUNT UPDATE- NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Wrong username or password!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "ACCOUNT UPDATE - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}
		
		case "ac_create":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|name|nnme|dob|gndr|biog", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_name = (string)$captureddata->name;
				$the_nickname = (string)$captureddata->nname;
				$the_birthday = (string)$captureddata->dob;
				$the_gender = (string)$captureddata->gndr;
				$the_biography = (string)$captureddata->biog;
				
				$json = $conn->doQuery("select `a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("insert into `n_actor` (`ac_name`,`ac_nickname`, `ac_description`,`ac_birthday`,`ac_gender`,`ac_created_by_id`,`ac_time_created`) values (?,?,?,?,?,?,?);",array($the_name,$the_nickname,$the_biography,$the_birthday,$the_gender,$the_creator_id,$conn->getDateTimeNow()),'json');
						$objUser = json_decode($json);
		
						// if creation succeeded
						if(strcmp($objUser->data->result,"ok")==0)
						{
							$return["ret"]["dat"]["popup"]["title"] = "CREATED";
							$return["ret"]["dat"]["popup"]["body"] = "New actor created successfully!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "ACTOR CREATION - SUCCESSFUL";
						}
						else
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "Actor creation failed. Please try again!";
						
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "ACTOR CREATION - FAILED TO INSERT NEW ENTRY TO DATABASE";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "ACTOR CREATION - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "ACTOR CREATION - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
		case "ac_search":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|kword", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_keyword = (string)$captureddata->kword;
				
				$json = $conn->doQuery("select `a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("select count(*) as `count` from `n_actor` where `ac_name` LIKE ?;",array("%$the_keyword%"),'json');
						$objUser = json_decode($json);
		
						$the_search_result_count = $objUser->data->query_result[0]->count;
						
						if( $the_search_result_count > 0 )
						{
							$json = $conn->doQuery("select `ac_id`,`ac_name`,`ac_description` from `n_actor` where `ac_name` LIKE ? LIMIT 50;",array("%$the_keyword%"),'json');
							$objUser = json_decode($json);
							
							// has to be found since we checked the total count before this, if not that means we have different argument in the select
							if(!empty($objUser->data->query_result))
							{
								$the_search_result = array();
								foreach($objUser->data->query_result as $key => $value)
								{
									$the_search_result[] = (string)$value->ac_id;
									$the_search_result[] = (string)$value->ac_name;
									
									$temp_search_description = (string)$value->ac_description;
									if( IsNullOrEmptyString($temp_search_description) )
									{
										$temp_search_description = "n/a";
									}
									$the_search_result[] = $temp_search_description;
								}
								$return["ret"]["dat"]["srch"]["actr"] = $the_search_result;
								
								if( $the_search_result_count > 50 )
								{
									$return["ret"]["dat"]["popup"]["title"] = "TOO MANY";
									$return["ret"]["dat"]["popup"]["body"] = "Too many results. Try a more specific keyword!";
								}
								else
								{
									$return["ret"]["dat"]["popup"]["title"] = "RESULT FOUND";
									$return["ret"]["dat"]["popup"]["body"] = "Results found!!";
								}
								
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "ACTOR SEARCH - FOUND";
							}
						}
						else
						{
							$return["ret"]["dat"]["popup"]["title"] = "NO RESULT";
							$return["ret"]["dat"]["popup"]["body"] = "No entry found. Try a different keyword!";
						
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "ACTOR SEARCH - NO RESULT FOUND";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "ACTOR CREATION - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "ACTOR CREATION - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
		case "ac_list":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|page", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_page_number = (int)$captureddata->page * $the_limit_per_page;
				$the_keyword = "n/a";
				if(array_key_exists_r("kword", $captureddata))
				{
					$the_keyword = (string)$captureddata->kword;
				}
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("select count(*) as `count` from `n_actor` where `ac_id`>=?;",array(1),'json');
						$objUser = json_decode($json);
		
						$temp_total_counts = $objUser->data->query_result[0]->count;
						
						$json = $conn->doQuery("select `ac_id`,`ac_name`,`ac_nickname`,`ac_description`,`ac_birthday`,`ac_gender`,`ac_created_by_id`,`ac_time_created`,`ac_is_active`,`ac_last_login` from `n_actor` where `ac_id`>=? ORDER BY `ac_name` ASC LIMIT ? OFFSET ?;",array(1, $the_limit_per_page,$the_page_number),'json');
						$objUser = json_decode($json);
						
						if( $the_keyword != "n/a" )
						{
							$json = $conn->doQuery("select count(*) as `count` from `n_admin` where `a_username` LIKE ?;",array("%$the_keyword%"),'json');
							$objUser = json_decode($json);
		
							$temp_total_counts = $objUser->data->query_result[0]->count;
						
							$json = $conn->doQuery("select `ac_id`,`ac_name`,`ac_nickname`,`ac_description`,`ac_birthday`,`ac_gender`,`ac_created_by_id`,`ac_time_created`,`ac_is_active`,`ac_last_login` from `n_actor` where `ac_name` LIKE ? ORDER BY `ac_name` ASC LIMIT ? OFFSET ?;",array("%$the_keyword%", $the_limit_per_page,$the_page_number),'json');
							$objUser = json_decode($json);
						}
					
						// if exists
						if(!empty($objUser->data->query_result))
						{
							$the_search_result = array();
							foreach($objUser->data->query_result as $key => $value)
							{
								$the_search_result[] = (string)$value->ac_id;
								$temp_search_text = (string)$value->ac_name;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$temp_search_text = (string)$value->ac_nickname;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$temp_search_text = (string)$value->ac_description;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$temp_search_text = (string)$value->ac_birthday;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "0000-00-00 ";
								}
								$the_search_result[] = $temp_search_text;
								
								$temp_search_text = (string)$value->ac_gender;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$the_search_result[] = (string)$value->ac_created_by_id;
								$the_search_result[] = (string)$value->ac_time_created;
								$the_search_result[] = (string)$value->ac_is_active;
								$the_search_result[] = (string)$value->ac_last_login;
							}
							$return["ret"]["dat"]["list"]["actor"] = $the_search_result;
							
							$return["ret"]["dat"]["list"]["total"] = $temp_total_counts;
							
							$return["ret"]["dat"]["list"]["itmlmt"] = $the_limit_per_page;
							
							$return["ret"]["dat"]["popup"]["title"] = "FOUND";
							$return["ret"]["dat"]["popup"]["body"] = "Results found!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "ACTOR LIST - SUCCESSFUL";
						}
						else
						{
							if( $the_keyword != "n/a" )
							{
								$return["ret"]["dat"]["popup"]["title"] = "NOT FOUND";
								$return["ret"]["dat"]["popup"]["body"] = "No results found. Try a different search keyword!";
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "NOT FOUND";
								$return["ret"]["dat"]["popup"]["body"] = "No entry is found. Create a new one first!";
							}
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "ACTOR LIST - NO RESULT";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "ACTOR LIST - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "ACTOR LIST - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}

		case "ac_edit":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|id|name|nnme|dob|gndr|biog|actv", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_id = (int)$captureddata->id;
				$the_name = (string)$captureddata->name;
				$the_nickname = (string)$captureddata->nnme;
				$the_birthday = (string)$captureddata->dob;
				$the_gender = (string)$captureddata->gndr;
				$the_biography = (string)$captureddata->biog;
				$the_is_active = (int)$captureddata->actv;
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						// check if username or email already exists
						$json = $conn->doQuery("select `ac_id` from `n_actor` where `ac_id`=? LIMIT 1;",array($the_id),'json');
						$objUser = json_decode($json);
					
						// if doesnt exists, somethings not right
						if(empty($objUser->data->query_result))
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "Id doesn't exist in the database!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "ACTOR EDIT - ID DOESN'T EXIST";
						}
						else
						{
							$json = $conn->doQuery("update `n_actor` set `ac_name`=?, `ac_nickname`=?, `ac_description`=?, `ac_birthday`=?,`ac_gender`=?, `ac_is_active`=? where `ac_id`=?;",array($the_name,$the_nickname,$the_biography,$the_birthday,$the_gender,$the_is_active,$the_id),'json');
							$objUser = json_decode($json);
		
							// if update succeeded
							if(strcmp($objUser->data->result,"ok")==0)
							{
								$return["ret"]["dat"]["popup"]["title"] = "UPDATED";
								$return["ret"]["dat"]["popup"]["body"] = "Actor info updated!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "ACTOR EDIT - SUCCESSFUL";
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "ERROR";
								$return["ret"]["dat"]["popup"]["body"] = "Actor edit failed. Please try again!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "ACTOR EDIT - FAILED TO UPDATE THE DATABASE";
							}
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "ACTOR EDIT - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "ACTOR EDIT - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
		case "b_create":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|titl|desc|frncsid|frncs|vdoid|vdo|urllg|urlbg|urllnk", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_title = (string)$captureddata->titl;
				$the_description = (string)$captureddata->desc;
				$the_franchise_id = (string)$captureddata->frncsid;
				$the_franchise = (string)$captureddata->frncs;
				$the_video_id = (string)$captureddata->vdoid;
				$the_video = (string)$captureddata->vdo;
				$the_url_logo = (string)$captureddata->urllg;
				$the_url_background = (string)$captureddata->urlbg;
				$the_url_link = (string)$captureddata->urllnk;

				$json = $conn->doQuery("select `a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("insert into `n_banner` (`b_title`,`b_desc`,`b_franchise_id`,`b_franchise`,`b_video_id`,`b_video`,`b_url_logo`,`b_url_background`,`b_url_link`,`b_date_added`,`b_last_updated`,`b_creator_id`) values (?,?,?,?,?,?,?,?,?,?,?,?);",array($the_title,$the_description,$the_franchise_id,$the_franchise,$the_video_id,$the_video,$the_url_logo,$the_url_background,$the_url_link, $conn->getDateTimeNow(),$conn->getDateTimeNow(),$the_creator_id),'json');
						$objUser = json_decode($json);
		
						// if creation succeeded
						if(strcmp($objUser->data->result,"ok")==0)
						{
							$return["ret"]["dat"]["popup"]["title"] = "CREATED";
							$return["ret"]["dat"]["popup"]["body"] = "New banner created successfully!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "BANNER CREATION - SUCCESSFUL";
						}
						else
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "Banner creation failed. Please try again!";
						
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "BANNER CREATION - FAILED TO INSERT NEW ENTRY TO DATABASE";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "BANNER CREATION - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "BANNER CREATION - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
		case "b_list":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|page", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_page_number = (int)$captureddata->page * $the_limit_per_page;
				$the_keyword = "n/a";
				if(array_key_exists_r("kword", $captureddata))
				{
					$the_keyword = (string)$captureddata->kword;
				}
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$temp_total_counts = GetBannersList($conn, false, $the_keyword, $the_limit_per_page, $the_page_number);
						
						if( $temp_total_counts > 0 )
						{
							$return["ret"]["dat"]["list"]["total"] = $temp_total_counts;
							
							$return["ret"]["dat"]["list"]["itmlmt"] = $the_limit_per_page;
							
							$return["ret"]["dat"]["popup"]["title"] = "FOUND";
							$return["ret"]["dat"]["popup"]["body"] = "Results found!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "BANNER LIST - SUCCESSFUL";
						}
						else
						{
							if( $the_keyword != "n/a" )
							{
								$return["ret"]["dat"]["popup"]["title"] = "NOT FOUND";
								$return["ret"]["dat"]["popup"]["body"] = "No results found. Try a different search keyword!";
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "NOT FOUND";
								$return["ret"]["dat"]["popup"]["body"] = "No entry is found. Create a new one first!";
							}
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "BANNER LIST - NO RESULT";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "BANNER LIST - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "BANNER LIST - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}

		case "b_edit":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|id|titl|desc|frncsid|frncs|vdoid|vdo|urllg|urlbg|urllnk|actv", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_id = (int)$captureddata->id;
				$the_title = (string)$captureddata->titl;
				$the_description = (string)$captureddata->desc;
				$the_franchise_id = (string)$captureddata->frncsid;
				$the_franchise = (string)$captureddata->frncs;
				$the_video_id = (string)$captureddata->vdoid;
				$the_video = (string)$captureddata->vdo;
				$the_url_logo = (string)$captureddata->urllg;
				$the_url_background = (string)$captureddata->urlbg;
				$the_url_link = (string)$captureddata->urllnk;
				$the_is_active = (int)$captureddata->actv;
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						// check if username or email already exists
						$json = $conn->doQuery("select `b_id` from `n_banner` where `b_id`=? LIMIT 1;",array($the_id),'json');
						$objUser = json_decode($json);
					
						// if doesnt exists, somethings not right
						if(empty($objUser->data->query_result))
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "Id doesn't exist in the database!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "BANNER EDIT - ID DOESN'T EXIST";
						}
						else
						{		
							$json = $conn->doQuery("update `n_banner` set `b_title`=?, `b_franchise_id`=?, `b_franchise`=?, `b_video_id`=?, `b_video`=?,`b_desc`=?, `b_url_logo`=?, `b_url_background`=?, `b_url_link`=?, `b_is_active`=?, `b_last_updated`=? where `b_id`=?;",array($the_title,$the_franchise_id, $the_franchise, $the_video_id, $the_video, $the_description, $the_url_logo, $the_url_background, $the_url_link,$the_is_active,$conn->getDateTimeNow(),$the_id),'json');
							$objUser = json_decode($json);
		
							// if update succeeded
							if(strcmp($objUser->data->result,"ok")==0)
							{
								$return["ret"]["dat"]["popup"]["title"] = "UPDATED";
								$return["ret"]["dat"]["popup"]["body"] = "Banner info updated!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "BANNER EDIT - SUCCESSFUL";
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "ERROR";
								$return["ret"]["dat"]["popup"]["body"] = "Banner edit failed. Please try again!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "BANNER EDIT - FAILED TO UPDATE THE DATABASE";
							}
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "BANNER EDIT - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "BANNER EDIT - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
		case "co_create":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|titl|desc", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_title = (string)$captureddata->titl;
				$the_description = (string)$captureddata->desc;
				
				$json = $conn->doQuery("select `a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("insert into `n_company` (`co_name`,`co_desc`,`co_creator`,`co_date_created`) values (?,?,?,?);",array($the_title,$the_description,$the_creator_id,$conn->getDateTimeNow()),'json');
						$objUser = json_decode($json);
		
						// if creation succeeded
						if(strcmp($objUser->data->result,"ok")==0)
						{
							$return["ret"]["dat"]["popup"]["title"] = "CREATED";
							$return["ret"]["dat"]["popup"]["body"] = "New company created successfully!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "COMPANY CREATION - SUCCESSFUL";
						}
						else
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "Company creation failed. Please try again!";
						
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "COMPANY CREATION - FAILED TO INSERT NEW ENTRY TO DATABASE";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "COMPANY CREATION - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "COMPANY CREATION - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
		case "co_search":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|kword", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_keyword = (string)$captureddata->kword;
				
				$json = $conn->doQuery("select `a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					//error_log("--- ".$the_creator_access_level, 3, "/home/admin/html/new/comm/php_errors.log");
            		
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("select count(*) as `count` from `n_company` where `co_name` LIKE ?;",array("%$the_keyword%"),'json');
						$objUser = json_decode($json);
		
						$the_search_result_count = $objUser->data->query_result[0]->count;
						
						if( $the_search_result_count > 0 )
						{
							$json = $conn->doQuery("select `co_id`,`co_name`,`co_desc` from `n_company` where `co_name` LIKE ? LIMIT 50;",array("%$the_keyword%"),'json');
							$objUser = json_decode($json);
							
							// has to be found since we checked the total count before this, if not that means we have different argument in the select
							if(!empty($objUser->data->query_result))
							{
								$the_search_result = array();
								foreach($objUser->data->query_result as $key => $value)
								{
									$the_search_result[] = (string)$value->co_id;
									$the_search_result[] = (string)$value->co_name;
									
									$temp_search_description = (string)$value->co_desc;
									if( IsNullOrEmptyString($temp_search_description) )
									{
										$temp_search_description = "n/a";
									}
									$the_search_result[] = $temp_search_description;
								}
								$return["ret"]["dat"]["srch"]["company"] = $the_search_result;
								
								if( $the_search_result_count > 50 )
								{
									$return["ret"]["dat"]["popup"]["title"] = "TOO MANY";
									$return["ret"]["dat"]["popup"]["body"] = "Too many results. Try a more specific keyword!";
								}
								else
								{
									$return["ret"]["dat"]["popup"]["title"] = "RESULT FOUND";
									$return["ret"]["dat"]["popup"]["body"] = "Results found!!";
								}
								
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "COMPANY SEARCH - FOUND";
							}
						}
						else
						{
							$return["ret"]["dat"]["popup"]["title"] = "NO RESULT";
							$return["ret"]["dat"]["popup"]["body"] = "No entry found. Try a different keyword!";
						
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "COMPANY SEARCH - NO RESULT FOUND";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "COMPANY SEARCH - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "COMPANY SEARCH - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}
		
		case "co_list":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|page", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_page_number = (int)$captureddata->page * $the_limit_per_page;
				$the_keyword = "n/a";
				if(array_key_exists_r("kword", $captureddata))
				{
					$the_keyword = (string)$captureddata->kword;
				}
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("select count(*) as `count` from `n_company` where `co_id`>=?;",array(1),'json');
						$objUser = json_decode($json);
		
						$temp_total_counts = $objUser->data->query_result[0]->count;
						
						$json = $conn->doQuery("select `co_id`,`co_name`,`co_desc`,`co_creator`,`co_date_created`,`co_is_active` from `n_company` where `co_id`>=? ORDER BY `co_name` ASC LIMIT ? OFFSET ?;",array(1, $the_limit_per_page,$the_page_number),'json');
						$objUser = json_decode($json);
						
						if( $the_keyword != "n/a" )
						{
							$json = $conn->doQuery("select count(*) as `count` from `n_company` where `co_name` LIKE ?;",array("%$the_keyword%"),'json');
							$objUser = json_decode($json);
		
							$temp_total_counts = $objUser->data->query_result[0]->count;
						
							$json = $conn->doQuery("select `co_id`,`co_name`,`co_desc`,`co_creator`,`co_date_created`,`co_is_active` from `n_company` where `co_name` LIKE ? ORDER BY `co_name` ASC LIMIT ? OFFSET ?;",array("%$the_keyword%", $the_limit_per_page,$the_page_number),'json');
							$objUser = json_decode($json);
						}
					
						// if exists
						if(!empty($objUser->data->query_result))
						{
							$the_search_result = array();
							foreach($objUser->data->query_result as $key => $value)
							{
								$the_search_result[] = (string)$value->co_id;
								$temp_search_text = (string)$value->co_name;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$temp_search_text = (string)$value->co_desc;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$the_search_result[] = (string)$value->co_creator;
								$the_search_result[] = (string)$value->co_date_created;
								$the_search_result[] = (string)$value->co_is_active;
							}
							$return["ret"]["dat"]["list"]["company"] = $the_search_result;
							
							$return["ret"]["dat"]["list"]["total"] = $temp_total_counts;
							
							$return["ret"]["dat"]["list"]["itmlmt"] = $the_limit_per_page;
							
							$return["ret"]["dat"]["popup"]["title"] = "FOUND";
							$return["ret"]["dat"]["popup"]["body"] = "Results found!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "COMPANY LIST - SUCCESSFUL";
						}
						else
						{
							if( $the_keyword != "n/a" )
							{
								$return["ret"]["dat"]["popup"]["title"] = "NOT FOUND";
								$return["ret"]["dat"]["popup"]["body"] = "No results found. Try a different search keyword!";
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "NOT FOUND";
								$return["ret"]["dat"]["popup"]["body"] = "No entry is found. Create a new one first!";
							}
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "COMPANY LIST - NO RESULT";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "COMPANY LIST - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "GENRE LIST - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}

		case "co_edit":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|id|titl|desc|actv", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_id = (int)$captureddata->id;
				$the_title = (string)$captureddata->titl;
				$the_description = (string)$captureddata->desc;
				$the_is_active = (int)$captureddata->actv;
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						// check if username or email already exists
						$json = $conn->doQuery("select `co_id` from `n_company` where `co_id`=? LIMIT 1;",array($the_id),'json');
						$objUser = json_decode($json);
					
						// if doesnt exists, somethings not right
						if(empty($objUser->data->query_result))
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "Id doesn't exist in the database!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "COMPANY EDIT - ID DOESN'T EXIST";
						}
						else
						{
							$json = $conn->doQuery("update `n_company` set `co_name`=?, `co_desc`=?,`co_is_active`=? where `co_id`=?;",array($the_title,$the_description,$the_is_active,$the_id),'json');
							$objUser = json_decode($json);
		
							// if update succeeded
							if(strcmp($objUser->data->result,"ok")==0)
							{
								$return["ret"]["dat"]["popup"]["title"] = "UPDATED";
								$return["ret"]["dat"]["popup"]["body"] = "Company info updated!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "COMPANY EDIT - SUCCESSFUL";
								
								GetVideoGenres($conn);
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "ERROR";
								$return["ret"]["dat"]["popup"]["body"] = "Company edit failed. Please try again!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "COMPANY EDIT - FAILED TO UPDATE THE DATABASE";
							}
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "COMPANY EDIT - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "COMPANY EDIT - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
		case "cy_create":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|titl", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_title = (string)$captureddata->titl;
				$the_description = (string)$captureddata->desc;
				
				$json = $conn->doQuery("select `a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("insert into `n_country` (`cy_name`,`cy_creator`,`cy_date_created`) values (?,?,?);",array($the_title,$the_creator_id,$conn->getDateTimeNow()),'json');
						$objUser = json_decode($json);
		
						// if creation succeeded
						if(strcmp($objUser->data->result,"ok")==0)
						{
							$return["ret"]["dat"]["popup"]["title"] = "CREATED";
							$return["ret"]["dat"]["popup"]["body"] = "New country created successfully!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "COUNTRY CREATION - SUCCESSFUL";
						}
						else
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "COUNTRY creation failed. Please try again!";
						
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "COUNTRY CREATION - FAILED TO INSERT NEW ENTRY TO DATABASE";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "COUNTRY CREATION - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "COUNTRY CREATION - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
		case "cy_search":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|kword", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_keyword = (string)$captureddata->kword;
				
				$json = $conn->doQuery("select `a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					//error_log("--- ".$the_creator_access_level, 3, "/home/admin/html/new/comm/php_errors.log");
            		
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("select count(*) as `count` from `n_country` where `cy_name` LIKE ?;",array("%$the_keyword%"),'json');
						$objUser = json_decode($json);
		
						$the_search_result_count = $objUser->data->query_result[0]->count;
						
						if( $the_search_result_count > 0 )
						{
							$json = $conn->doQuery("select `cy_id`,`cy_name` from `n_country` where `cy_name` LIKE ? LIMIT 50;",array("%$the_keyword%"),'json');
							$objUser = json_decode($json);
							
							// has to be found since we checked the total count before this, if not that means we have different argument in the select
							if(!empty($objUser->data->query_result))
							{
								$the_search_result = array();
								foreach($objUser->data->query_result as $key => $value)
								{
									$the_search_result[] = (string)$value->cy_id;
									$the_search_result[] = (string)$value->cy_name;
									$the_search_result[] = "n/a";
								}
								$return["ret"]["dat"]["srch"]["country"] = $the_search_result;
								
								if( $the_search_result_count > 50 )
								{
									$return["ret"]["dat"]["popup"]["title"] = "TOO MANY";
									$return["ret"]["dat"]["popup"]["body"] = "Too many results. Try a more specific keyword!";
								}
								else
								{
									$return["ret"]["dat"]["popup"]["title"] = "RESULT FOUND";
									$return["ret"]["dat"]["popup"]["body"] = "Results found!!";
								}
								
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "COUNTRY SEARCH - FOUND";
							}
						}
						else
						{
							$return["ret"]["dat"]["popup"]["title"] = "NO RESULT";
							$return["ret"]["dat"]["popup"]["body"] = "No entry found. Try a different keyword!";
						
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "COUNTRY SEARCH - NO RESULT FOUND";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "COUNTRY SEARCH - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "COUNTRY SEARCH - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}
		
		case "cy_list":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|page", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_page_number = (int)$captureddata->page * $the_limit_per_page;
				$the_keyword = "n/a";
				if(array_key_exists_r("kword", $captureddata))
				{
					$the_keyword = (string)$captureddata->kword;
				}
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("select count(*) as `count` from `n_country` where `cy_id`>=?;",array(1),'json');
						$objUser = json_decode($json);
		
						$temp_total_counts = $objUser->data->query_result[0]->count;
						
						$json = $conn->doQuery("select `cy_id`,`cy_name`,`cy_creator`,`cy_date_created`,`cy_is_active` from `n_country` where `cy_id`>=? ORDER BY `cy_name` ASC LIMIT ? OFFSET ?;",array(1, $the_limit_per_page,$the_page_number),'json');
						$objUser = json_decode($json);
						
						if( $the_keyword != "n/a" )
						{
							$json = $conn->doQuery("select count(*) as `count` from `n_country` where `cy_name` LIKE ?;",array("%$the_keyword%"),'json');
							$objUser = json_decode($json);
		
							$temp_total_counts = $objUser->data->query_result[0]->count;
						
							$json = $conn->doQuery("select `cy_id`,`cy_name`,`cy_creator`,`cy_date_created`,`cy_is_active` from `n_country` where `cy_name` LIKE ? ORDER BY `cy_name` ASC LIMIT ? OFFSET ?;",array("%$the_keyword%", $the_limit_per_page,$the_page_number),'json');
							$objUser = json_decode($json);
						}
					
						// if exists
						if(!empty($objUser->data->query_result))
						{
							$the_search_result = array();
							foreach($objUser->data->query_result as $key => $value)
							{
								$the_search_result[] = (string)$value->cy_id;
								$temp_search_text = (string)$value->cy_name;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$the_search_result[] = (string)$value->cy_creator;
								$the_search_result[] = (string)$value->cy_date_created;
								$the_search_result[] = (string)$value->cy_is_active;
							}
							$return["ret"]["dat"]["list"]["country"] = $the_search_result;
							
							$return["ret"]["dat"]["list"]["total"] = $temp_total_counts;
							
							$return["ret"]["dat"]["list"]["itmlmt"] = $the_limit_per_page;
							
							$return["ret"]["dat"]["popup"]["title"] = "FOUND";
							$return["ret"]["dat"]["popup"]["body"] = "Results found!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "COUNTRY LIST - SUCCESSFUL";
						}
						else
						{
							if( $the_keyword != "n/a" )
							{
								$return["ret"]["dat"]["popup"]["title"] = "NOT FOUND";
								$return["ret"]["dat"]["popup"]["body"] = "No results found. Try a different search keyword!";
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "NOT FOUND";
								$return["ret"]["dat"]["popup"]["body"] = "No entry is found. Create a new one first!";
							}
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "COUNTRY LIST - NO RESULT";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "COUNTRY LIST - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "COUNTRY LIST - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}

		case "cy_edit":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|id|titl|actv", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_id = (int)$captureddata->id;
				$the_title = (string)$captureddata->titl;
				$the_description = (string)$captureddata->desc;
				$the_is_active = (int)$captureddata->actv;
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						// check if username or email already exists
						$json = $conn->doQuery("select `cy_id` from `n_country` where `cy_id`=? LIMIT 1;",array($the_id),'json');
						$objUser = json_decode($json);
					
						// if doesnt exists, somethings not right
						if(empty($objUser->data->query_result))
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "Id doesn't exist in the database!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "COUNTRY EDIT - ID DOESN'T EXIST";
						}
						else
						{
							$json = $conn->doQuery("update `n_country` set `cy_name`=?,`cy_is_active`=? where `cy_id`=?;",array($the_title,$the_is_active,$the_id),'json');
							$objUser = json_decode($json);
		
							// if update succeeded
							if(strcmp($objUser->data->result,"ok")==0)
							{
								$return["ret"]["dat"]["popup"]["title"] = "UPDATED";
								$return["ret"]["dat"]["popup"]["body"] = "Genre info updated!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "COUNTRY EDIT - SUCCESSFUL";
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "ERROR";
								$return["ret"]["dat"]["popup"]["body"] = "Genre edit failed. Please try again!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "COUNTRY EDIT - FAILED TO UPDATE THE DATABASE";
							}
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "COUNTRY EDIT - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "COUNTRY EDIT - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
		case "t_create":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|titl|desc", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_title = (string)$captureddata->titl;
				$the_description = (string)$captureddata->desc;
				
				$json = $conn->doQuery("select `a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("insert into `n_type` (`t_name`,`t_desc`,`t_creator`,`t_date_created`) values (?,?,?,?);",array($the_title,$the_description,$the_creator_id,$conn->getDateTimeNow()),'json');
						$objUser = json_decode($json);
		
						// if creation succeeded
						if(strcmp($objUser->data->result,"ok")==0)
						{
							$return["ret"]["dat"]["popup"]["title"] = "CREATED";
							$return["ret"]["dat"]["popup"]["body"] = "New video type created successfully!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "TYPE CREATION - SUCCESSFUL";
								
							GetVideoTypes($conn);
						}
						else
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "Video type creation failed. Please try again!";
						
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "TYPE CREATION - FAILED TO INSERT NEW ENTRY TO DATABASE";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "TYPE CREATION - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "TYPE CREATION - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
		case "t_list":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|page", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_page_number = (int)$captureddata->page * $the_limit_per_page;
				$the_keyword = "n/a";
				if(array_key_exists_r("kword", $captureddata))
				{
					$the_keyword = (string)$captureddata->kword;
				}
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("select count(*) as `count` from `n_type` where `t_id`>=?;",array(1),'json');
						$objUser = json_decode($json);
		
						$temp_total_counts = $objUser->data->query_result[0]->count;
						
						$json = $conn->doQuery("select `t_id`,`t_name`,`t_desc`,`t_creator`,`t_date_created`,`t_is_active` from `n_type` where `t_id`>=? ORDER BY `t_name` ASC LIMIT ? OFFSET ?;",array(1, $the_limit_per_page,$the_page_number),'json');
						$objUser = json_decode($json);
						
						if( $the_keyword != "n/a" )
						{
							$json = $conn->doQuery("select count(*) as `count` from `n_type` where `t_name` LIKE ?;",array("%$the_keyword%"),'json');
							$objUser = json_decode($json);
		
							$temp_total_counts = $objUser->data->query_result[0]->count;
						
							$json = $conn->doQuery("select `t_id`,`t_name`,`t_desc`,`t_creator`,`t_date_created`,`t_is_active` from `n_type` where `t_name` LIKE ? ORDER BY `t_name` ASC LIMIT ? OFFSET ?;",array("%$the_keyword%", $the_limit_per_page,$the_page_number),'json');
							$objUser = json_decode($json);
						}
					
						// if exists
						if(!empty($objUser->data->query_result))
						{
							$the_search_result = array();
							foreach($objUser->data->query_result as $key => $value)
							{
								$the_search_result[] = (string)$value->t_id;
								$temp_search_text = (string)$value->t_name;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$temp_search_text = (string)$value->t_desc;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$the_search_result[] = (string)$value->t_creator;
								$the_search_result[] = (string)$value->t_date_created;
								$the_search_result[] = (string)$value->t_is_active;
							}
							$return["ret"]["dat"]["list"]["type"] = $the_search_result;
							
							$return["ret"]["dat"]["list"]["total"] = $temp_total_counts;
							
							$return["ret"]["dat"]["list"]["itmlmt"] = $the_limit_per_page;
							
							$return["ret"]["dat"]["popup"]["title"] = "FOUND";
							$return["ret"]["dat"]["popup"]["body"] = "Results found!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "TYPE LIST - SUCCESSFUL";
						}
						else
						{
							if( $the_keyword != "n/a" )
							{
								$return["ret"]["dat"]["popup"]["title"] = "NOT FOUND";
								$return["ret"]["dat"]["popup"]["body"] = "No results found. Try a different search keyword!";
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "NOT FOUND";
								$return["ret"]["dat"]["popup"]["body"] = "No entry is found. Create a new one first!";
							}
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "TYPE LIST - NO RESULT";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "TYPE LIST - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "TYPE LIST - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}

		case "t_edit":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|id|titl|desc|actv", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_id = (int)$captureddata->id;
				$the_title = (string)$captureddata->titl;
				$the_description = (string)$captureddata->desc;
				$the_is_active = (int)$captureddata->actv;
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						// check if username or email already exists
						$json = $conn->doQuery("select `t_id` from `n_type` where `t_id`=? LIMIT 1;",array($the_id),'json');
						$objUser = json_decode($json);
					
						// if doesnt exists, somethings not right
						if(empty($objUser->data->query_result))
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "Id doesn't exist in the database!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "TYPE EDIT - ID DOESN'T EXIST";
						}
						else
						{
							$json = $conn->doQuery("update `n_type` set `t_name`=?, `t_desc`=?,`t_is_active`=? where `t_id`=?;",array($the_title,$the_description,$the_is_active,$the_id),'json');
							$objUser = json_decode($json);
		
							// if update succeeded
							if(strcmp($objUser->data->result,"ok")==0)
							{
								$return["ret"]["dat"]["popup"]["title"] = "UPDATED";
								$return["ret"]["dat"]["popup"]["body"] = "Type info updated!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "TYPE EDIT - SUCCESSFUL";
								
								GetVideoTypes($conn);
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "ERROR";
								$return["ret"]["dat"]["popup"]["body"] = "Type edit failed. Please try again!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "TYPE EDIT - FAILED TO UPDATE THE DATABASE";
							}
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "TYPE EDIT - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "TYPE EDIT - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
		case "g_create":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|titl|desc", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_title = (string)$captureddata->titl;
				$the_description = (string)$captureddata->desc;
				
				$json = $conn->doQuery("select `a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("insert into `n_genre` (`g_name`,`g_desc`,`g_creator`,`g_date_created`) values (?,?,?,?);",array($the_title,$the_description,$the_creator_id,$conn->getDateTimeNow()),'json');
						$objUser = json_decode($json);
		
						// if creation succeeded
						if(strcmp($objUser->data->result,"ok")==0)
						{
							$return["ret"]["dat"]["popup"]["title"] = "CREATED";
							$return["ret"]["dat"]["popup"]["body"] = "New genre type created successfully!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "GENRE CREATION - SUCCESSFUL";
							
							GetVideoGenres($conn);
						}
						else
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "Genre creation failed. Please try again!";
						
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "GENRE CREATION - FAILED TO INSERT NEW ENTRY TO DATABASE";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "GENRE CREATION - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "GENRE CREATION - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
		case "g_list":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|page", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_page_number = (int)$captureddata->page * $the_limit_per_page;
				$the_keyword = "n/a";
				if(array_key_exists_r("kword", $captureddata))
				{
					$the_keyword = (string)$captureddata->kword;
				}
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("select count(*) as `count` from `n_genre` where `g_id`>=?;",array(1),'json');
						$objUser = json_decode($json);
		
						$temp_total_counts = $objUser->data->query_result[0]->count;
						
						$json = $conn->doQuery("select `g_id`,`g_name`,`g_desc`,`g_creator`,`g_date_created`,`g_is_active` from `n_genre` where `g_id`>=? ORDER BY `g_name` ASC LIMIT ? OFFSET ?;",array(1, $the_limit_per_page,$the_page_number),'json');
						$objUser = json_decode($json);
						
						if( $the_keyword != "n/a" )
						{
							$json = $conn->doQuery("select count(*) as `count` from `n_genre` where `g_name` LIKE ?;",array("%$the_keyword%"),'json');
							$objUser = json_decode($json);
		
							$temp_total_counts = $objUser->data->query_result[0]->count;
						
							$json = $conn->doQuery("select `g_id`,`g_name`,`g_desc`,`g_creator`,`g_date_created`,`g_is_active` from `n_genre` where `g_name` LIKE ? ORDER BY `g_name` ASC LIMIT ? OFFSET ?;",array("%$the_keyword%", $the_limit_per_page,$the_page_number),'json');
							$objUser = json_decode($json);
						}
					
						// if exists
						if(!empty($objUser->data->query_result))
						{
							$the_search_result = array();
							foreach($objUser->data->query_result as $key => $value)
							{
								$the_search_result[] = (string)$value->g_id;
								$temp_search_text = (string)$value->g_name;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$temp_search_text = (string)$value->g_desc;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$the_search_result[] = (string)$value->g_creator;
								$the_search_result[] = (string)$value->g_date_created;
								$the_search_result[] = (string)$value->g_is_active;
							}
							$return["ret"]["dat"]["list"]["genre"] = $the_search_result;
							
							$return["ret"]["dat"]["list"]["total"] = $temp_total_counts;
							
							$return["ret"]["dat"]["list"]["itmlmt"] = $the_limit_per_page;
							
							$return["ret"]["dat"]["popup"]["title"] = "FOUND";
							$return["ret"]["dat"]["popup"]["body"] = "Results found!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "GENRE LIST - SUCCESSFUL";
						}
						else
						{
							if( $the_keyword != "n/a" )
							{
								$return["ret"]["dat"]["popup"]["title"] = "NOT FOUND";
								$return["ret"]["dat"]["popup"]["body"] = "No results found. Try a different search keyword!";
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "NOT FOUND";
								$return["ret"]["dat"]["popup"]["body"] = "No entry is found. Create a new one first!";
							}
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "GENRE LIST - NO RESULT";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "GENRE LIST - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "GENRE LIST - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}

		case "g_edit":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|id|titl|desc|actv", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_id = (int)$captureddata->id;
				$the_title = (string)$captureddata->titl;
				$the_description = (string)$captureddata->desc;
				$the_is_active = (int)$captureddata->actv;
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						// check if username or email already exists
						$json = $conn->doQuery("select `g_id` from `n_genre` where `g_id`=? LIMIT 1;",array($the_id),'json');
						$objUser = json_decode($json);
					
						// if doesnt exists, somethings not right
						if(empty($objUser->data->query_result))
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "Id doesn't exist in the database!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "GENRE EDIT - ID DOESN'T EXIST";
						}
						else
						{
							$json = $conn->doQuery("update `n_genre` set `g_name`=?, `g_desc`=?,`g_is_active`=? where `g_id`=?;",array($the_title,$the_description,$the_is_active,$the_id),'json');
							$objUser = json_decode($json);
		
							// if update succeeded
							if(strcmp($objUser->data->result,"ok")==0)
							{
								$return["ret"]["dat"]["popup"]["title"] = "UPDATED";
								$return["ret"]["dat"]["popup"]["body"] = "Genre info updated!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "GENRE EDIT - SUCCESSFUL";
								
								GetVideoGenres($conn);
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "ERROR";
								$return["ret"]["dat"]["popup"]["body"] = "Genre edit failed. Please try again!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "GENRE EDIT - FAILED TO UPDATE THE DATABASE";
							}
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "GENRE EDIT - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "GENRE EDIT - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
		case "f_create":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|titl|desc|compid|comp|cntryid|cntry|cntryacid|cntryac|yrprod|type|genre|purl", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_title = (string)$captureddata->titl;
				$the_description = (string)$captureddata->desc;
				$the_company_id = (string)$captureddata->compid;
				$the_company = (string)$captureddata->comp;
				$the_country_id = (string)$captureddata->cntryid;
				$the_country = (string)$captureddata->cntry;
				$the_country_access_id = (string)$captureddata->cntryacid;
				$the_country_access = (string)$captureddata->cntryac;
				$the_year_production = (string)$captureddata->yrprod;
				$the_type = (int)$captureddata->type;
				$the_genre = (string)$captureddata->genre;
				$the_poster_url = (string)$captureddata->purl;
				$the_genre_1 = -1;
				$the_genre_2 = -1;
				$the_genre_3 = -1;
				$the_genre_4 = -1;
				$the_genre_5 = -1;

				if( IsNullOrEmptyString($the_country_access_id) )
				{
					$the_country_access_id = "1";
				}

				if( IsNullOrEmptyString($the_country_access) )
				{
					$the_country_access = "All";
				}

				$json = $conn->doQuery("select `a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						SplitGenre($the_genre, $the_genre_1, $the_genre_2, $the_genre_3, $the_genre_4, $the_genre_5);
						
						$json = $conn->doQuery("insert into `n_franchise` (`f_name`,`f_synopsis`,`f_company_id`,`f_company`,`f_country_id`,`f_country`,`f_country_access_id`,`f_country_access`,`f_year_production`,`f_type_id`,`f_genre_id_1`,`f_genre_id_2`,`f_genre_id_3`,`f_genre_id_4`,`f_genre_id_5`,`f_date_added`,`f_last_updated`,`f_creator_id`,`f_url_poster`) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);",array($the_title,$the_description,$the_company_id,$the_company,$the_country_id,$the_country,$the_country_access_id,$the_country_access,$the_year_production,$the_type,$the_genre_1, $the_genre_2, $the_genre_3, $the_genre_4, $the_genre_5, $conn->getDateTimeNow(),$conn->getDateTimeNow(),$the_creator_id,$the_poster_url),'json');
						$objUser = json_decode($json);
		
						// if creation succeeded
						if(strcmp($objUser->data->result,"ok")==0)
						{
							$return["ret"]["dat"]["popup"]["title"] = "CREATED";
							$return["ret"]["dat"]["popup"]["body"] = "New franchise created successfully!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "FRANCHISE CREATION - SUCCESSFUL";
						}
						else
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "Franchise creation failed. Please try again!";
						
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "FRANCHISE CREATION - FAILED TO INSERT NEW ENTRY TO DATABASE";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "FRANCHISE CREATION - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "FRANCHISE CREATION - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
		case "f_search":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|kword", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_keyword = (string)$captureddata->kword;
				
				$json = $conn->doQuery("select `a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					error_log("--- ".$the_creator_access_level, 3, "/home/admin/html/new/comm/php_errors.log");
            		
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("select count(*) as `count` from `n_franchise` where `f_name` LIKE ?;",array("%$the_keyword%"),'json');
						$objUser = json_decode($json);
		
						$the_search_result_count = $objUser->data->query_result[0]->count;
						
						if( $the_search_result_count > 0 )
						{
							$json = $conn->doQuery("select `f_id`,`f_name`,`f_synopsis` from `n_franchise` where `f_name` LIKE ? LIMIT 50;",array("%$the_keyword%"),'json');
							$objUser = json_decode($json);
							
							// has to be found since we checked the total count before this, if not that means we have different argument in the select
							if(!empty($objUser->data->query_result))
							{
								$the_search_result = array();
								foreach($objUser->data->query_result as $key => $value)
								{
									$the_search_result[] = (string)$value->f_id;
									$the_search_result[] = (string)$value->f_name;
									
									$temp_search_description = (string)$value->f_synopsis;
									if( IsNullOrEmptyString($temp_search_description) )
									{
										$temp_search_description = "n/a";
									}
									$the_search_result[] = $temp_search_description;
								}
								$return["ret"]["dat"]["srch"]["frncs"] = $the_search_result;
								
								if( $the_search_result_count > 50 )
								{
									$return["ret"]["dat"]["popup"]["title"] = "TOO MANY";
									$return["ret"]["dat"]["popup"]["body"] = "Too many results. Try a more specific keyword!";
								}
								else
								{
									$return["ret"]["dat"]["popup"]["title"] = "RESULT FOUND";
									$return["ret"]["dat"]["popup"]["body"] = "Results found!!";
								}
								
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "FRANCHISE SEARCH - FOUND";
							}
						}
						else
						{
							$return["ret"]["dat"]["popup"]["title"] = "NO RESULT";
							$return["ret"]["dat"]["popup"]["body"] = "No entry found. Try a different keyword!";
						
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "FRANCHISE SEARCH - NO RESULT FOUND";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "FRANCHISE SEARCH - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "FRANCHISE SEARCH - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}
		
		case "f_list":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|page", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_page_number = (int)$captureddata->page * $the_limit_per_page;
				$the_keyword = "n/a";
				if(array_key_exists_r("kword", $captureddata))
				{
					$the_keyword = (string)$captureddata->kword;
				}
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("select count(*) as `count` from `n_franchise` where `f_id`>=?;",array(1),'json');
						$objUser = json_decode($json);
		
						$temp_total_counts = $objUser->data->query_result[0]->count;
						
						$json = $conn->doQuery("select `f_id`,`f_name`,`f_company_id`,`f_company`,`f_country_id`,`f_country`,`f_country_access_id`,`f_country_access`,`f_year_production`,`f_synopsis`,`f_genre_id_1`,`f_genre_id_2`,`f_genre_id_3`,`f_genre_id_4`,`f_genre_id_5`,`f_type_id`,`f_url_poster`,`f_creator_id`,`f_is_active`,`f_date_added`,`f_last_updated` from `n_franchise` where `f_id`>=? ORDER BY `f_name` ASC LIMIT ? OFFSET ?;",array(1, $the_limit_per_page,$the_page_number),'json');
						$objUser = json_decode($json);
						
						if( $the_keyword != "n/a" )
						{
							$json = $conn->doQuery("select count(*) as `count` from `n_franchise` where `f_name` LIKE ?;",array("%$the_keyword%"),'json');
							$objUser = json_decode($json);
		
							$temp_total_counts = $objUser->data->query_result[0]->count;
						
							$json = $conn->doQuery("select `f_id`,`f_name`,`f_company_id`,`f_company`,`f_country_id`,`f_country`,`f_country_access_id`,`f_country_access`,`f_year_production`,`f_synopsis`,`f_genre_id_1`,`f_genre_id_2`,`f_genre_id_3`,`f_genre_id_4`,`f_genre_id_5`,`f_type_id`,`f_url_poster`,`f_creator_id`,`f_is_active`,`f_date_added`,`f_last_updated` from `n_franchise` where `f_name` LIKE ? ORDER BY `f_name` ASC LIMIT ? OFFSET ?;",array("%$the_keyword%", $the_limit_per_page,$the_page_number),'json');
							$objUser = json_decode($json);
						}
					
						// if exists
						if(!empty($objUser->data->query_result))
						{
							$the_search_result = array();
							foreach($objUser->data->query_result as $key => $value)
							{
								$the_search_result[] = (string)$value->f_id;
								
								$temp_search_text = (string)$value->f_name;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$temp_search_text = (string)$value->f_company_id;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$temp_search_text = (string)$value->f_company;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$temp_search_text = (string)$value->f_synopsis;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$the_search_result[] = (string)$value->f_genre_id_1;
								$the_search_result[] = (string)$value->f_genre_id_2;
								$the_search_result[] = (string)$value->f_genre_id_3;
								$the_search_result[] = (string)$value->f_genre_id_4;
								$the_search_result[] = (string)$value->f_genre_id_5;
								
								$temp_search_text = (string)$value->f_type_id;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "0";
								}
								$the_search_result[] = $temp_search_text;
								
								$temp_search_text = (string)$value->f_url_poster;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$the_search_result[] = (string)$value->f_creator_id;
								$the_search_result[] = (string)$value->f_is_active;
								$the_search_result[] = (string)$value->f_date_added;
								$the_search_result[] = (string)$value->f_last_updated;
								
								$temp_search_text = (string)$value->f_country_id;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$temp_search_text = (string)$value->f_country;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$temp_search_text = (string)$value->f_country_access_id;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$temp_search_text = (string)$value->f_country_access;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
								
								$temp_search_text = (string)$value->f_year_production;
								if( IsNullOrEmptyString($temp_search_text) )
								{
									$temp_search_text = "n/a";
								}
								$the_search_result[] = $temp_search_text;
							}
							$return["ret"]["dat"]["list"]["franchise"] = $the_search_result;
							
							$return["ret"]["dat"]["list"]["total"] = $temp_total_counts;
							
							$return["ret"]["dat"]["list"]["itmlmt"] = $the_limit_per_page;
							
							$return["ret"]["dat"]["popup"]["title"] = "FOUND";
							$return["ret"]["dat"]["popup"]["body"] = "Results found!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "FRANCHISE LIST - SUCCESSFUL";
						}
						else
						{
							if( $the_keyword != "n/a" )
							{
								$return["ret"]["dat"]["popup"]["title"] = "NOT FOUND";
								$return["ret"]["dat"]["popup"]["body"] = "No results found. Try a different search keyword!";
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "NOT FOUND";
								$return["ret"]["dat"]["popup"]["body"] = "No entry is found. Create a new one first!";
							}
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "FRANCHISE LIST - NO RESULT";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "FRANCHISE LIST - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "FRANCHISE LIST - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}

		case "f_edit":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|id|titl|desc|compid|comp|cntryid|cntry|cntryacid|cntryac|yrprod|type|purl|actv", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_id = (int)$captureddata->id;
				$the_title = (string)$captureddata->titl;
				$the_description = (string)$captureddata->desc;
				$the_company_id = (string)$captureddata->compid;
				$the_company = (string)$captureddata->comp;
				$the_country_id = (string)$captureddata->cntryid;
				$the_country = (string)$captureddata->cntry;
				$the_country_access_id = (string)$captureddata->cntryacid;
				$the_country_access = (string)$captureddata->cntryac;
				$the_year_production = (string)$captureddata->yrprod;
				$the_type = (int)$captureddata->type;
				$the_genre = "";
				$the_poster_url = (string)$captureddata->purl;
				$the_genre_1 = -1;
				$the_genre_2 = -1;
				$the_genre_3 = -1;
				$the_genre_4 = -1;
				$the_genre_5 = -1;
				$the_is_active = (int)$captureddata->actv;

				if( IsNullOrEmptyString($the_country_access_id) )
				{
					$the_country_access_id = "1";
				}

				if( IsNullOrEmptyString($the_country_access) )
				{
					$the_country_access = "All";
				}
				
				$the_genre_is_passed_in = false;
				if(array_key_exists_r("genre", $captureddata))
				{
					$the_genre_is_passed_in = true;
					$the_genre = (string)$captureddata->genre;
				}
			
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						// check if username or email already exists
						$json = $conn->doQuery("select `f_id` from `n_franchise` where `f_id`=? LIMIT 1;",array($the_id),'json');
						$objUser = json_decode($json);
					
						// if doesnt exists, somethings not right
						if(empty($objUser->data->query_result))
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "Id doesn't exist in the database!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "FRANCHISE EDIT - ID DOESN'T EXIST";
						}
						else
						{		
							if( $the_genre_is_passed_in == true )
							{
								SplitGenre($the_genre, $the_genre_1, $the_genre_2, $the_genre_3, $the_genre_4, $the_genre_5);
							
								$json = $conn->doQuery("update `n_franchise` set `f_name`=?, `f_company_id`=?, `f_company`=?, `f_country_id`=?, `f_country`=?, `f_country_access_id`=?, `f_country_access`=?, `f_year_production`=?,`f_synopsis`=?, `f_genre_id_1`=?, `f_genre_id_2`=?, `f_genre_id_3`=?, `f_genre_id_4`=?, `f_genre_id_5`=?, `f_type_id`=?, `f_url_poster`=?, `f_is_active`=?, `f_last_updated`=? where `f_id`=?;",array($the_title,$the_company_id,$the_company,$the_country_id,$the_country,$the_country_access_id, $the_country_access,$the_year_production,$the_description,$the_genre_1,$the_genre_2,$the_genre_3,$the_genre_4,$the_genre_5,$the_type,$the_poster_url,$the_is_active,$conn->getDateTimeNow(),$the_id),'json');
								$objUser = json_decode($json);
							}
							else
							{
								$json = $conn->doQuery("update `n_franchise` set `f_name`=?, `f_company_id`=?, `f_company`=?, `f_country_id`=?, `f_country`=?, `f_country_access_id`=?, `f_country_access`=?, `f_year_production`=?,`f_synopsis`=?, `f_type_id`=?, `f_url_poster`=?, `f_is_active`=?, `f_last_updated`=? where `f_id`=?;",array($the_title,$the_company_id,$the_company,$the_country_id,$the_country,$the_country_access_id, $the_country_access,$the_year_production,$the_description,$the_type,$the_poster_url,$the_is_active,$conn->getDateTimeNow(),$the_id),'json');
								$objUser = json_decode($json);
							}
							
		
							// if update succeeded
							if(strcmp($objUser->data->result,"ok")==0)
							{
								$return["ret"]["dat"]["popup"]["title"] = "UPDATED";
								$return["ret"]["dat"]["popup"]["body"] = "Franchise info updated!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "FRANCHISE EDIT - SUCCESSFUL";
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "ERROR";
								$return["ret"]["dat"]["popup"]["body"] = "Franchise edit failed. Please try again!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "FRANCHISE EDIT - FAILED TO UPDATE THE DATABASE";
							}
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "FRANCHISE EDIT - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "FRANCHISE EDIT - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
		case "v_create":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|frid|frname|titl|sesn|epsd|yrprod|desc|drctrid|prdcrid|castsid|drctr|prdcr|casts|purl|plurl|ytid|cdn|prioyt|price|ss1|ss2|ss3|ss4|ss5|ftrd", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_franchise_id = (string)$captureddata->frid;
				$the_franchise_name = (string)$captureddata->frname;
				$the_title = (string)$captureddata->titl;
				$the_season = (int)$captureddata->sesn;
				$the_episode = (int)$captureddata->epsd;
				$the_year_production = (string)$captureddata->yrprod;
				$the_description = (string)$captureddata->desc;
				$the_duration = 0;
				$the_director_id = (string)$captureddata->drctrid;
				$the_producer_id = (string)$captureddata->prdcrid;
				$the_casts_id = (string)$captureddata->castsid;
				$the_director = (string)$captureddata->drctr;
				$the_producer = (string)$captureddata->prdcr;
				$the_casts = (string)$captureddata->casts;
				$the_poster_url = (string)$captureddata->purl;
				$the_poster_landscape_url = (string)$captureddata->plurl;
				$the_youtube_id = (string)$captureddata->ytid;
				$the_cdn_url = (string)$captureddata->cdn;
				$the_prioritize_youtube = (int)$captureddata->prioyt;
				$the_price = (int)$captureddata->price;
				$the_screenshot_url_1 = (string)$captureddata->ss1;
				$the_screenshot_url_2 = (string)$captureddata->ss2;
				$the_screenshot_url_3 = (string)$captureddata->ss3;
				$the_screenshot_url_4 = (string)$captureddata->ss4;
				$the_screenshot_url_5 = (string)$captureddata->ss5;
				$the_is_featured = (int)$captureddata->ftrd;
				
				if(array_key_exists_r("dura", $captureddata))
				{
					$the_duration = (string)$captureddata->dura;
				}

				$json = $conn->doQuery("select `a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$json = $conn->doQuery("insert into `n_video` (`v_franchise_id`,`v_franchise_name`,`v_title`,`v_season`,`v_episode`,`v_year_production`,`v_synopsis`,`v_duration`,`v_director_id`,`v_producer_id`,`v_casts_id`,`v_director`,`v_producer`,`v_casts`,`v_url_poster`,`v_url_poster_landscape`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_price`,`v_screenshot_url_1`,`v_screenshot_url_2`,`v_screenshot_url_3`,`v_screenshot_url_4`,`v_screenshot_url_5`,`v_is_featured`,`v_uploader_admin_id`,`v_updater_admin_id`,`v_date_uploaded`,`v_last_updated`) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);",array($the_franchise_id,$the_franchise_name,$the_title,$the_season,$the_episode,$the_year_production,$the_description,$the_duration,$the_director_id,$the_producer_id,$the_casts_id,$the_director,$the_producer,$the_casts,$the_poster_url,$the_poster_landscape_url,$the_youtube_id,$the_cdn_url,$the_prioritize_youtube,$the_price,$the_screenshot_url_1,$the_screenshot_url_2,$the_screenshot_url_3,$the_screenshot_url_4,$the_screenshot_url_5,$the_is_featured,$the_creator_id,$the_creator_id,$conn->getDateTimeNow(),$conn->getDateTimeNow()),'json');
						$objUser = json_decode($json);
		
						// if creation succeeded
						if(strcmp($objUser->data->result,"ok")==0)
						{
							$return["ret"]["dat"]["popup"]["title"] = "CREATED";
							$return["ret"]["dat"]["popup"]["body"] = "New video created successfully!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "VIDEO CREATION - SUCCESSFUL";
						}
						else
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "Video creation failed. Please try again!";
						
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "VIDEO CREATION - FAILED TO INSERT NEW ENTRY TO DATABASE";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "VIDEO CREATION - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "VIDEO CREATION - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
		case "v_list":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|page", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_page_number = (int)$captureddata->page * $the_limit_per_page;
				$the_keyword = "n/a";
				if(array_key_exists_r("kword", $captureddata))
				{
					$the_keyword = (string)$captureddata->kword;
				}
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						$temp_total_counts = GetVideoList($conn, false, $the_keyword, $the_limit_per_page, $the_page_number);
						
						// if exists
						if( $temp_total_counts > 0 )
						{
							$return["ret"]["dat"]["list"]["total"] = $temp_total_counts;
							
							$return["ret"]["dat"]["list"]["itmlmt"] = $the_limit_per_page;
							
							$return["ret"]["dat"]["popup"]["title"] = "FOUND";
							$return["ret"]["dat"]["popup"]["body"] = "Results found!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "VIDEO LIST - SUCCESSFUL";
						}
						else
						{
							if( $the_keyword != "n/a" )
							{
								$return["ret"]["dat"]["popup"]["title"] = "NOT FOUND";
								$return["ret"]["dat"]["popup"]["body"] = "No results found. Try a different search keyword!";
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "NOT FOUND";
								$return["ret"]["dat"]["popup"]["body"] = "No entry is found. Create a new one first!";
							}
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "VIDEO LIST - NO RESULT";
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "VIDEO LIST - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "VIDEO LIST - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}

		case "v_edit":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("creator|id|frid|frname|titl|sesn|epsd|yrprod|desc|drctrid|prdcrid|castsid|drctr|prdcr|casts|purl|plurl|ytid|cdn|prioyt|price|ss1|ss2|ss3|ss4|ss5|actv|ftrd", $captureddata))
			{
				$the_creator_id = (int)$captureddata->creator;
				$the_id = (int)$captureddata->id;
				$the_franchise_id = (string)$captureddata->frid;
				$the_franchise_name = (string)$captureddata->frname;
				$the_title = (string)$captureddata->titl;
				$the_season = (int)$captureddata->sesn;
				$the_episode = (int)$captureddata->epsd;
				$the_year_production = (string)$captureddata->yrprod;
				$the_description = (string)$captureddata->desc;
				$the_duration = 0;
				$the_director_id = (string)$captureddata->drctrid;
				$the_producer_id = (string)$captureddata->prdcrid;
				$the_casts_id = (string)$captureddata->castsid;
				$the_director = (string)$captureddata->drctr;
				$the_producer = (string)$captureddata->prdcr;
				$the_casts = (string)$captureddata->casts;
				$the_poster_url = (string)$captureddata->purl;
				$the_poster_landscape_url = (string)$captureddata->plurl;
				$the_youtube_id = (string)$captureddata->ytid;
				$the_cdn_url = (string)$captureddata->cdn;
				$the_prioritize_youtube = (int)$captureddata->prioyt;
				$the_price = (int)$captureddata->price;
				$the_screenshot_url_1 = (string)$captureddata->ss1;
				$the_screenshot_url_2 = (string)$captureddata->ss2;
				$the_screenshot_url_3 = (string)$captureddata->ss3;
				$the_screenshot_url_4 = (string)$captureddata->ss4;
				$the_screenshot_url_5 = (string)$captureddata->ss5;
				$the_is_active = (int)$captureddata->actv;
				$the_is_featured = (int)$captureddata->ftrd;
				
				
				if(array_key_exists_r("dura", $captureddata))
				{
					$the_duration = (string)$captureddata->dura;
				}
				
				$json = $conn->doQuery("select `a_name`,`a_access_level` from `n_admin` where `a_id`=? LIMIT 1;",array($the_creator_id),'json');
				$objUser = json_decode($json);
					
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_creator_name = (string)$objUser->data->query_result[0]->a_name;
					$the_creator_access_level = (string)$objUser->data->query_result[0]->a_access_level;
					
					// check if access level valid
					if( AccessLevelValid((string)$jsonaction, $the_creator_access_level) )
					{
						// check if username or email already exists
						$json = $conn->doQuery("select `v_id` from `n_video` where `v_id`=? LIMIT 1;",array($the_id),'json');
						$objUser = json_decode($json);
					
						// if doesnt exists, somethings not right
						if(empty($objUser->data->query_result))
						{
							$return["ret"]["dat"]["popup"]["title"] = "ERROR";
							$return["ret"]["dat"]["popup"]["body"] = "Id doesn't exist in the database!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "FRANCHISE EDIT - ID DOESN'T EXIST";
						}
						else
						{		
							$json = $conn->doQuery("update `n_video` set `v_franchise_id`=?,`v_franchise_name`=?, `v_title`=?, `v_season`=?,`v_episode`=?, `v_year_production`=?, `v_synopsis`=?, `v_duration`=?, `v_director_id`=?, `v_producer_id`=?, `v_casts_id`=?,`v_director`=?, `v_producer`=?, `v_casts`=?, `v_url_poster`=?, `v_url_poster_landscape`=?,`v_url_youtube_id`=?, `v_url_cdn`=?, `v_prioritize_youtube`=?, `v_price`=?, `v_screenshot_url_1`=?, `v_screenshot_url_2`=?, `v_screenshot_url_3`=?, `v_screenshot_url_4`=?, `v_screenshot_url_5`=?, `v_is_featured`=?, `v_updater_admin_id`=?, `v_last_updated`=?, `v_is_active`=? where `v_id`=?;",array($the_franchise_id,$the_franchise_name,$the_title,$the_season,$the_episode,$the_year_production,$the_description,$the_duration,$the_director_id,$the_producer_id,$the_casts_id,$the_director,$the_producer,$the_casts,$the_poster_url,$the_poster_landscape_url,$the_youtube_id,$the_cdn_url,$the_prioritize_youtube,$the_price,$the_screenshot_url_1,$the_screenshot_url_2,$the_screenshot_url_3,$the_screenshot_url_4,$the_screenshot_url_5,$the_is_featured,$the_creator_id,$conn->getDateTimeNow(),$the_is_active,$the_id),'json');
							$objUser = json_decode($json);
		
							// if update succeeded
							if(strcmp($objUser->data->result,"ok")==0)
							{
								$return["ret"]["dat"]["popup"]["title"] = "UPDATED";
								$return["ret"]["dat"]["popup"]["body"] = "Video info updated!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "VIDEO EDIT - SUCCESSFUL";
							}
							else
							{
								$return["ret"]["dat"]["popup"]["title"] = "ERROR";
								$return["ret"]["dat"]["popup"]["body"] = "Video edit failed. Please try again!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "VIDEO EDIT - FAILED TO UPDATE THE DATABASE";
							}
						}
					}
					else
					{
						$return["ret"]["dat"]["popup"]["title"] = "ERROR";
						$return["ret"]["dat"]["popup"]["body"] = "You don't have enough privilege to do this action!";
							
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "VIDEO EDIT - NOT ENOUGH PRIVILEGE";
					}
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "Creator ID doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "VIDEO EDIT - CREATOR DOESN'T EXIST";
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}	
		
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

function addpadding($string, $blocksize = 32)
{
    $len = strlen($string);
    $pad = $blocksize - ($len % $blocksize);
    $string .= str_repeat(chr($pad), $pad);
    return $string;
}

function strippadding($string)
{
    $slast = ord(substr($string, -1));
    $slastc = chr($slast);
    $pcheck = substr($string, -$slast);
    if(preg_match("/$slastc{".$slast."}/", $string)){
        $string = substr($string, 0, strlen($string)-$slast);
        return $string;
    } else {
        return false;
    }
}

function encrypt($string = "")
{
	//$keyfile = "./AES.keyz";
	//$keyfile = file($keyfile);
    $key = base64_decode("PSVJQRk9QTEpNVU1DWUZCRVFGV1VVT0ZOV1RRU1NaWQ=");
    $iv = base64_decode("YWlFLVEZZUFNaWlhPQ01ZT0lLWU5HTFJQVFNCRUJZVA=");
	return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, addpadding($string), MCRYPT_MODE_CBC, $iv));
}

function decrypt($string = "")
{
	//$keyfile = "./AES.keyz";
	//$keyfile = file($keyfile);
    $key = base64_decode("PSVJQRk9QTEpNVU1DWUZCRVFGV1VVT0ZOV1RRU1NaWQ=");
    $iv = base64_decode("YWlFLVEZZUFNaWlhPQ01ZT0lLWU5HTFJQVFNCRUJZVA=");
	$string = base64_decode($string);
	return strippadding(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $string, MCRYPT_MODE_CBC, $iv));
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

