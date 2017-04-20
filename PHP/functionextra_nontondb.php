<?php
function get_initial_content($conn, $app_signature, $country_name, $sessionid)
{
	global $return;
	if(strtoupper($country_name)=='ALL')
		$country_name="";
	$the_search_result = array();

	$json = $conn->doQuery("select `cl_id`,`cl_name`,`cl_latest_version`,`cl_force_update`,`cl_rules_paid`,`cl_is_active`,`cl_creator_id`, `cl_date_created` from `n_client` where `cl_name`='".$app_signature."' LIMIT 1;", null,'json');
					
	$objClient = json_decode($json);
	
	if(!empty($objClient->data->query_result))
	{
		if((string)$objClient->data->query_result[0]->cl_force_update==1)
		{
			$return["sta"] = "FAIL";
			$return["ret"]["msg"] = "APP HAS TO BE UPDATED";
		}
		else
		{
			$json = $conn->doQuery("SELECT t_id from n_type;",
										null,'json');
			$objType = json_decode($json);
			$data=array();
			foreach($objType->data->query_result as $row)
			{
				$content=get_content($conn, $row->t_id);
				if($content!=null)
					$data[]=$content;
			}
			$response=array("sessionid"=>$sessionid,
						"content"=>$data,
					);
			$return["sta"] = "SUCCESS";
			$return["ret"]["dat"] = encrypt(json_encode($response));
		}
	}
	else
	{
		$return["sta"] = "FAIL";
		$return["ret"]["msg"] = "APP SIGNATURE NO LONGER EXISTS";
	}
}

function get_content($conn, $typeid)
{
	$json = $conn->doQuery("SELECT count(*) total FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$typeid.";", 
										null,'json');
	$objCount = json_decode($json);
	$count=$objCount->data->query_result[0]->total;
	if($count>4 && $count<7)
		$limit=4;
	else
		$limit=7;
	$json = $conn->doQuery("SELECT `v_franchise_id`,`v_id`, f_name `v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_url_poster`, `v_url_poster_landscape`, `t_name`, `t_id`, `t_url_poster_landscape`, f_url_poster_landscape FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$typeid." group by v_franchise_id  ORDER BY v_last_updated DESC LIMIT ".$limit.";", 
										null,'json');
	$objVideo = json_decode($json);
	return $objVideo->data->query_result;
}

function search_franchise($conn, $kwd, $country_name, $start)
{
	global $return;
	if(strtoupper($country_name)=='ALL')
		$country_name="";
	$limit_per_search = 10;
	$json = $conn->doQuery("SELECT COUNT(*),f_country_access,, f_id  FROM (SELECT f_id, f_country_access from n_franchise f 
							LEFT JOIN n_genre as g1 on f_genre_id_1=g1.g_id 
							LEFT JOIN n_genre as g2 on f_genre_id_2=g2.g_id 
							LEFT JOIN n_genre as g3 on f_genre_id_3=g3.g_id 
							LEFT JOIN n_genre as g4 on f_genre_id_4=g4.g_id 
							LEFT JOIN n_genre as g5 on f_genre_id_5=g5.g_id) tino
								where upper(f_country_access) like '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%' group by f_id;", 
										null,'json');
	$count = json_decode($json)->data->query_result[0]->total;
	$json = $conn->doQuery("SELECT * FROM (SELECT distinct(f_id), v_id,	v_url_youtube_id, v_url_cdn, f_url_poster, f_url_poster_landscape, f_synopsis, f_name, f_company, f_country_access, ifnull(g1.g_id, '') g_id1, ifnull(g1.g_name, '') g_name1, ifnull(g2.g_id, '') g_id2,  ifnull(g2.g_name, '') g_name2, ifnull(g3.g_id,'') g_id3, ifnull(g3.g_name,'') g_name3, ifnull(g4.g_id,'') g_id4,   ifnull(g4.g_name,'') g_name4,ifnull(g5.g_id, '') g_id5,  ifnull(g5.g_name, '') g_name5
 from n_franchise f 
							LEFT JOIN n_video as v on f_id=v.v_franchise_id
							LEFT JOIN n_genre as g1 on f_genre_id_1=g1.g_id 
							LEFT JOIN n_genre as g2 on f_genre_id_2=g2.g_id 
							LEFT JOIN n_genre as g3 on f_genre_id_3=g3.g_id 
							LEFT JOIN n_genre as g4 on f_genre_id_4=g4.g_id 
							LEFT JOIN n_genre as g5 on f_genre_id_5=g5.g_id) tino
								where upper(f_name) like '%".strtoupper($kwd)."%' and (upper(f_country_access) like '%".strtoupper($country_name)."%' OR UPPER(f_country_access ) LIKE '%ALL%') group by f_id
							LIMIT ".$start.",".$limit_per_search.";", 
										null,'json');
	if(($start+1)<$count/$limit_per_search)
	{
		$nextPage=true;
	}
	else
	{
		$nextPage=false;
	}
	$objFranchise = json_decode($json);
	$return["sta"] = "SUCCESS";
	$return["ret"]["dat"] = encrypt(json_encode(array("nextPage"=>$nextPage,$objFranchise->data->query_result)));;
	
}

function get_autocomplete($conn, $kwd, $country_name)
{
	global $return;
	if(strtoupper($country_name)=='ALL' || $country_name==null)
		$country_name="";
	$json = $conn->doQuery("SELECT `f_name`,`v_franchise_id` FROM `n_video` v, `n_franchise` f WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND upper(f_name) like '%".strtoupper($kwd)."%' GROUP BY F_NAME ORDER BY v_last_updated DESC LIMIT 10;", 
										null,'json');
	//echo json_encode($json);	
	$objFranchise = json_decode($json);

	$return["sta"] = "SUCCESS";
	$return["ret"]["dat"] = encrypt(json_encode($objFranchise->data->query_result));;
	
}

function setup_action_param($conn, $session_id, $event_name, $extra_note, $current_video_id)
{
	$rsSession=get_session($conn, $session_id);

	return array(
					'sessionid'=>$session_id,
					'an_name'=>$event_name,
					'an_note'=>$extra_note,
					'an_is_valid'=>1,
					'an_user_id'=>$rsSession[0]->s_user_id,
					'an_video_id'=>$current_video_id
					);
					
	/*$conn->doQuery("insert into n_action (an_session_id, an_name, an_note, an_is_valid, an_user_id, an_video_id) values
					'".$session_id."',
					'".$event_name."',
					'".$extra_note."'1, 
					",
					null,"json");*/
	
}

function insert_action($conn, $param)
{
	if(isset($param['an_video_id']) && isset($param['an_user_id']))
		$json = $conn->doQuery("insert into n_action (`an_session_id`, `an_name`, `an_note`, `an_is_valid`, `an_video_id`, `an_user_id`) values ('".$param['sessionid']."','".$param['an_name']."','".$param['an_note']."',".$param['an_is_valid'].", '".$param['an_video_id']."', '".$param['an_user_id']."');", 
										null,'json');
	else
		$json = $conn->doQuery("insert into n_action (`an_session_id`, `an_name`, `an_note`, `an_is_valid`) values ('".$param['sessionid']."','".$param['an_name']."','".$param['an_note']."',".$param['an_is_valid'].");", 
										null,'json');
	//echo json_decode($json);
	//echo $json;
}

