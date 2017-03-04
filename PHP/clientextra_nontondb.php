<?php

function GetClientExtraAPI($jsonaction, $data)
{
	global $return;

	switch( (string)$jsonaction )
	{	
		case "sample":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("fbid|type", $captureddata))
			{
				$the_fb_id = (string)$captureddata->fbid;
				$the_subscription_type = (string)$captureddata->type;

				if( AddSubscription($conn, $the_fb_id, $the_subscription_type) )
				{
					GetUserInfo($conn, $the_fb_id);
				}
			}
			else if(array_key_exists_r("uid|type", $captureddata))
			{
				$the_user_id = (string)$captureddata->uid;
				$the_subscription_type = (string)$captureddata->type;

				if( AddSubscription($conn, "-1", $the_subscription_type, $the_user_id) )
				{
					GetUserInfo($conn, "-1", $the_user_id);
				}
			}
			else
			{
				// wrong request format
				$return["ret"]["dat"]["message"]["title"] = "ERROR";
				$return["ret"]["dat"]["message"]["body"] = "WRONG REQUEST FORMAT";
					
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID MEMBER FORMAT";
			}
			
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}
		case "cli_get_initial_data":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			//echo $data;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->ip) && isset($captureddata->aps) && isset($captureddata->apv))
			{
				//echo $captureddata->ip;
				$country_name=GetCountryNameFromIp($captureddata->ip);
				get_initial_content($conn, $captureddata->aps, $country_name);
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			echo json_encode($return);
			break;
		}

		default:
		{
			$return["evn"] = "unknown";
			$return["sta"] = "FAIL";
			$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			echo json_encode($return);	
			break;
		}	
	}
}

?>
