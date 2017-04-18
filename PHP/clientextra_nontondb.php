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
			
			$captureddata = json_decode(decrypt($data));
			$country_name=GetCountryNameFromIp($captureddata->ip);
			$rsCountry= getCountryByName($conn, $country_name);

			// TODO : Get signature name and get the id
			$clientId = "";
			$clientName = "";

			$loggedUserName = "";
			$loggedUserFbid = "";
			
			$clientName = $captureddata->aps;
			$clientId = "";
			if( isset($clientName) )
			{
				$jsonClient = $conn->doQuery("Select `cl_id` from  n_client where cl_name='".$clientName."';", null,'json');
				$objClient = json_decode($jsonClient);
				
				if(!empty($objClient->data->query_result))
				{
					$clientId = $objClient->data->query_result[0]->cl_id;
				}
			}
			
			if( isset($captureddata->uid) )
			{
				$jsonUser = $conn->doQuery("Select `u_fullname`,`u_fbid` from  n_user where u_id=".$captureddata->uid.";", null,'json');
				$objUser = json_decode($jsonUser);
				
				if(!empty($objUser->data->query_result))
				{
					$loggedUserName = $objUser->data->query_result[0]->u_fullname;
					$loggedUserFbid = $objUser->data->query_result[0]->u_fbid;
				}
			}
			
			$param=array( 's_user_id'=>$captureddata->uid,
					 's_device_id'=>$captureddata->did,
					 's_fb_id'=>$loggedUserFbid,
					 's_name'=>$loggedUserName,
					 's_origin_ip'=>$captureddata->ip,
					 's_origin_country_id'=>$rsCountry[0]->cy_id,
					 's_origin_country_name'=>$rsCountry[0]->cy_name,
					 's_client_id'=>$clientId,
					 's_client_name'=>$clientName
					);

			$rsSession=insert_session($conn, $param);
			
			if(isset($captureddata->ip) && isset($captureddata->aps) && isset($captureddata->apv) && isset($captureddata->uid) && isset($captureddata->fid) && isset($captureddata->did) )
			{
				get_initial_content($conn, $captureddata->aps, $country_name, $rsSession->data->query_id);

				$param=setup_action_param($conn, $rsSession->data->query_id, (string)$jsonaction, "", "");
				insert_action($conn, $param);
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			
			echo json_encode($return);
			break;
		}

		case "cli_search_database":
		{
			$conn = new database();
			$searchResultLimit = 10;
			$return["evn"] = (string)$jsonaction;
			
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->kwd) && isset($captureddata->pg))
			{
				$rsSession=get_session($conn, $captureddata->sid);
				$country_name=$rsSession[0]->s_origin_country_name;
				
				$page=$searchResultLimit*$captureddata->pg;
				search_franchise($conn, $captureddata->kwd,$country_name, $page);
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}

			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", "");
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_get_autocomplete":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->kwd))
			{
				$rsSession=get_session($conn, $captureddata->sid);

				$country_name=$rsSession[0]->s_origin_country_name;

				get_autocomplete($conn, $captureddata->kwd,$country_name);
			}
			else
			{
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			
			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", "");
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_get_video_details":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			//echo $data;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->activityId) /*&& ($captureddata->activityId>0)*/ && isset($captureddata->videoId) && isset($captureddata->duration) && isset($captureddata->nShares) && isset($captureddata->resolution) && isset($captureddata->uid) && isset($captureddata->uid))
			{
				if($captureddata->uid==null)
				{
					$favorite=false;
				}
				else
				{
					$favorite=check_favorite($conn, $captureddata->uid, $captureddata->videoId);
				}
				
				$session=check_session($conn, $captureddata->sid);
				if($captureddata->videoId!=-1)
					$activity=insert_activity($conn, $captureddata->sid, $captureddata->videoId );
				if($captureddata->activityId!="" || $captureddata->activityId!=-1)
					update_activity($conn, $captureddata->activityId, $captureddata->duration, $captureddata->lastSecond, $captureddata->nShares, $captureddata->resolution, $conn->getDateTimeNow());
				
				$data=get_video_detail($conn,  $captureddata->videoId, $favorite, $activity->data->query_id, $captureddata->uid);
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", $captureddata->videoId);
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_get_comments":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			//echo $data;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->videoId) && isset($captureddata->pg) )
			{
				$page=10*$captureddata->pg;
				get_comments($conn, $captureddata->videoId, $page);
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", $captureddata->videoId);
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_get_comments_by_replied":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			$limit_result_per_page = 10;
			//echo $data;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->cm_id) )
			{
				$page=$limit_result_per_page*$captureddata->pg;
				get_comments_replied($conn, $captureddata->cm_id);
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "comment_id : ".$captureddata->cm_id, "");
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_add_comment":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			//echo $data;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->commentId) && isset($captureddata->userId) && isset($captureddata->videoId) && isset($captureddata->activityId) && isset($captureddata->title) && isset($captureddata->body))
			{
				//$page=10*$captureddata->pg;
				insert_comment($conn, $captureddata->sid, $captureddata->commentId, $captureddata->userId, $captureddata->videoId, $captureddata->activityId, $captureddata->title, $captureddata->body);
				
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			
			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "activity_id : ".$captureddata->activityId, $captureddata->videoId);
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_like_comment":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			//echo $data;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->commentId) && isset($captureddata->userId) && isset($captureddata->activityId))
			{
				//$page=10*$captureddata->pg;
				like_comment($conn, $captureddata->sid, $captureddata->userid, $captureddata->activityId, $captureddata->commentId);
				$return["sta"] = "SUCCESS";
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			
			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "activity_id : ".$captureddata->activityId, "");
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_create_user":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->facebookid) && isset($captureddata->deviceid) && isset($captureddata->referalid) && isset($captureddata->email) && isset($captureddata->fullname) && isset($captureddata->username) && isset($captureddata->gender) && isset($captureddata->birthday) && isset($captureddata->sid) && isset($captureddata->password) && isset($captureddata->photourl))
			{
				create_user($conn, $captureddata->facebookid, $captureddata->deviceid,$captureddata->referalid, $captureddata->email,$captureddata->fullname, $captureddata->username, $captureddata->gender, $captureddata->birthday,$captureddata->sessionid, $captureddata->password, $captureddata->photourl);

				// TODO : insert this user id, fb id and name to current session id
				$jsonUser = $conn->doQuery("Select `u_id` from  n_user where u_email='".$captureddata->email."';", null,'json');
				$objUser = json_decode($jsonUser);

				$currentUserId = "";
				if(!empty($objUser->data->query_result))
				{
					$currentUserId = $objUser->data->query_result[0]->u_id;
				}
				
				$json=$conn->doQuery("update n_session set s_user_id='".$currentUserId."', s_fb_id='".$captureddata->facebookid."', s_name='".$captureddata->fullname."' where s_id=".$captureddata->sid, null, 'json');
				//echo $json;
			}
			else
			{
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			
			// TODO : This has to return user points, username, gender, avatar_url, birthday, phone, subscription_end, total_referred_since_last_login
			
			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", "");
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_load_user":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data));
			if( isset($captureddata->sid) &&isset($captureddata->email) && isset($captureddata->password) && isset($captureddata->facebookid) )
			{
				load_user($conn, $captureddata->email, $captureddata->password,$captureddata->facebookid, $captureddata->sid);

				// TODO : insert this user id, fb id and name to current session id
				$jsonUser = $conn->doQuery("Select `u_id`, `u_fbid`, `u_fullname` from  n_user where u_email='".$captureddata->email."' or u_fbid='".$captureddata->facebookid."';", null,'json');
				$objUser = json_decode($jsonUser);

				$currentUserId = "";
				$currentFacebookId = "";
				$currentUserName = "";
				if(!empty($objUser->data->query_result))
				{
					$currentUserId = $objUser->data->query_result[0]->u_id;
					$currentFacebookId = $objUser->data->query_result[0]->u_fbid;
					$currentUserName = $objUser->data->query_result[0]->u_fullname;
				}
				
				$json=$conn->doQuery("update n_session set s_user_id='".$currentUserId."', s_fb_id='".$currentFacebookId."', s_name='".$currentUserName."' where s_id=".$captureddata->sid, null, 'json');
				//echo $json;
			}
			else
			{
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}

			// TODO : This has to return user points, username, gender, avatar_url, birthday, phone, subscription_end, total_referred_since_last_login
			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", "");
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_link_facebook":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data));
			if( isset($captureddata->sid) &&isset($captureddata->uname) && isset($captureddata->password) && isset($captureddata->facebookid) )
			{
				link_facebook($conn, $captureddata->uname, $captureddata->password,$captureddata->facebookid);
				$return["sta"] = "SUCCESS";
			}
			else
			{
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}

			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", "");
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_purchase_video":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data));
			if( isset($captureddata->sid) &&isset($captureddata->userid) && isset($captureddata->videoid) && isset($captureddata->usetoken) )
			{
				purchase_video($conn, $captureddata->sid, $captureddata->userid, $captureddata->videoid, $captureddata->usetoken);
			}
			else
			{
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			
			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", $captureddata->videoid);
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_add_subscription":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data));
			if( isset($captureddata->sid) &&isset($captureddata->userid) && isset($captureddata->type))
			{
				//$page=10*$captureddata->pg;
				add_subscription($conn, $captureddata->sid, $captureddata->userid, $captureddata->type);
				$return["sta"] = "SUCCESS";
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			
			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", "");
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_set_favorite":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data));
			if( isset($captureddata->sid) && isset($captureddata->userid) && isset($captureddata->videoid) && isset($captureddata->activityid))
			{
				$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", $captureddata->videoid);
				insert_action($conn, $param);
				$return["sta"] = "SUCCESS";
			}
			else
			{
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			echo json_encode($return);
			break;
		}
		
		case "cli_unset_favorite":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data));
			if( isset($captureddata->sid) &&isset($captureddata->userid) && isset($captureddata->videoid) && isset($captureddata->acitivityid))
			{
				$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", $captureddata->videoid);
				insert_action($conn, $param);
				$return["sta"] = "SUCCESS";
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			echo json_encode($return);
			break;
		}
		
		case "cli_get_video_list":
		case "cli_get_video_list_old":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			//echo $data;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->lim) && isset($captureddata->fid) && isset($captureddata->cat))
			{
				$rsSession=get_session($conn, $captureddata->sid);
				$country_name=$rsSession[0]->s_origin_country_name;
				get_video_list($conn, $captureddata->lim, $captureddata->fid, $captureddata->cat,$country_name);
			}
			else
			{
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", "");
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_add_tokens":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			//echo $data;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->uid) && isset($captureddata->type) )
			{
				add_tokens($conn, $captureddata->uid, $captureddata->type);
			}
			else
			{
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", "");
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_get_ppv":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			//echo $data;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->uid))
			{
				
				get_ppv($conn, $captureddata->uid);
			}
			else
			{
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", "");
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_add_playlist":
		case "cli_remove_playlist":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			//echo $data;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->uid) && isset($captureddata->videoid))
			{
				
				add_playlist($conn, $captureddata->uid, $captureddata->videoid, $captureddata->sid);
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", $captureddata->videoid);
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_get_playlist":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			//echo $data;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->uid))
			{
				
				get_playlist($conn, $captureddata->uid);
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", "");
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_add_notification":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			//echo $data;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->uid) && isset($captureddata->type) && isset($captureddata->videoid) && isset($captureddata->commentid))
			{
				
				add_notification($conn, $captureddata->uid, $captureddata->type, $captureddata->videoid, $captureddata->commentid );
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", "");
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_get_notification":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			//echo $data;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->uid))
			{
				
				add_notification($conn, $captureddata->uid, $captureddata->type, $captureddata->videoid, $captureddata->commentid );
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", "");
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_add_tokens_and_purchase_video":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			//echo $data;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->uid) && isset($captureddata->type) && isset($captureddata->videoid) )
			{
				add_tokens($conn, $captureddata->uid, $captureddata->type);

				purchase_video($conn, $captureddata->sid, $captureddata->uid, $captureddata->videoid, 1);
			}
			else
			{
				$return["sta"] = "FAIL";
				$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			
			$param=setup_action_param($conn, $captureddata->sid, (string)$jsonaction, "", "");
			insert_action($conn, $param);
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