function insert_session($conn, $param)
{
	global $return;
	$json = $conn->doQuery("insert into n_session (`s_user_id`, `s_device_id`, `s_fb_id`, `s_name`, `s_origin_ip`, `s_origin_country_id`, `s_origin_country_name`, `s_client_id`, `s_client_name`) 
							values ('".$param['s_user_id']."','".$param['s_device_id']."','".$param['s_fb_id']."','".$param['s_name']."','".$param['s_origin_ip']."','".$param['s_origin_country_id']."','".$param['s_origin_country_name']."','".$param['s_client_id']."','".$param['s_client_name']."');", 
										null,'json');
	return json_decode($json);
}

function get_session($conn, $param)
{
	global $rsSession;
	$json = $conn->doQuery("Select `s_id`,`s_user_id`, `s_device_id`, `s_fb_id`, `s_name`, `s_origin_ip`, `s_origin_country_id`, `s_origin_country_name`, `s_client_id`, `s_client_name` from  n_session where s_id=".$param.";", null,'json');
	$objSession = json_decode($json);
	
	return $objSession->data->query_result;
}

function getCountryByName($conn, $param)
{
	global $rsCountry;
	$json = $conn->doQuery("select `cy_id`, `cy_name`, `cy_is_active`, `cy_creator` from  n_country where upper(cy_name) like '%".$param."%';", 
					null,'json');
	$objCountry = json_decode($json);
	
	return $objCountry->data->query_result;
}

function get_video_detail($conn,  $videoId=null, $favorite, $activityId, $userid)
{
	global $return;
	//echo $activityId;
	if($videoId==null || $videoId==-1)
	{
		
	}
	else
	{
			$json=$conn->doQuery("select v_id, v_franchise_id,t_name, v_title, v_price, f_genre_id_1, f_genre_id_2,f_genre_id_3,f_genre_id_4,f_genre_id_5,f_company, v_franchise_id, v_synopsis, v_url_youtube_id,v_season, v_episode, v_year_production, v_director, v_casts, v_price from n_video v, n_franchise f, n_type t where t.t_id =f.f_type_id and v_id=".$videoId." and v.v_franchise_id=f.f_id",
								null,'json');
			$json=$conn->doQuery("select v_id, v_franchise_id,t_name, v_title, v_price, f_genre_id_1, f_genre_id_2,f_genre_id_3,f_genre_id_4,f_genre_id_5,f_company, v_franchise_id, v_synopsis, v_url_youtube_id,v_season, v_episode, v_year_production, v_director, v_casts, v_price, f_type_id from n_video v, n_franchise f, n_type t where t.t_id =f.f_type_id and v_id=".$videoId." and v.v_franchise_id=f.f_id", null,'json');
			$objVideoDetail=json_decode($json);
			$json=$conn->doQuery("select v_id, v_title, `v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`, v_franchise_id, v_url_poster, v_url_poster_landscape,v_url_cdn,v_season, v_episode, v_year_production, v_director, v_casts, v_price from n_video where v_franchise_id='".$objVideoDetail->data->query_result[0]->v_franchise_id."' and v_season='".$objVideoDetail->data->query_result[0]->v_season."' and v_season!=-1",
								null,'json');
			$video_type = $objVideoDetail->data->query_result[0]->f_type_id;

			$json=$conn->doQuery("select v_id, v_title, `v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`, v_franchise_id, v_url_poster, v_url_poster_landscape,v_url_cdn,v_season, v_episode, v_year_production, v_director, v_casts, v_price from n_video where v_franchise_id='".$objVideoDetail->data->query_result[0]->v_franchise_id."' and v_season='".$objVideoDetail->data->query_result[0]->v_season."' and v_season!=-1", null,'json');
			$objEpisode=json_decode($json);
			$json=$conn->doQuery("select distinct(v_id) v_id, v_franchise_id, v_title, v_url_youtube_id,v_url_cdn, v_prioritize_youtube, v_url_poster, v_url_poster_landscape from n_video v, n_franchise f where v.v_franchise_id=f.f_id and (f_genre_id_1 in (".$objVideoDetail->data->query_result[0]->f_genre_id_1.",".$objVideoDetail->data->query_result[0]->f_genre_id_2.", ".$objVideoDetail->data->query_result[0]->f_genre_id_3.",".$objVideoDetail->data->query_result[0]->f_genre_id_4.",".$objVideoDetail->data->query_result[0]->f_genre_id_5.") 

			$json=$conn->doQuery("select distinct(v_id) v_id, v_franchise_id, v_title, v_url_youtube_id,v_url_cdn, v_prioritize_youtube, v_url_poster, v_url_poster_landscape from n_video v, n_franchise f where v.v_franchise_id=f.f_id and f.f_type_id =".$video_type." and (f_genre_id_1 in (".$objVideoDetail->data->query_result[0]->f_genre_id_1.",".$objVideoDetail->data->query_result[0]->f_genre_id_2.", ".$objVideoDetail->data->query_result[0]->f_genre_id_3.",".$objVideoDetail->data->query_result[0]->f_genre_id_4.",".$objVideoDetail->data->query_result[0]->f_genre_id_5.") 
									or f_genre_id_2 in (".$objVideoDetail->data->query_result[0]->f_genre_id_1.",".$objVideoDetail->data->query_result[0]->f_genre_id_2.", ".$objVideoDetail->data->query_result[0]->f_genre_id_3.",".$objVideoDetail->data->query_result[0]->f_genre_id_4.",".$objVideoDetail->data->query_result[0]->f_genre_id_5.") 
									or f_genre_id_3 in (".$objVideoDetail->data->query_result[0]->f_genre_id_1.",".$objVideoDetail->data->query_result[0]->f_genre_id_2.", ".$objVideoDetail->data->query_result[0]->f_genre_id_3.",".$objVideoDetail->data->query_result[0]->f_genre_id_4.",".$objVideoDetail->data->query_result[0]->f_genre_id_5.") 
									or f_genre_id_4 in (".$objVideoDetail->data->query_result[0]->f_genre_id_1.",".$objVideoDetail->data->query_result[0]->f_genre_id_2.", ".$objVideoDetail->data->query_result[0]->f_genre_id_3.",".$objVideoDetail->data->query_result[0]->f_genre_id_4.",".$objVideoDetail->data->query_result[0]->f_genre_id_5.") 
									or f_genre_id_5 in (".$objVideoDetail->data->query_result[0]->f_genre_id_1.",".$objVideoDetail->data->query_result[0]->f_genre_id_2.", ".$objVideoDetail->data->query_result[0]->f_genre_id_3.",".$objVideoDetail->data->query_result[0]->f_genre_id_4.",".$objVideoDetail->data->query_result[0]->f_genre_id_5.") )
									and v_id !=".$objVideoDetail->data->query_result[0]->v_id." and v_franchise_id!='".$objVideoDetail->data->query_result[0]->v_franchise_id."' group by v_franchise_id;",
								null,'json');
			//echo $json;			
									and v_id !=".$objVideoDetail->data->query_result[0]->v_id." and v_franchise_id!='".$objVideoDetail->data->query_result[0]->v_franchise_id."' group by v_franchise_id;", null,'json');
			
			$objSimiliarVideo=json_decode($json);
			//echo $objSimiliarVideo;
			
			$json=$conn->doQuery("select count(*) total from n_activity where av_video_id='".$videoId."'",
								null,'json');
			$objCount=json_decode($json);
			//$objEpisode=json_decode($json);
			
			$json=$conn->doQuery("SELECT av_last_second_watched from n_activity ac, n_session s where ac.av_video_id='".$videoId."' and ac.av_session_id=s.s_id and s.s_user_id='".$userid."' order by  av_id desc limit 1", null, 'json');
			$objLastSecond=json_decode($json);
			
			//check eligible
			if((int)$objVideoDetail->data->query_result[0]->v_price>0)
			{
				$json=$conn->doQuery("select u_subscription_end  from n_user where u_id=".$userid,
									null,'json');
				$subsDate=strtotime(json_decode($json)->data->query_result[0]->u_subscription_end);
				//echo $json;
					if($subsDate<time())
					{
						//echo $subsDate;
						$json=$conn->doQuery("select ppv_date_ended  from n_pay_per_view where ppv_user_id='".$userid."' and ppv_video_id='".$videoId."'",
									null,'json');
						$payDate=strtotime(json_decode($json)->data->query_result[0]->ppv_date_ended);
						//echo $payDate;
						if($payDate<time())
						{
							
							$eligible=false;
						}	
						else
						{
							//echo 'true';
							$eligible=true;
						}
							
					}
					else
						$eligible=true;
			}
			else
			{
				$eligible=true;
				
			}
			if($objVideoDetail->data->query_result[0]->v_url_cdn==null)
				$v_url_cdn='';
			else
				$v_url_cdn=$objVideoDetail->data->query_result[0]->v_url_cdn;
				
			$data=array(
			  "v_id" => $objVideoDetail->data->query_result[0]->v_id,
			  "category"=>  $objVideoDetail->data->query_result[0]->t_name,
			  "v_franchise_id"=> $objVideoDetail->data->query_result[0]->v_franchise_id,
			  "v_title"=>$objVideoDetail->data->query_result[0]->v_title,
			  "f_company"=>$objVideoDetail->data->query_result[0]->f_company,
			  "v_synopsis"=> $objVideoDetail->data->query_result[0]->v_synopsis,
			  "v_url_youtube_id"=> $objVideoDetail->data->query_result[0]->v_url_youtube_id,
			  "v_season"=> $objVideoDetail->data->query_result[0]->v_season,
			  "v_episode"=> $objVideoDetail->data->query_result[0]->v_episode,
			  "v_year_production"=> $objVideoDetail->data->query_result[0]->v_year_production,
			  "v_director"=> $objVideoDetail->data->query_result[0]->v_director,
			  "v_casts"=> $objVideoDetail->data->query_result[0]->v_casts,
			  "v_price"=> $objVideoDetail->data->query_result[0]->v_price,
			  "v_url_cdn"=> $v_url_cdn,
			  "views"=>$objCount->data->query_result[0]->total,
			  "favorite"=>$favorite,
			  "eligible"=>$eligible,
			  "av_last_second_watched"=>(int)$objLastSecond->data->query_result[0]->av_last_second_watched,
			  "similiar_video"=>$objSimiliarVideo->data->query_result,
			  "episode"=> $objEpisode->data->query_result
			  );
	}
			//array_push($objVideoDetail->data->query_result, $objEpisode->data->query_result);
			$return["sta"] = "SUCCESS";
			$return["ret"]["dat"] = encrypt(json_encode(array("activityId"=>$activityId,"video"=>$data)));;
			return $data;
	
}

