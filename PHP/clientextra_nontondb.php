<?php

function GetClientExtraAPI($jsonaction, $data)
{
	global $return;

	switch( (string)$jsonaction )
	{	
		// TODO : where is get_similar_videos
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
			$country_name=GetCountryNameFromIp($captureddata->ip);
			$rsCountry= getCountryByName($conn, $country_name);
			//echo $rsCountry;
			$param=array( 's_user_id'=>$captureddata->uid,
					 's_device_id'=>$captureddata->did,
					 's_fb_id'=>$captureddata->fid,
					 's_name'=>'',
					 's_origin_ip'=>$captureddata->ip,
					 's_origin_country_id'=>$rsCountry[0]->cy_id,
					 's_origin_country_name'=>$rsCountry[0]->cy_name,
					 's_client_id'=>'',
					 's_client_name'=>''
					);
			$rsSession=insert_session($conn, $param);
			//echo json_encode($rzSession);
			if(isset($captureddata->ip) && isset($captureddata->aps) && isset($captureddata->apv) && isset($captureddata->uid) && isset($captureddata->fid) && isset($captureddata->did) )
			{
				//echo $captureddata->ip;
				
				get_initial_content($conn, $captureddata->aps, $country_name, $rsSession->data->query_id);
				$param=array(
						"an_session_id"=>$rsSession->data->query_id,
						"an_name"=>(string)$jsonaction,
						"an_note"=>"",
						"an_is_valid"=>0
						);
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
		
		case "cli_get_video_list":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			//echo $data;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->pg) && isset($captureddata->cat))
			{
				//echo $captureddata->ip;
				$rsSession=get_session($conn, $captureddata->sid);
				$country_name=$rsSession[0]->s_origin_country_name;
				$lim=5;
				$page=$lim*$captureddata->pg;
				get_video_list($conn, $page, $captureddata->fid, $captureddata->cat,$country_name);
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=array(
					'sessionid'=>$captureddata->sid,
					'an_name'=>'cli_get_video_list',
					'an_note'=>'',
					'an_is_valid'=>0);
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}

		case "cli_search_database":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			//echo $data;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->kwd) && isset($captureddata->pg))
			{
				$rsSession=get_session($conn, $captureddata->sid);
				$country_name=$rsSession[0]->s_origin_country_name;
				//echo $country_name;
				$search_limit = 10;
				$page=$search_limit*$captureddata->pg;
				search_franchise($conn, $captureddata->kwd,$country_name, $page);
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=array(
					'sessionid'=>$captureddata->sid,
					'an_name'=>'cli_search_database',
					'an_note'=>'',
					'an_is_valid'=>0);
			insert_action($conn, $param);
			echo json_encode($return);
			break;
		}
		
		case "cli_get_autocomplete":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			//echo $data;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->kwd))
			{
				$rsSession=get_session($conn, $captureddata->sid);
//echo json_encode($rsSession);
				$country_name=$rsSession[0]->s_origin_country_name;
//echo $country_name;
				get_autocomplete($conn, $captureddata->kwd,$country_name);
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=array(
						"an_session_id"=>$captureddata->sid,
						"an_name"=>(string)$jsonaction,
						"an_note"=>"",
						"an_is_valid"=>0
						);
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
			if(isset($captureddata->sid) && isset($captureddata->activityId) && isset($captureddata->videoId) && isset($captureddata->duration) && isset($captureddata->nShares) && isset($captureddata->resolution) && isset($captureddata->timeEnd))
			{
				$favorite=check_favorite($conn, $captureddata->sid, $captureddata->videoId);
				$activity=insert_activity($conn, $captureddata->sid,$captureddata->videoId );
				if($captureddata->activityId!="")
					update_activity($conn, $captureddata->activityId, $captureddata->duration, $captureddata->nShares, $captureddata->resolution, $captureddata->timeEnd);
				//echo $activity->data->query_id;
				$data=get_video_detail($conn,  $captureddata->videoId, $favorite, $activity->data->query_id);
				
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=array(
						"an_session_id"=>$captureddata->sid,
						"an_name"=>(string)$jsonaction,
						"an_note"=>"",
						"an_is_valid"=>0
						);
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
			$param=array(
						"an_session_id"=>$captureddata->sid,
						"an_name"=>(string)$jsonaction,
						"an_note"=>"",
						"an_is_valid"=>0
						);
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
				$return["sta"] = "SUCCESS";
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=array(
						"an_session_id"=>$captureddata->sid,
						"an_name"=>(string)$jsonaction,
						"an_note"=>"",
						"an_is_valid"=>0
						);
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
				// TODO : not implemented yet
				like_comment($conn, $captureddata->sid, $captureddata->userid, $captureddata->activityId, $captureddata->commentId);
				$return["sta"] = "SUCCESS";
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=array(
						"an_session_id"=>$captureddata->sid,
						"an_name"=>(string)$jsonaction,
						"an_note"=>"",
						"an_is_valid"=>0
						);
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
				//$page=10*$captureddata->pg;
				create_user($conn, $captureddata->facebookid, $captureddata->deviceid,$captureddata->referalid, $captureddata->email,$captureddata->fullname, $captureddata->username, $captureddata->gender, $captureddata->birthday,$captureddata->sessionid, $captureddata->password, $captureddata->photourl);
				//$return["sta"] = "SUCCESS";
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=array(
						"an_session_id"=>$captureddata->sid,
						"an_name"=>(string)$jsonaction,
						"an_note"=>"",
						"an_is_valid"=>0
						);
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
				//$page=10*$captureddata->pg;
				load_user($conn, $captureddata->uname, $captureddata->password,$captureddata->facebookid);
				$return["sta"] = "SUCCESS";
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=array(
						"an_session_id"=>$captureddata->sid,
						"an_name"=>(string)$jsonaction,
						"an_note"=>"",
						"an_is_valid"=>0
						);
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
				//$page=10*$captureddata->pg;
				link_facebook($conn, $captureddata->uname, $captureddata->password,$captureddata->facebookid);
				$return["sta"] = "SUCCESS";
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=array(
						"an_session_id"=>$captureddata->sid,
						"an_name"=>(string)$jsonaction,
						"an_note"=>"",
						"an_is_valid"=>0
						);
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
				//$page=10*$captureddata->pg;
				purchase_video($conn, $captureddata->sid, $captureddata->userid, $captureddata->videoid, $captureddata->usetoken);
				//$return["sta"] = "SUCCESS";
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=array(
						"an_session_id"=>$captureddata->sid,
						"an_name"=>(string)$jsonaction,
						"an_note"=>"",
						"an_is_valid"=>0
						);
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
			$param=array(
						"an_session_id"=>$captureddata->sid,
						"an_name"=>(string)$jsonaction,
						"an_note"=>"",
						"an_is_valid"=>0
						);
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
				//$page=10*$captureddata->pg;
				//set_action($conn, $captureddata->sid, $captureddata->userid, $captureddata->type);
				$param=array(
						"an_session_id"=>$captureddata->sid,
						"an_name"=>(string)$jsonaction,
						"an_note"=>$captureddata->userid.'-'.$captureddata->videoid,
						"an_is_valid"=>0
						);
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
				//$page=10*$captureddata->pg;
				//set_action($conn, $captureddata->sid, $captureddata->userid, $captureddata->type);
				$param=array(
						"an_session_id"=>$captureddata->sid,
						"an_name"=>(string)$jsonaction,
						"an_note"=>$captureddata->userid.'-'.$captureddata->videoid,
						"an_is_valid"=>0
						);
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
		
		case "cli_get_video_list_old":
		{
			$conn = new database();
			$return["evn"] = (string)$jsonaction;
			//echo $data;
			$captureddata = json_decode(decrypt($data));
			if(isset($captureddata->sid) && isset($captureddata->lim) && isset($captureddata->fid) && isset($captureddata->cat))
			{
				//echo $captureddata->ip;
				$rsSession=get_session($conn, $captureddata->sid);
				$country_name=$rsSession[0]->s_origin_country_name;
				get_video_list_old($conn, $captureddata->lim, $captureddata->fid, $captureddata->cat,$country_name);
			}
			else
			{
					$return["sta"] = "FAIL";
					$return["ret"]["msg"] = "INVALID EVENT FORMAT";
			}
			$param=array(
					'sessionid'=>$captureddata->sid,
					'an_name'=>'cli_get_video_list',
					'an_note'=>'',
					'an_is_valid'=>0);
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