function insert_activity($conn, $sessionid, $videoId)
{
	$date=date('Y-m-d H:i:s');

	$jsonVideo = $conn->doQuery("Select `v_title` from  n_video where v_id=".$videoId.";", null,'json');
	$objVideo = json_decode($jsonVideo);
	$videoName = "";

	if(!empty($objVideo->data->query_result))
	{
		$videoName = $objVideo->data->query_result[0]->v_title;
	}

	$json=$conn->doQuery("insert into n_activity (av_session_id, av_video_id, av_time_start, av_video_name) values ('".$sessionid."','".$videoId."','".$date."','".$videoName."');", null, 'json');
	
	return json_decode($json);					
}

function update_activity($conn, $av_id, $duration, $lastSecond, $share, $resolution, $timeEnd)
{
	
	$json=$conn->doQuery("update n_activity set av_duration_watched=".$duration.", av_last_second_watched=".$lastSecond.", av_share=".$share.", av_resolution='".$resolution."', av_time_end='".$timeEnd."' where av_id=".$av_id, null, 'json');
}
/*
function get_comments($conn, $videoId, $start)
{
	global $return;
	$json = $conn->doQuery("SELECT `cm_id`, `u_id`, `u_avatar_url`,`u_username`, `cm_replied_to_id`, `cm_video_id`, `cm_video_name`, `cm_session_id`, `cm_activity_id`, `cm_user_id`, `cm_title`, `cm_body`, `cm_likes`, `cm_dislikes`, `cm_is_active`, `cm_date_created`,(select count(*) from n_comment a where a.cm_replied_to_id=c.cm_id) cm_total   FROM `n_comment` c, `n_user` u WHERE c.`cm_video_id` = ".$videoId." and u.u_id=c.`cm_user_id` and c.cm_replied_to_id =''
							order by cm_date_created desc LIMIT ".$start.",5; ", 
										null,'json');
										//echo $json;
	$objComment = json_decode($json);
	$data=array();
	foreach($objComment->data->query_result as $row)
	{
		$json = $conn->doQuery("SELECT `cm_id`, `u_id`, `u_avatar_url`,`u_username`,  `cm_video_id`, `cm_video_name`, `cm_session_id`, `cm_activity_id`, `cm_user_id`, `cm_title`, `cm_body`, `cm_likes`, `cm_dislikes`, `cm_is_active`, `cm_date_created`, (select count(*) from n_comment a where a.cm_replied_to_id=c.cm_id) cm_total  FROM `n_comment` c, `n_user` u WHERE c.`cm_video_id` = ".$videoId." and u.u_id=c.`cm_user_id` and `cm_replied_to_id`='".$row->cm_id."'
							order by cm_date_created", 
										null,'json');
												//echo $json;
		$objComment = json_decode($json);
		$data[]=array(
					"parent"=>$row,
					"child"=>$objComment->data->query_result
					);
	}
	if(($start+1)<$count/10)
	{
		$nextPage=true;
	}
	else
	{
		$nextPage=false;
	}
	
	$return["sta"] = "SUCCESS";
	$return["ret"]["dat"] = encrypt(json_encode(array("nextPage"=>$nextPage,"data"=>$data)));;
}
/*
function get_comments_replied($conn, $cm_id)
{
	global $return;
	$json = $conn->doQuery("SELECT `cm_id`, `u_id`, `u_avatar_url`,`u_username`,  `cm_video_id`, `cm_video_name`, `cm_session_id`, `cm_activity_id`, `cm_user_id`, `cm_title`, `cm_body`, `cm_likes`, `cm_dislikes`, `cm_is_active`, `cm_date_created`, (select count(*) from n_comment a where a.cm_replied_to_id=c.cm_id) cm_total  FROM `n_comment` c, `n_user` u WHERE u.u_id=c.`cm_user_id` and `cm_replied_to_id`='".$cm_id."'
							order by cm_date_created", 
										null,'json');
	$objComment = json_decode($json);
	$return["sta"] = "SUCCESS";
	$return["ret"]["dat"] = encrypt(json_encode($objComment->data->query_result));
}
*/
function get_comments($conn, $videoId, $start)
{
	global $return;
	$json = $conn->doQuery("SELECT `cm_id`, `u_id`, `u_avatar_url`,`u_username`, `cm_replied_to_id`, `cm_video_id`, `cm_video_name`, `cm_session_id`, `cm_activity_id`, `cm_user_id`, `cm_title`, `cm_body`, `cm_likes`, `cm_dislikes`, `cm_is_active`, `cm_date_created` FROM `n_comment` c, `n_user` u WHERE c.`cm_video_id` = ".$videoId." and u.u_id=c.`cm_user_id`
							order by cm_date_created desc LIMIT ".$start.",10; ", 
										null,'json');
	if(($start+1)<$count/10)
	{
		$nextPage=true;
	}
	else
	{
		$nextPage=false;
	}
	$objComment = json_decode($json);
	$return["sta"] = "SUCCESS";
	$return["ret"]["dat"] = encrypt(json_encode(array("nextPage"=>$nextPage,$objComment->data->query_result)));;
}

function insert_comment($conn, $sid, $commentId, $userId, $videoId, $activityId, $title, $body)
{
	global $return;
	$json=$conn->doQuery("insert into n_comment (cm_session_id, Cm_replied_to_id , Cm_user_id , Cm_video_id , Cm_activity_id, Cm_title, Cm_body ) 
							values ('".$sid."','".$commentId."','".$userId."','".$videoId."','".$activityId."','".$title."', '".$body."');",
						null, 'json');
	
	if(json_decode($json)->data->result=='ok')
		$return["sta"] = "SUCCESS";
	else
		$return["sta"] = "FAILED";
}

function create_user($conn, $facebookid=null, $deviceid=null,$referalid=null, $email=null,$fullname=null, $username=null, $gender=null, $birthday=null,$sessionid=null, $password=null, $photourl=null)
{
	global $return;
	$json = $conn->doQuery("select * from n_user where u_fbid='".$facebookid."' or u_email='".$email."';", 
										null,'json');
	$user=json_decode($json);
	//echo json_encode($user->data->query_result[0]);
	if(isset($user->data->query_result[0]->u_id))
	{
		$return["sta"] = "USER ALREADY REGISTERED";
		$json = $conn->doQuery("SELECT `u_id`, `u_points` points, u_fullname, u_avatar_url, u_subscription_end FROM `n_user` u WHERE `u_id` = ".$user->data->query_result[0]->u_id.";", 
										null,'json');
		$objUser = json_decode($json);
		$data=$objUser->data->query_result[0];
	
		$return["ret"]["dat"] = encrypt(json_encode($data));
		//echo "test";
	}
	else
	{
		if($birthday=="")
			$birthday='1900-01-01';

		$using_referral_points = 10;
		$referred_points = 10;

		$new_user_points = 0;
		$json=$conn->doQuery("SELECT u_id, u_points, u_total_referred_since_last_login FROM n_user WHERE u_username='".$referalid."';",
							null,'json');
		$check=json_decode($json);
		if($check->data->query_result[0]->u_id != null)
		{
			$totalPoints=(int)$check->data->query_result[0]->u_points + $referred_points;
			$totalReferredSinceLastLogin=(int)$check->data->query_result[0]->u_total_referred_since_last_login + 1;
			$json=$conn->doQuery("UPDATE n_user SET u_points=".$totalPoints.", u_total_referred_since_last_login=".$totalReferredSinceLastLogin." WHERE u_id=".$check->data->query_result[0]->u_id.";",
							null,'json');

			$new_user_points = $using_referral_points;
		}		

		$json = $conn->doQuery("insert into n_user (U_fbid, U_device_id, U_username, U_password, U_fullname,U_gender,U_birthday, U_points, u_avatar_url, u_email) 
								values ('".$facebookid."','".$deviceid."','".$username."','".md5($password)."','".$fullname."','".$gender."','".$birthday."', '".$new_user_points."', '".$photourl."', '".$email."');", 
											null,'json');
		$insert=json_decode($json);
		//echo $json;
		//echo $insert->data->result;
		if($insert->data->result !='ok')
		{
			
			$return["sta"] = "FAILED";
			$return["ret"]["msg"] = $insert->data->error_message;
			//break;
			
		}
		else
		{
			$json = $conn->doQuery("SELECT * FROM `n_user` u WHERE `u_id` = ".$insert->data->query_id.";", null,'json');
		$objUser = json_decode($json);
		$data=$objUser->data->query_result[0];
	
		$return["sta"] = "SUCCESS";
		$return["ret"]["dat"] = encrypt(json_encode($data));
		}
		
	}
}

function load_user($conn, $email, $password, $facebookid, $sid)
{
	global $return;
	if($email == '')
	{
		$json = $conn->doQuery("SELECT u_points, u_username, u_gender, u_avatar_url, u_birthday, u_phone, u_subscription_end, u_total_referred_since_last_login FROM `n_user` u WHERE `u_fbid` = '".$facebookid."';", 
										null,'json');
		$objUser = json_decode($json);
	}
	else
	{
		$json = $conn->doQuery("SELECT u_points, u_username, u_gender, u_avatar_url, u_birthday, u_phone, u_subscription_end, u_total_referred_since_last_login FROM `n_user` u WHERE `u_email` = '".$email."' and u_password='".$password."';", 
										null,'json');
		$objUser = json_decode($json);
	}
	if($objUser->data->query_result[0]==null || $objUser->data->query_result[0]=='')
	{
		$return["sta"] = "EMAIL/PASSWORD NOT VALID";
	}
	else
	{
		$json = $conn->doQuery("UPDATE n_session set s_user_id='".$objUser->data->query_result[0]->u_id."' where s_id=".$sid.";", null,'json');
		$return["sta"] = "SUCCESS";
		$return["ret"]["dat"] = encrypt(json_encode($objUser->data->query_result[0]));
	}		
		
	
}

function link_facebook($conn, $uname, $password, $facebookid)
{
	global $return;

	$json = $conn->doQuery("update n_user set `u_fbid`='". $facebookid."' WHERE `u_username` = '".$uname."' and u_password='".$password."';", 
										null,'json');
	$json = $conn->doQuery("SELECT `u_id`, `u_points` FROM `n_user` u WHERE `u_username` = '".$uname."';", 
										null,'json');
	$objUser = json_decode($json);
	$return["sta"] = "SUCCESS";
	$return["ret"]["dat"] = encrypt(json_encode($objUser->data->query_result[0]));
}

function purchase_video($conn, $sid, $userid, $videoid, $usetoken)
{
	
	global $return;
	$json = $conn->doQuery("SELECT `v_price` FROM `n_video` WHERE v_id='".$videoid."' ;", 
									    null,'json');
	$video=json_decode($json);
	$json = $conn->doQuery("SELECT `u_id`, `u_points` FROM `n_user` u WHERE `u_id` = '".$userid."';", 
										null,'json');
		
	$user=json_decode($json);
	if($usetoken=="1")
	{
		$point=$video->data->query_result[0]->v_price;
		$amount=0;
		
		//echo json_encode($json);
		if((int)$user->data->query_result[0]->u_points<(int)$point)
		{
			$return["sta"] = "FAIL";
			$return["ret"]["msg"] = "INSUFICIENT POINT";
			//echo "test";
			return $return;
			
			break;
		}
	}
	else
	{
		$point=0;
		$amount=$video->data->query_result[0]->v_price;
		/*if((int)$user->data->query_result[0]->u_points<(int)$amount)
		{
			$return["sta"] = "FAIL";
			$return["ret"]["msg"] = "INSUFICIENT POINT";
			//echo "test";
			return $return;
			
			break;
		}*/
	
	}
		
	/*$json = $conn->doQuery("insert into n_pay_per_view (ppv_user_id, ppv_video_id, ppv_session_id,ppv_points_used, ppv_amount_paid) 
							values ('".$userid."','".$videoid."','".$sid."',".$point.", ".$amount.");", 
										null,'json');*/
	//echo json_encode($json);
	$json = $conn->doQuery("call purchase_video('".$userid."','".$videoid."','".$sid."',".$point.",".$amount.");", 
											null,'json');
	//echo json_encode($json);
	if(json_decode($json)->data->result!='ok')
	{
		$return["sta"] = "FAILED";
		$return["ret"]["msg"] = json_decode($json)->data->error_message;
	}
	else
	{
		$json = $conn->doQuery("SELECT `u_id`, `u_points` FROM `n_user` u WHERE `u_id` = '".$userid."';", 
										null,'json');
	$user=json_decode($json);									
	$data=array(
				"u_points"=>$user->data->query_result[0]->u_points,
				"videoid"=>$videoid,
				"expirationdate"=>date('Y-m-d', strtotime(' +2 day'))
				);
	$return["sta"] = "SUCCESS";
	$return["ret"]["dat"] = encrypt(json_encode($data));
	}
	
}

function add_subscription($conn, $sid, $userid, $type)
{
	global $return;
	if($type=="week")
		$day=7;
	else
		$day=30;
	$json = $conn->doQuery("insert into n_pay_subscription (ps_user_id, ps_session_id,ps_type) 
							values ('".$userid."','".$sid."','".$type."');", 
										null,'json');
	$json = $conn->doQuery("call add_subscription('".$userid."', ".$day.");", 
										null,'json');
										
	$json = $conn->doQuery("SELECT `u_id`, `u_points`, u_subscription_end FROM `n_user` WHERE `u_id` = ".$userid.";", 
										null,'json');
	//echo json_encode($json);
	$user=json_decode($json);
	$data=array(
				"u_points"=>$user->data->query_result[0]->u_points,
				"expirationdate"=>$user->data->query_result[0]->u_subscription_end,
				);
	$return["sta"] = "SUCCESS";
	$return["ret"]["dat"] = encrypt(json_encode($data));
}

function check_favorite($conn, $userid, $videoid)
{
	$json=$conn->doQuery("select count(*) favorite from n_action  where an_user_id='".$userid."' and an_video_id='".$videoid."';",
						null,'json');
					//	echo $json;
	$objAction=json_decode($json);
	$count=(int)$objAction->data->query_result[0]->favorite;
	if($count%2==0)
	{
		return false;
	}
	else
	{
		return true;
	}
}

function like($conn, $sid, $userid, $activityId, $ommentId)
{
	$json=$conn->doQuery("insert into n_comment_action (Ca_session_id , Ca_comment_id  , Ca_activity_id  , Ca_name  , Ca_note) values ('".$sid."','".$commentId."','".$userId."','".$activityId."','".$ommentId."','".$title."', '".$body."');",
						null, 'json');
}

function dislike()
{
	
}

function get_video_list($conn, $limit, $page_number, $category, $country_name)
{
	global $return;
	if(strtoupper($country_name)=='ALL')
		$country_name='';

	$limit_entry_per_page = 5;
	$maxNumberOfRowsToBeViewed = 2;
	$isEpisodic = -1;

	$the_search_result = array();
			
	// check category, see if it needs to return landscape or poster
	$json = $conn->doQuery("SELECT `t_use_landscape_poster_in_page` FROM `n_type` t WHERE t.t_id=".$category." LIMIT 1;", null,'json');
	$use_landscape_poster = (int)(json_decode($json)->data->query_result[0]->t_use_landscape_poster_in_page);

	// get all franchise id with the category specified
	$json = $conn->doQuery("SELECT `f_id`, `f_url_poster_landscape`, `t_name` FROM  `n_franchise` f, `n_type` t WHERE t.t_id =f.f_type_id AND t.t_id=".$category." ORDER BY f_date_added ASC LIMIT ".$page_number.",".$limit_entry_per_page.";", null,'json');
	$objFranchise = json_decode($json);

	$banner=null;
	$dataTotal=null;
	//$banner=null;
	//$dataTotal=null;
	$dataCat=array();
	$lastFid=array();

	$totalItemPerRow = 3;
	if( $use_landscape_poster == 1 )
	{
		$totalItemPerRow = 2;
	}

	foreach($objFranchise->data->query_result as $row)
	{
		if( $isEpisodic == -1 )
		{
			$jsonEpisode = $conn->doQuery("SELECT count(distinct(v_episode)) total FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_season=-1 AND v_is_featured=1 AND v_is_active=1 AND v_franchise_id=".$row->f_id." AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') ORDER BY v_last_updated DESC;", null,'json');
			$objEpisode=json_decode($jsonEpisode);
			$totalEpisode=$objEpisode->data->query_result[0]->total;

			// if we do that means this is a franchise without any episodes (for examples: movies, premium)
			// it will be handled down below
			if( $totalEpisode > 0 )
			{
				// every video, based on the type should be the same
				// so if 1 video has season = -1, that means all video for this category will have it as well
				// which also means this type is not a serial type
				$isEpisodic = 0;
				break;
			}
			else
			{
				$isEpisodic = 1;
			}
		}

		if( $isEpisodic == 1 )
		{
			$json = $conn->doQuery("SELECT count(distinct(v_id)) total,`v_id`,`v_franchise_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_url_poster`, `v_url_poster_landscape` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=".$row->f_id." AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') ORDER BY v_last_updated DESC;", null,'json');
					
			$videoCount= json_decode($json)->data->query_result[0]->total;
			$objVideo = json_decode($json);
					
			$requestedTotalVideoToReturn = ($maxNumberOfRowsToBeViewed * $totalItemPerRow) + 1;

			// if the latest season is not 1, the total video returned is as defined above
			if( $videoCount >= $requestedTotalVideoToReturn) 
			{
				//$totalVideoToReturn doesn't change
			}
			else 
			{
				$requestedTotalVideoToReturn = ((int)((int)($videoCount - 1) / (int)$totalItemPerRow) * $totalItemPerRow) + 1;
			}

			// in the case there's only 1 video to return, skip this entry
			if( $requestedTotalVideoToReturn <= 1 )
			{
				continue;
			}

			if((int)$requestedTotalVideoToReturn>0)
			{
				$json = $conn->doQuery("SELECT `v_id`,`v_franchise_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,'' `v_url_poster`, `v_url_poster_landscape` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=".$row->f_id." AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') group by v_id ORDER BY v_id DESC LIMIT ".$requestedTotalVideoToReturn.";", 
									null,'json');
				$objVideo = json_decode($json);
				$dataVid=$objVideo->data->query_result;
				$dataCat[]=array(
								"f_id"=> $row->f_id,
								"f_url_poster_landscape"=> $row->f_url_poster_landscape,
								"t_name"=>$row->t_name,
								"data_vid"=>$dataVid);	
				if($lastFid[0]<$row->f_id)
				{
					$lastFid[0]=$row->f_id;
				}
			}
		}
	}

	if( $isEpisodic == 0 )
	{
		// if it's not episodic, then categorize the videos by its genre
		
		/*
		// uncomment this when doing by genre
		$json = $conn->doQuery("SELECT g_id, g_name, g_banner_url from n_genre group by g_id LIMIT ".$page_number.",".$limit_entry_per_page.";", null,'json');

		$objGenre = json_decode($json);
		$json = $conn->doQuery("SELECT count(*) total from n_genre", null,'json');
		$objCount=json_decode($json);
		$totalGenre=$objCount->data->query_result[0]->total;

		foreach( $objGenre->data->query_result as $row )
		GetNonEpisodicVideosByGenre($conn, $objGenre, $maxNumberOfRowsToBeViewed, $totalItemPerRow, $dataCat, $category, $country_name, "usa");
		*/
		GetNonEpisodicVideosByCountry($conn, $objGenre, $maxNumberOfRowsToBeViewed, $totalItemPerRow, $dataCat, $category, $country_name, "LIKE", 1, $page_number, $limit_entry_per_page);
		GetNonEpisodicVideosByCountry($conn, $objGenre, $maxNumberOfRowsToBeViewed, $totalItemPerRow, $dataCat, $category, $country_name, "NOT LIKE", 2, $page_number, $limit_entry_per_page);
	}
			
	$videoList=array("next_id"=>$page_number+$limit_entry_per_page, "data"=>$dataCat);
	$return["sta"] = "SUCCESS";
	$return["ret"]["dat"] = encrypt(json_encode($videoList));
}

function GetNonEpisodicVideosByCountry($conn, $objGenre, $maxNumberOfRowsToBeViewed, $totalItemPerRow, &$dataCat, $category, $country_name, $is_indonesia, $genreId, $page_number, $limit_entry_per_page)
{
	$country_origin = "indonesia";
	$maxNumberOfRowsToBeViewed = $maxNumberOfRowsToBeViewed + 1;

	// check how many videos we have that has this specific genre
	$json = $conn->doQuery("SELECT count(*) total FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND  (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access)LIKE '%ALL%') AND UPPER(f_country) ".$is_indonesia." '%".strtoupper($country_origin)."%' AND t.t_id =f.f_type_id AND t.t_id=".$category." and v.v_franchise_id=f.f_id ORDER BY v_last_updated DESC LIMIT ".$page_number.",".$limit_entry_per_page.";", null,'json');
	$objCount=json_decode($json);
	$videoCount=$objCount->data->query_result[0]->total;
	$data="";

	$requestedTotalVideoToReturn = ($maxNumberOfRowsToBeViewed * $totalItemPerRow) + 1;
	// if the latest season is not 1, the total video returned is as defined above
	if( $videoCount >= $requestedTotalVideoToReturn) 
	{
		//$totalVideoToReturn doesn't change
	}
	else 
	{
		$tempTotalVideoToReturn = ((int)((int)$videoCount / (int)$totalItemPerRow) * $totalItemPerRow) + 1;

		if( ($videoCount - 1) % $totalItemPerRow == ($totalItemPerRow + 1) )
		{
			$genreId = $row->g_id;
			$genreName = $row->g_name;
			$tempTotalVideoToReturn = $requestedTotalVideoToReturn - 1;
		}

			// check how many videos we have that has this specific genre
			$json = $conn->doQuery("SELECT count(*) total FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND f_genre_id_1='".$genreId."' AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." and v.v_franchise_id=f.f_id ORDER BY v_last_updated DESC;", null,'json');
			$objCount=json_decode($json);
			$videoCount=$objCount->data->query_result[0]->total;
			$data="";
		if( (int)($videoCount / $totalItemPerRow) == 0 )
		{
			$requestedTotalVideoToReturn = $videoCount;
		}
		else
		{
			$requestedTotalVideoToReturn = $tempTotalVideoToReturn;
		}
	}

			$requestedTotalVideoToReturn = ($maxNumberOfRowsToBeViewed * $totalItemPerRow) + 1;
			// if the latest season is not 1, the total video returned is as defined above
			if( $videoCount >= $requestedTotalVideoToReturn) 
			{
				//$totalVideoToReturn doesn't change
			}
			else 
			{
				$tempTotalVideoToReturn = ((int)((int)$videoCount / (int)$totalItemPerRow) * $totalItemPerRow) + 1;
	if( $requestedTotalVideoToReturn <= 1 )
	{
		continue;
	}

				if( ($videoCount - 1) % $totalItemPerRow == ($totalItemPerRow + 1) )
				{
					$tempTotalVideoToReturn = $requestedTotalVideoToReturn - 1;
				}
	// get the list of video
	$jsonVideo = $conn->doQuery("SELECT `v_franchise_id`,`v_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,
							CASE WHEN v_url_poster = ''
								THEN 'http://new.nonton.com/comm/images/posters/222_potrait.jpg'
								ELSE v_url_poster
							END AS v_url_poster,
						 `v_url_poster_landscape`,`f_url_poster_landscape` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access)LIKE '%ALL%') AND UPPER(f_country) ".$is_indonesia." '%".strtoupper($country_origin)."%' AND t.t_id =f.f_type_id AND t.t_id=".$category." and v.v_franchise_id=f.f_id group by v_id ORDER BY v_episode ASC LIMIT ".$requestedTotalVideoToReturn.";", null,'json');
	
	$objVideo = json_decode($jsonVideo);
	$data=array();
	$firstEntryLandscapePosterUrl = "";
	$currentIterator=0;
	foreach($objVideo->data->query_result as $rows)
	{
		if($currentIterator==0)
		{
			$firstEntryLandscapePosterUrl = $rows->f_url_poster_landscape;

				if( (int)($videoCount / $totalItemPerRow) == 0 )
				{
					$requestedTotalVideoToReturn = $videoCount;
				}
				else
				{
					$requestedTotalVideoToReturn = $tempTotalVideoToReturn;
				}
			$data[]=array( 
							"v_franchise_id"=> $rows->v_franchise_id,
							"v_id"=> $rows->v_id,
							"v_title"=> $rows->v_title,
							"v_url_youtube_id"=> $rows->v_url_youtube_id,
							"v_url_cdn"=> $rows->v_url_youtube_id,
							"v_prioritize_youtube"=> $rows->v_prioritize_youtube,
							"v_url_poster"=> $rows->v_url_poster,
							"v_url_poster_landscape"=> $firstEntryLandscapePosterUrl
						  );
		}
		else
		{
			$data[]=array(
							"v_franchise_id"=> $rows->v_franchise_id,
							"v_id"=> $rows->v_id,
							"v_title"=> $rows->v_title,
							"v_url_youtube_id"=> $rows->v_url_youtube_id,
							"v_url_cdn"=> $rows->v_url_youtube_id,
							"v_prioritize_youtube"=> $rows->v_prioritize_youtube,
							"v_url_poster"=> $rows->v_url_poster,
							"v_url_poster_landscape"=> ''
						  );
		}

		$currentIterator++;												  
	}
	
	if($requestedTotalVideoToReturn>=1)
	{
		$dataCat[]=array(
				"f_id"=> $genreId,
				//"f_url_poster_landscape"=> $row->g_banner_url,
				"f_url_poster_landscape"=> $firstEntryLandscapePosterUrl,
				"t_name"=>$country_origin,
				"data_vid"=>$data);
	}
}

function GetNonEpisodicVideosByGenre($conn, $objGenre, $maxNumberOfRowsToBeViewed, $totalItemPerRow, &$dataCat, $category, $country_name, $country_origin)
{
	// return based on genre
	foreach( $objGenre->data->query_result as $row )
	{
		$genreId = $row->g_id;
		$genreName = $row->g_name;

		// check how many videos we have that has this specific genre
		$json = $conn->doQuery("SELECT count(*) total FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND f_genre_id_1='".$genreId."' AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access)LIKE '%ALL%') AND UPPER(f_country) LIKE '%".strtoupper($country_origin)."%' AND t.t_id =f.f_type_id AND t.t_id=".$category." and v.v_franchise_id=f.f_id ORDER BY v_last_updated DESC;", null,'json');
		$objCount=json_decode($json);
		$videoCount=$objCount->data->query_result[0]->total;
		$data="";

		$requestedTotalVideoToReturn = ($maxNumberOfRowsToBeViewed * $totalItemPerRow) + 1;
		// if the latest season is not 1, the total video returned is as defined above
		if( $videoCount >= $requestedTotalVideoToReturn) 
		{
			//$totalVideoToReturn doesn't change
		}
		else 
		{
			$tempTotalVideoToReturn = ((int)((int)$videoCount / (int)$totalItemPerRow) * $totalItemPerRow) + 1;

			if( ($videoCount - 1) % $totalItemPerRow == ($totalItemPerRow + 1) )
			{
				$tempTotalVideoToReturn = $requestedTotalVideoToReturn - 1;
			}

			if( $requestedTotalVideoToReturn <= 1 )
			if( (int)($videoCount / $totalItemPerRow) == 0 )
			{
				continue;
				$requestedTotalVideoToReturn = $videoCount;
			}
			else
			{
				$requestedTotalVideoToReturn = $tempTotalVideoToReturn;
			}
		}

			// get the list of video
			$jsonVideo = $conn->doQuery("SELECT `v_franchise_id`,`v_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,
									CASE WHEN v_url_poster = ''
										THEN 'http://new.nonton.com/comm/images/posters/222_potrait.jpg'
										ELSE v_url_poster
									END AS v_url_poster,
								 `v_url_poster_landscape`,`f_url_poster_landscape` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND f_genre_id_1=".$genreId." AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." and v.v_franchise_id=f.f_id group by v_id ORDER BY v_episode ASC LIMIT ".$requestedTotalVideoToReturn.";", null,'json');
			
			$objVideo = json_decode($jsonVideo);
			$data=array();
			$firstEntryLandscapePosterUrl = "";
			$currentIterator=0;
			foreach($objVideo->data->query_result as $rows)
		if( $requestedTotalVideoToReturn <= 1 )
		{
			continue;
		}

		// get the list of video
		$jsonVideo = $conn->doQuery("SELECT `v_franchise_id`,`v_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,
								CASE WHEN v_url_poster = ''
									THEN 'http://new.nonton.com/comm/images/posters/222_potrait.jpg'
									ELSE v_url_poster
								END AS v_url_poster,
							 `v_url_poster_landscape`,`f_url_poster_landscape` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND f_genre_id_1=".$genreId." AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access)LIKE '%ALL%') AND UPPER(f_country) LIKE '%".strtoupper($country_origin)."%' AND t.t_id =f.f_type_id AND t.t_id=".$category." and v.v_franchise_id=f.f_id group by v_id ORDER BY v_episode ASC LIMIT ".$requestedTotalVideoToReturn.";", null,'json');
		
		$objVideo = json_decode($jsonVideo);
		$data=array();
		$firstEntryLandscapePosterUrl = "";
		$currentIterator=0;
		foreach($objVideo->data->query_result as $rows)
		{
			if($currentIterator==0)
			{
				if($currentIterator==0)
				{
					$firstEntryLandscapePosterUrl = $rows->f_url_poster_landscape;

					$data[]=array( 
									"v_franchise_id"=> $rows->v_franchise_id,
									"v_id"=> $rows->v_id,
									"v_title"=> $rows->v_title,
									"v_url_youtube_id"=> $rows->v_url_youtube_id,
									"v_url_cdn"=> $rows->v_url_youtube_id,
									"v_prioritize_youtube"=> $rows->v_prioritize_youtube,
									"v_url_poster"=> $rows->v_url_poster,
									"v_url_poster_landscape"=> $firstEntryLandscapePosterUrl
								  );
				}
				else
				{
					$data[]=array(
									"v_franchise_id"=> $rows->v_franchise_id,
									"v_id"=> $rows->v_id,
									"v_title"=> $rows->v_title,
									"v_url_youtube_id"=> $rows->v_url_youtube_id,
									"v_url_cdn"=> $rows->v_url_youtube_id,
									"v_prioritize_youtube"=> $rows->v_prioritize_youtube,
									"v_url_poster"=> $rows->v_url_poster,
									"v_url_poster_landscape"=> ''
								  );
				}
				$firstEntryLandscapePosterUrl = $rows->f_url_poster_landscape;

				$currentIterator++;												  
				$data[]=array( 
								"v_franchise_id"=> $rows->v_franchise_id,
								"v_id"=> $rows->v_id,
								"v_title"=> $rows->v_title,
								"v_url_youtube_id"=> $rows->v_url_youtube_id,
								"v_url_cdn"=> $rows->v_url_youtube_id,
								"v_prioritize_youtube"=> $rows->v_prioritize_youtube,
								"v_url_poster"=> $rows->v_url_poster,
								"v_url_poster_landscape"=> $firstEntryLandscapePosterUrl
							  );
			}
			
			if($requestedTotalVideoToReturn>=1)
			else
			{
				$dataCat[]=array(
						"f_id"=> $genreId,
						//"f_url_poster_landscape"=> $row->g_banner_url,
						"f_url_poster_landscape"=> $firstEntryLandscapePosterUrl,
						"t_name"=>$genreName,
						"data_vid"=>$data);
				$data[]=array(
								"v_franchise_id"=> $rows->v_franchise_id,
								"v_id"=> $rows->v_id,
								"v_title"=> $rows->v_title,
								"v_url_youtube_id"=> $rows->v_url_youtube_id,
								"v_url_cdn"=> $rows->v_url_youtube_id,
								"v_prioritize_youtube"=> $rows->v_prioritize_youtube,
								"v_url_poster"=> $rows->v_url_poster,
								"v_url_poster_landscape"=> ''
							  );
			}
		}

			$currentIterator++;												  
		}
		
		if($requestedTotalVideoToReturn>=1)
		{
			$dataCat[]=array(
					"f_id"=> $genreId,
					//"f_url_poster_landscape"=> $row->g_banner_url,
					"f_url_poster_landscape"=> $firstEntryLandscapePosterUrl,
					"t_name"=>$genreName,
					"data_vid"=>$data);
		}
	}
			
	$videoList=array("next_id"=>$page_number+$limit_entry_per_page, "data"=>$dataCat);
	$return["sta"] = "SUCCESS";
	$return["ret"]["dat"] = encrypt(json_encode($videoList));
				
}


function check_session($conn, $sessionid)
{
	$json=$conn->doQuery("SELECT s_user_id from n_session where s_id=".$sessionid.";", null, 'json');
	//echo $json;
	return json_decode($json);
}

function add_tokens($conn, $uid, $type)
{
	global $return;
	$addPoints=0;

	switch( $type )
	{
		case 'ppv':
		{
			$addPoints=3000;
			break;
		}
	}
	
	$json=$conn->doQuery("select * from n_user where u_id=".$uid.";", null, 'json');
	$objUser= json_decode($json);
	//echo $json=
	$points=$objUser->data->query_result[0]->u_points+$addPoints;
	$json=$conn->doQuery("update n_user set u_points=".$points." where u_id=".$objUser->data->query_result[0]->u_id.";", null, 'json');	
	if(json_decode($json)->data->result=='ok')
	{
		$json=$conn->doQuery("select u_points from n_user where u_id=".$uid.";", null, 'json');
		$objUser= json_decode($json);
		$return["sta"] = "SUCCESS";
		$return["ret"]["dat"] = encrypt(json_encode($objUser->data->query_result[0]));
	}
	else
	{
		$return["sta"] = "FAIL";
	}
	
}

function get_ppv($conn, $uid)
{
	global $return;
	$json=$conn->doQuery("select ppv_video_id from n_pay_per_view where ppv_user_id='".$uid."' and ppv_date_ended>NOW();", null, 'json');
	//echo $json;
	$objPpv=json_decode($json);
	$data=array();
	foreach($objPpv->data->query_result as $row)
	{
		$json=$conn->doQuery("select * from n_video where v_id=".$row->ppv_video_id.";", null, 'json');
		$data[]=json_decode($json)->data->query_result;
	}
	$return["sta"] = "SUCCESS";
	$return["ret"]["dat"] = encrypt(json_encode($data));
}

function add_playlist($conn, $uid, $videoid, $sid)
{
	global $return;
	$json = $conn->doQuery("select * from n_video where v_id=".$videoid.";", 
										null,'json');
	$objVideo=json_decode($json);	
	if($objVideo->data->query_result[0] =='' || $objVideo->data->query_result[0] ==null)
	{
		$return["sta"] = "FAIL";
	}
	else
	{
		$jsonPlaylist=$conn->doQuery("select pl_video_id from n_playlist where pl_is_active=1 and pl_user_id='".$uid."' and pl_video_id='".$videoid."';", null, 'json');
		$objPlaylist=json_decode($jsonPlaylist);
		if(!empty($objPlaylist->data->query_result))
		{
			$json=$conn->doQuery("update n_playlist set pl_is_active=0 where pl_user_id='".$uid."' and pl_video_id='".$videoid."'", null, 'json');
		}
		else
		{
			$jsonVideo = $conn->doQuery("SELECT `v_name` FROM `n_video` v WHERE v.v_id=".$videoid." LIMIT 1;", null,'json');
			$objVideo=json_decode($jsonVideo);
			$videoName=(int)$objVideo->data->query_result[0]->v_name;
			$json = $conn->doQuery("insert into n_playlist (`pl_session_id`, `pl_video_name`, `pl_user_id`, `pl_video_id`) 
							values ('".$sid."','".$videoName."', '".$uid."', '".$videoid."');", 
											null,'json');
		}
		$return["sta"] = "SUCCESS";
	}
}

function get_playlist($conn, $uid)
{
	global $return;
	$json=$conn->doQuery("select pl_video_id from n_playlist where pl_is_valid=1 and pl_user_id='".$uid."';", null, 'json');
	
	$objPlaylist=json_decode($json);
	$data=array();
	foreach($objPlaylist->data->query_result as $row)
	{
		$json=$conn->doQuery("select * from n_video where v_id=".$row->pl_video_id.";", null, 'json');
		$data[]=json_decode($json)->data->query_result;
	}
	$return["sta"] = "SUCCESS";
	$return["ret"]["dat"] = encrypt(json_encode($data));
}

function add_notification($conn, $uid, $type, $videoid, $commentid)
{
	global $return;
	$json=$conn->doQuery("select v_franchise_id from n_video where v_id=".$videoid.";", null, 'json');
	$objVideo=json_decode($json);
	$json=$conn->doQuery("insert into n_notification (n_notification_message_title_id, n_notification_message_body_id, n_user_id, n_franchise_id, n_video_id, n_comment_id, n_is_read) 
						   values (1,
								   2,
								   '".$uid."',
								   '".$objVideo->data->query_result[0]->v_franchise_id."',
								   '".$videoid."',
								   '".$commentid."',
								   0
								   );", null, 'json');
	//echo $json;
	if(json_decode($json)->data->result=='ok')
	{
		$return["sta"] = "SUCCESS";
	}
	else
	{
		$return["sta"] = "FAILED";
	}
}
?>