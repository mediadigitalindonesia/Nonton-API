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
	$json = $conn->doQuery("SELECT `v_franchise_id`,`v_id`, f_name `v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_url_poster`, `v_url_poster_landscape`, `t_name`, `t_id`, `t_url_poster_landscape` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$typeid." group by v_franchise_id  ORDER BY v_last_updated DESC LIMIT ".$limit.";", 
										null,'json');
	$objVideo = json_decode($json);
	return $objVideo->data->query_result;
}

function get_video_list($conn, $start, $franchiseid, $category,$country_name)
{
	global $return;
	if(strtoupper($country_name)=='ALL')
		$country_name="";
	$the_search_result = array();

	
			$json = $conn->doQuery("SELECT `v_franchise_id`,`v_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_url_poster`, `v_url_poster_landscape`, `t_name`, `t_id`, `t_url_poster_landscape`, `v_season` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." GROUP BY v_franchise_id ORDER BY v_franchise_id DESC LIMIT ".$start.",5;", 
										null,'json');
			//echo json_encode($json);
			$objVideo = json_decode($json);
			$json = $conn->doQuery("SELECT count(*) total FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." ORDER BY v_last_updated DESC;", 
										null,'json');
			$count = json_decode($json)->data->query_result[0]->total;
			$data=array();
			foreach($objVideo->data->query_result as $row)
			{
				$json = $conn->doQuery("SELECT count(DISTINCT v_episode) total FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id='".$row->v_franchise_id."' AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') ORDER BY v_last_updated DESC;", 
									    null,'json');
				$objCount=json_decode($json);
				$count=$objCount->data->query_result[0]->total;
				if($count>1 && $count<5)
				{
					$limit=3;
					$json = $conn->doQuery("SELECT `v_franchise_id`,v_episode,v_season,`v_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,'' `v_url_poster`, `v_url_poster_landscape`, `t_name`, `t_id`, `t_url_poster_landscape` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND  v_franchise_id='".$row->v_franchise_id."' AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND v_season=".$row->v_season."   group by v_id ORDER BY v_episode ASC LIMIT ".$limit.";", 
										null,'json');
					$objVideo = json_decode($json);
					$data[]=$objVideo->data->query_result;
				}
				else if($count>=5)
				{
					$limit=5;
					$json = $conn->doQuery("SELECT `v_franchise_id`,v_episode,v_season,`v_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`, '' `v_url_poster`, `v_url_poster_landscape`, `t_name`, `t_id`, `t_url_poster_landscape` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND  v_franchise_id='".$row->v_franchise_id."' AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND v_season=".$row->v_season."  group by v_id ORDER BY v_episode ASC LIMIT ".$limit.";", 
										null,'json');
					$objVideo = json_decode($json);
					$data[]=$objVideo->data->query_result;
				}
				else 
				{
					$json = $conn->doQuery("SELECT count(*) total FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." ORDER BY v_last_updated ;", 
										null,'json');
					$objCount = json_decode($json);
					$count=$objCount->data->query_result[0]->total;
					if($count>4 && $count<7)
						$limit=4;
					else
						$limit=7;
					$json = $conn->doQuery("SELECT `v_franchise_id`,`v_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_url_poster`, `v_url_poster_landscape`, `t_name`, `t_id`, `t_url_poster_landscape`, `v_season` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." group by v_id ORDER BY v_episode ASC LIMIT ".$limit.";", 
										null,'json');
					$objVideo = json_decode($json);
					$data[]=$objVideo->data->query_result;
				}
					
				
			}
			if(($start+1)<$count)
			{
				$nextPage=true;
			}
			else
			{
				$nextPage=false;
			}
			//$videoList=array("next_id"=>$lastFid[0], "data"=>$dataCat);
			$return["sta"] = "SUCCESS";
			$return["ret"]["dat"] = encrypt(json_encode(array("nextPage"=>$nextPage, "listVideo"=>$data)));
				
}

function search_franchise($conn, $kwd, $country_name, $start)
{
	global $return;
	if(strtoupper($country_name)=='ALL')
		$country_name="";
	$json = $conn->doQuery("SELECT COUNT(*),f_country_access,, f_id  FROM (SELECT f_id, f_country_access from n_franchise f 
							LEFT JOIN n_genre as g1 on f_genre_id_1=g1.g_id 
							LEFT JOIN n_genre as g2 on f_genre_id_2=g2.g_id 
							LEFT JOIN n_genre as g3 on f_genre_id_3=g3.g_id 
							LEFT JOIN n_genre as g4 on f_genre_id_4=g4.g_id 
							LEFT JOIN n_genre as g5 on f_genre_id_5=g5.g_id) tino
								where upper(f_country_access) like '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%' group by f_id;", 
										null,'json');
	$count = json_decode($json)->data->query_result[0]->total;
	$json = $conn->doQuery("SELECT * FROM (SELECT distinct(f_id), 	v_url_youtube_id, v_url_cdn, f_url_poster, f_url_poster_landscape, f_synopsis, f_name, f_company, f_country_access, ifnull(g1.g_id, '') g_id1, ifnull(g1.g_name, '') g_name1, ifnull(g2.g_id, '') g_id2,  ifnull(g2.g_name, '') g_name2, ifnull(g3.g_id,'') g_id3, ifnull(g3.g_name,'') g_name3, ifnull(g4.g_id,'') g_id4,   ifnull(g4.g_name,'') g_name4,ifnull(g5.g_id, '') g_id5,  ifnull(g5.g_name, '') g_name5
 from n_franchise f 
							LEFT JOIN n_video as v on f_id=v.v_franchise_id
							LEFT JOIN n_genre as g1 on f_genre_id_1=g1.g_id 
							LEFT JOIN n_genre as g2 on f_genre_id_2=g2.g_id 
							LEFT JOIN n_genre as g3 on f_genre_id_3=g3.g_id 
							LEFT JOIN n_genre as g4 on f_genre_id_4=g4.g_id 
							LEFT JOIN n_genre as g5 on f_genre_id_5=g5.g_id) tino
								where upper(f_name) like '%".strtoupper($kwd)."%' and (upper(f_country_access) like '%".strtoupper($country_name)."%' OR UPPER(f_country_access ) LIKE '%ALL%') group by f_id
							LIMIT ".$start.",10;", 
										null,'json');
	if(($start+1)<$count/10)
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
	if(strtoupper($country_name)=='ALL')
		$country_name="";
	$json = $conn->doQuery("SELECT `f_name`,`v_franchise_id` FROM `n_video` v, `n_franchise` f WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND upper(f_name) like '%".strtoupper($kwd)."%' GROUP BY F_NAME ORDER BY v_last_updated DESC LIMIT 10;", 
										null,'json');
//echo json_encode($json);	
$objFranchise = json_decode($json);

	$return["sta"] = "SUCCESS";
	$return["ret"]["dat"] = encrypt(json_encode($objFranchise->data->query_result));;
	
}

function insert_action($conn, $param)
{
	$json = $conn->doQuery("insert into n_action (`an_session_id`, `an_name`, `an_note`, `an_is_valid`) values ('".$param['sessionid']."','".$param['an_name']."','".$param['an_note']."',".$param['an_is_valid'].");", 
										null,'json');
	//echo json_decode($json);
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
	$json = $conn->doQuery("Select `s_id`,`s_user_id`, `s_device_id`, `s_fb_id`, `s_name`, `s_origin_ip`, `s_origin_country_id`, `s_origin_country_name`, `s_client_id`, `s_client_name` from  n_session where s_id=".$param.";", 
					null,'json');
	//echo json_encode($json);
	$objSession = json_decode($json);
	//echo  json_encode($objCountry->data->query_result);;
	return $objSession->data->query_result;
}

function getCountryByName($conn, $param)
{
	global $rsCountry;
	$json = $conn->doQuery("select `cy_id`, `cy_name`, `cy_is_active`, `cy_creator` from  n_country where upper(cy_name) like '%".$param."%';", 
					null,'json');
	$objCountry = json_decode($json);
	//echo  json_encode($objCountry->data->query_result);;
	return $objCountry->data->query_result;
}

function get_video_detail($conn,  $videoId=null, $favorite, $activityId, $userid)
{
	global $return;
	//echo $activityId;
	$json=$conn->doQuery("select v_id, t_name, v_title, v_price, f_genre_id_1, f_genre_id_2,f_genre_id_3,f_genre_id_4,f_genre_id_5,f_company, v_franchise_id, v_synopsis, v_url_youtube_id,v_season, v_episode, v_year_production, v_director, v_casts, v_price from n_video v, n_franchise f, n_type t where t.t_id =f.f_type_id and v_id=".$videoId." and v.v_franchise_id=f.f_id",
						null,'json');
	$objVideoDetail=json_decode($json);
	$json=$conn->doQuery("select v_id, v_title, v_url_poster, v_url_poster_landscape,v_url_cdn,v_season, v_episode, v_year_production, v_director, v_casts, v_price from n_video where v_franchise_id=".$objVideoDetail->data->query_result[0]->v_franchise_id." and v_season=".$objVideoDetail->data->query_result[0]->v_season,
						null,'json');
	$objEpisode=json_decode($json);
	$json=$conn->doQuery("select distinct(v_id) v_id, v_franchise_id, v_title, v_url_youtube_id,v_url_cdn, v_prioritize_youtube, v_url_poster, v_url_poster_landscape from n_video v, n_franchise f where v.v_franchise_id=f.f_id and f_genre_id_1 in (".$objVideoDetail->data->query_result[0]->f_genre_id_1.",".$objVideoDetail->data->query_result[0]->f_genre_id_2.", ".$objVideoDetail->data->query_result[0]->f_genre_id_3.",".$objVideoDetail->data->query_result[0]->f_genre_id_4.",".$objVideoDetail->data->query_result[0]->f_genre_id_5.") 
							or f_genre_id_2 in (".$objVideoDetail->data->query_result[0]->f_genre_id_1.",".$objVideoDetail->data->query_result[0]->f_genre_id_2.", ".$objVideoDetail->data->query_result[0]->f_genre_id_3.",".$objVideoDetail->data->query_result[0]->f_genre_id_4.",".$objVideoDetail->data->query_result[0]->f_genre_id_5.") 
							or f_genre_id_3 in (".$objVideoDetail->data->query_result[0]->f_genre_id_1.",".$objVideoDetail->data->query_result[0]->f_genre_id_2.", ".$objVideoDetail->data->query_result[0]->f_genre_id_3.",".$objVideoDetail->data->query_result[0]->f_genre_id_4.",".$objVideoDetail->data->query_result[0]->f_genre_id_5.") 
							or f_genre_id_4 in (".$objVideoDetail->data->query_result[0]->f_genre_id_1.",".$objVideoDetail->data->query_result[0]->f_genre_id_2.", ".$objVideoDetail->data->query_result[0]->f_genre_id_3.",".$objVideoDetail->data->query_result[0]->f_genre_id_4.",".$objVideoDetail->data->query_result[0]->f_genre_id_5.") 
							or f_genre_id_5 in (".$objVideoDetail->data->query_result[0]->f_genre_id_1.",".$objVideoDetail->data->query_result[0]->f_genre_id_2.", ".$objVideoDetail->data->query_result[0]->f_genre_id_3.",".$objVideoDetail->data->query_result[0]->f_genre_id_4.",".$objVideoDetail->data->query_result[0]->f_genre_id_5.") 
							and v_id !=".$objVideoDetail->data->query_result[0]->v_id." group by v_franchise_id;",
						null,'json');
	$objSimiliarVideo=json_decode($json);
	$json=$conn->doQuery("select count(*) total from n_activity where av_video_id='".$videoId."'",
						null,'json');
	$objCount=json_decode($json);
	$objEpisode=json_decode($json);
	
	
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
					echo 'true';
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
	  "v_url_cdn"=> $objVideoDetail->data->query_result[0]->v_url_cdn,
	  "views"=>$objCount->data->query_result[0]->total,
	  "favorite"=>$favorite,
	  "eligible"=>$eligible,
	  "similiar_video"=>$objSimiliarVideo->data->query_result,
	  "episode"=> $objEpisode->data->query_result
	  );
	//array_push($objVideoDetail->data->query_result, $objEpisode->data->query_result);
	$return["sta"] = "SUCCESS";
	$return["ret"]["dat"] = encrypt(json_encode(array("activityId"=>$activityId,"video"=>$data)));;
	return $data;
	
}

function insert_activity($conn, $sessionid, $videoId)
{
	$date=date('Y-m-d H:i:s');
	$json=$conn->doQuery("insert into n_activity (av_session_id, av_video_id, av_time_start) values ('".$sessionid."','".$videoId."','".$date."');",
						null, 'json');
	//echo json_encode($json);;
	return json_decode($json);					
}

function update_activity($conn, $av_id,$duration, $share, $resolution, $timeEnd)
{
	
	$json=$conn->doQuery("update n_activity set av_duration=".$duration.", av_share=".$share.", av_resolution='".$resolution."', av_time_end='".$timeEnd."' where av_id=".$av_id,
						null, 'json');
}

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
	$json=$conn->doQuery("insert into n_comment (cm_session_id, Cm_replied_to_id , Cm_user_id , Cm_video_id , Cm_activity_id, Cm_title, Cm_body ) values (".$sid.",".$commentId.",".$userId.",".$videoId.",".$activityId.",'".$title."', '".$body."');",
						null, 'json');
	//echo json_encode($json);
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
		$json=$conn->doQuery("SELECT u_id, u_points FROM n_user WHERE u_referral_id='".$referalid."';",
							null,'json');
		$check=json_decode($json);
		if($check->data->query_result[0]->u_id != null)
		{
			$totalPoints=(int)$check->data->query_result[0]->u_points + 10;
			$json=$conn->doQuery("UPDATE n_user SET u_points=".$totalPoints." WHERE u_id=".$check->data->query_result[0]->u_id.";",
							null,'json');
		}			
		$json = $conn->doQuery("insert into n_user (U_fbid, U_device_id, U_username, U_password, U_fullname,U_gender,U_birthday, U_points, u_avatar_url, u_email) 
								values ('".facebookid."','".$deviceid."','".$username."','".$password."','".fullname."','".$gender."','".$birthday."',10, '".$photourl."', '".$email."');", 
											null,'json');
		//echo json_encode($json);
		$insert=json_decode($json);
		$json = $conn->doQuery("SELECT `u_id`, `u_points` points, u_fullname, u_avatar_url, u_subscription_end FROM `n_user` u WHERE `u_id` = ".$insert->data->query_id.";", 
										null,'json');
		$objUser = json_decode($json);
		$data=$objUser->data->query_result[0];
	
		$return["sta"] = "SUCCESS";
		$return["ret"]["dat"] = encrypt(json_encode($data));
	}
		
}

function load_user($conn, $email, $password, $facebookid, $sid)
{
	global $return;
	if($email == '')
	{
		$json = $conn->doQuery("SELECT `u_id`, `u_points`, u_fullname, u_avatar_url, u_subscription_end FROM `n_user` u WHERE `u_fbid` = '".$facebookid."';", 
										null,'json');
		$objUser = json_decode($json);
	}
	else
	{
		$json = $conn->doQuery("SELECT `u_id`, `u_points`, u_fullname, u_avatar_url, u_email, u_subscription_end FROM `n_user` u WHERE `u_email` = '".$email."' and u_password='".$password."';", 
										null,'json');
		$objUser = json_decode($json);
		//echo $json;
	}
	if($objUser->data->query_result[0]==null || $objUser->data->query_result[0]=='')
	{
		$return["sta"] = "EMAIL/PASSWORD NOT VALID";
	}
	else
	{
		$json = $conn->doQuery("UPDATE n_session set s_user_id='".$objUser->data->query_result[0]->u_id."' where s_id=".$sid.";", 
										null,'json');
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
	if($usetoken=="1")
	{
		$point=$video->data->query_result[0]->v_price;
		$amount=0;
		$json = $conn->doQuery("SELECT `u_id`, `u_points` FROM `n_user` u WHERE `u_id` = '".$userid."';", 
										null,'json');
		
		$user=json_decode($json);
		if((int)$user->data->query_result[0]->u_points<(int)$point)
		{
			$return["sta"] = "INSUFICIENT POINT";
			//echo "test";
			return $return;
			
			break;
		}
		/*$json = $conn->doQuery("update n_user set `u_points`= ".((int)$user->data->query_result[0]->u_points-(int)$point)." where  `u_id` = '".$userid."';", 
											null,'json');*/
		//echo json_encode($json);
	}
	else
	{
		$point=0;
		$amount=$video->data->query_result[0]->v_price;
	}
		
	/*$json = $conn->doQuery("insert into n_pay_per_view (ppv_user_id, ppv_video_id, ppv_session_id,ppv_points_used, ppv_amount_paid) 
							values ('".$userid."','".$videoid."','".$sid."',".$point.", ".$amount.");", 
										null,'json');*/
	//echo json_encode($json);
	$json = $conn->doQuery("call purchase_video('".$userid."','".$videoId."','".$sid."',".$point.",".$amount.");", 
											null,'json');
	//echo json_encode($json);
	$json = $conn->doQuery("SELECT `u_id`, `u_points` FROM `n_user` u WHERE `u_id` = '".$userid."';", 
										null,'json');
	$user=json_decode($json);									
	$data=array(
				"u_points"=>$user->data->query_result[0]->u_points,
				"videoid"=>$videoid,
				"expirationdate"=>date('Y-m-d', strtotime(' +2 day'))
				);
	$return["sta"] = "SUCCESS";
	$return["ret"]["dat"] = encrypt(json_encode($data));;
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

function check_favorite($conn, $sessionid, $videoid)
{
	$json=$conn->doQuery("select s_user_id favorite from n_session  where s_id=".$sessionid.";",
						null,'json');
	$objSession=json_decode($json);
	$json=$conn->doQuery("select count(*) favorite from n_action  where an_note='".$objSession->data->query_result[0]->s_user_id.'-'.$videoId."';",
						null,'json');
	$objAction=json_decode($json);
	$count=(int)$objSession->data->query_result[0]->favorite;
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

function get_video_list_old($conn, $limit, $franchiseid, $category,$country_name)
{
	global $return;
	if(strtoupper($country_name)=='ALL')
		$country_name='';
	$the_search_result = array();
			// TODO : why are you checking franchise id, why did you put this line below "and f_id > ".$franchiseid."
			// shouldn't we only check the type and return every franchise with this type? Maybe I'm understanding this code wrong, let me know
			// TODO : where is the pagination? There is only a limit set but no indication where to start counting
			$json = $conn->doQuery("SELECT `f_id`, `f_url_poster_landscape`, `t_name`  
												FROM  `n_franchise` f, `n_type` t WHERE t.t_id =f.f_type_id AND t.t_id=".$category." ORDER BY f_date_added ASC LIMIT ".$franchiseid.",5;", 
										null,'json');
			$objFranchise = json_decode($json);
			$banner=null;
			$dataTotal=null;
			$dataCat=array();
			$lastFid=array();
			if($category==1 || $category==2)
			{
				$max=5;
				$min=3;
			}
			else
			{
				$max=7;
				$min=7;
			}
			if($category==1 || $category==2)
			{
				foreach($objFranchise->data->query_result as $row)
				{
					$json = $conn->doQuery("SELECT count(distinct(v_id)) total,`v_id`,`v_franchise_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_url_poster`, `v_url_poster_landscape` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=".$row->f_id." AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%')  ORDER BY v_last_updated DESC;", 
											null,'json');
					$count= json_decode($json)->data->query_result[0]->total;
					$objVideo = json_decode($json);
					
					
						if($count>=3 && $count<5)
							$limit=3;
						else if($count>=5)
							$limit=5;
						else
							$limit=0;
					
					
					if((int)$limit>0)
					{
						$json = $conn->doQuery("SELECT `v_id`,`v_franchise_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,'' `v_url_poster`, `v_url_poster_landscape` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=".$row->f_id." AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') group by v_id ORDER BY v_last_updated DESC LIMIT ".$limit.";", 
											null,'json');
						$objVideo = json_decode($json);
						$dataVid=$objVideo->data->query_result;
						$dataCat[]=array(
										"f_id"=> $row->f_id,
										"f_url_poster_landscape"=> $row->f_url_poster_landscape,
										"t_name"=>$row->t_name,
										"data_vid"=>$dataVid);	
						if($lastFid[0]<$row->f_id)
							$lastFid[0]=$row->f_id;
					}
				}
				
			}
			else
			{
				$json = $conn->doQuery("SELECT g_id, g_name, g_banner_url from n_genre group by g_id LIMIT ".$franchiseid.",5;", null,'json');
				//
				$objGenre = json_decode($json);
				$json = $conn->doQuery("SELECT count(*) total from n_genre", null,'json');
				$objCount=json_decode($json);
				$count=$objCount->data->query_result[0]->total;
				foreach( $objGenre->data->query_result as $row )
				{
					$genreId=$row->g_id;
					//echo $genreId;
					$json = $conn->doQuery("SELECT `v_franchise_id`,`v_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_url_poster`, `v_url_poster_landscape`, `t_name`, `t_id`, `t_url_poster_landscape`, `v_season`, `f_genre_id_1` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." AND f.f_genre_id_1=".$genreId." GROUP BY f_genre_id_1 ORDER BY v_last_updated DESC LIMIT ".$franchiseid.",5;", null,'json');
					//echo $json;
					$objVideo = json_decode($json);
					//foreach($objVideo->data->query_result as $row)
					//{
						// check how many videos we have that has this specific genre
						$json = $conn->doQuery("SELECT count(*) total FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND f_genre_id_1='".$genreId."' AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') ORDER BY v_last_updated DESC;", null,'json');
						$objCount=json_decode($json);
						$videoCount=$objCount->data->query_result[0]->total;

						$maxNumberOfRowsToBeViewed = 2;
						// if we do that means this is a franchise without any episodes (for examples: movies, premium)
						if( $videoCount > 0 )
						{
							$base_calculation = 3;

							$totalVideoToReturn = $maxNumberOfRowsToBeViewed * $base_calculation;

							// if the latest season is not 1, the total video returned is as defined above
							if( $videoCount >= 7) 
							{
								$totalVideoToReturn =7;
							}
							else if ($videoCount >= 4 && $videoCount <7 )
							{
								$totalVideoToReturn = 4;
							}
							else
							{
								$totalVideoToReturn = 0;
							}
								
							
							

							// get the list of video
							$jsonVideo = $conn->doQuery("SELECT `v_franchise_id`,`v_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,
													CASE WHEN v_url_poster = ''
														THEN 'http://new.nonton.com/comm/images/posters/222_potrait.jpg'
														ELSE v_url_poster
													END AS v_url_poster,
												 '' `v_url_poster_landscape`  FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND f_genre_id_1=".$genreId." AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." and v.v_franchise_id=f.f_id group by v_id ORDER BY v_episode ASC LIMIT ".$totalVideoToReturn.";", 
												null,'json');
												//echo json_encode($jsonVideo).'<br>';
							$objVideo = json_decode($jsonVideo);
							$data=$objVideo->data->query_result;
						}
					//}
					$dataCat[]=array(
										"f_id"=> $genreId,
										"f_url_poster_landscape"=> "",
										"t_name"=>'Premium',
										"data_vid"=>$data);
			
				}
			}
			
			$videoList=array("next_id"=>$franchiseid+5, "data"=>$dataCat);
			$return["sta"] = "SUCCESS";
			$return["ret"]["dat"] = encrypt(json_encode($videoList));
				
}

function get_video_list_tino($conn, $start, $franchiseid, $category,$country_name)
{
	global $return;
	$the_search_result = array();

	$isEpisodic = false;
	
	$json = $conn->doQuery("SELECT `v_franchise_id`,`v_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_url_poster`, `v_url_poster_landscape`, `t_name`, `t_id`, `t_url_poster_landscape`, `v_season` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." GROUP BY v_franchise_id ORDER BY v_last_updated DESC LIMIT ".$start.",5;", 
		null,'json');
	//echo json_encode($json);
	$objVideo = json_decode($json);

	$totalFranchise = 0;
	
	$json = $conn->doQuery("SELECT count(*) total FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." ORDER BY v_last_updated DESC;", null,'json');
	$count = json_decode($json)->data->query_result[0]->total;
	
	$data=array();
	foreach($objVideo->data->query_result as $row)
	{
		// check if we have any video from season -1
		$json = $conn->doQuery("SELECT count(DISTINCT v_episode) total FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_season=-1 AND v_is_featured=1 AND v_is_active =1 AND v_franchise_id='".$row->v_franchise_id."' AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') ORDER BY v_last_updated DESC;", 
							    null,'json');
		$objCount=json_decode($json);
		$count=$objCount->data->query_result[0]->total;

		$maxNumberOfRowsToBeViewed = 2;
		// if we do that means this is a franchise without any episodes (for examples: movies, premium)
		// it will be handled down below
		if( $count > 0 )
		{
			// every video, based on the type should be the same
			// so if 1 video has season = -1, that means all video for this category will have it as well
			// which also means this type is not a serial type
			break;
		}
		else
		{
			$base_calculation = 2;
			$isEpisodic = true;

			// get the highest season
			$jsonSeason = $conn->doQuery("SELECT MAX(v_season) as `max` FROM `n_video`, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_franchise_id='".$row->v_franchise_id."' AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." ORDER BY v_last_updated ;", 
								null,'json');

			$objSeason = json_decode($jsonSeason);
			$theLatestSeason = $objSeason->data->query_result[0]->max;

			// now we know what the latest season number is, we also know the first season has to be 1
			// from then on it's easy, we just need to count the number of episode in the latest season
			$jsonCount = $conn->doQuery("SELECT count(*) total FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_franchise_id='".$row->v_franchise_id."' AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." ORDER BY v_last_updated ;", 
								null,'json');
			$objCount = json_decode($jsonCount);
			$theTotalEpisodeInLatestSeason=$objCount->data->query_result[0]->total;

			$totalVideoToReturn = $maxNumberOfRowsToBeViewed * $base_calculation;

			// if the latest season is not 1, the total video returned is as defined above
			if( $theLatestSeason > 1 || ($theLatestSeason == 1 && $theTotalEpisodeInLatestSeason >= $totalVideoToReturn) )
			{
				//$totalVideoToReturn doesn't change
			}
			else //if( $theLatestSeason == 1 && $theTotalEpisodeInLatestSeason < $base_calculation )
			{
				$totalVideoToReturn = (int)((int)$theTotalEpisodeInLatestSeason / (int)$base_calculation) * $base_calculation;
			}

			// the banner should always return video of season 1 episode 1
			$jsonBanner = $conn->doQuery("SELECT `v_franchise_id`,`v_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`, '' `v_url_poster`, `v_url_poster_landscape`, `t_name`, `t_id`, `t_url_poster_landscape`, `v_season` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_season=1 AND v_episode=1 AND v_is_featured=1 AND v_is_active =1 AND v_franchise_id='".$row->v_franchise_id."' AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." group by v_id ORDER BY v_episode ASC LIMIT 1;", 
								null,'json');
			$objBanner = json_decode($jsonBanner);
			$data[]=$objBanner->data->query_result;

			// get the list of video
			$jsonVideo = $conn->doQuery("SELECT `v_franchise_id`,`v_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`, '' `v_url_poster`, `v_url_poster_landscape`, `t_name`, `t_id`, `t_url_poster_landscape`, `v_season` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND v_franchise_id='".$row->v_franchise_id."' AND  (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." group by v_id ORDER BY v_episode ASC LIMIT ".$totalVideoToReturn.";", 
								null,'json');
			$objVideo = json_decode($jsonVideo);
			$data[]=$objVideo->data->query_result;
		}
	}

	// this portion of code will be called if it's a non episodic type (movie, premium, etc)
	if( $isEpisodic == false )
	{
		// for now, because of the limitation of amount of movies in specific genre, we only want to show those with genre_id 1-4
		// the rest will be put in a special category
		$json = $conn->doQuery("SELECT g_id, g_banner_url from n_genre LIMIT ".$start.",5", null,'json');
		$objGenre = json_decode($json);
		$json = $conn->doQuery("SELECT count(*) total from n_genre", null,'json');
		$objCount=json_decode($json);
		$count=$objCount->data->query_result[0]->total;
		foreach( $objGenre->data->query_result as $row  )
		{
			$genreId=$row->g_id;
			$json = $conn->doQuery("SELECT `v_franchise_id`,`v_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_url_poster`, `v_url_poster_landscape`, `t_name`, `t_id`, `t_url_poster_landscape`, `v_season`, `f_genre_id_1` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." AND f.f_genre_id_1=".$genreId." GROUP BY f_genre_id_1 ORDER BY v_last_updated DESC LIMIT ".$start.",5;", null,'json');
			
			$objVideo = json_decode($json);
			foreach($objVideo->data->query_result as $row)
			{
				// check how many videos we have that has this specific genre
				$json = $conn->doQuery("SELECT count(*) total FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND f_genre_id_1='".$row->f_genre_id_1."' AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') ORDER BY v_last_updated DESC;", null,'json');
				$objCount=json_decode($json);
				$videoCount=$objCount->data->query_result[0]->total;

				$maxNumberOfRowsToBeViewed = 2;
				// if we do that means this is a franchise without any episodes (for examples: movies, premium)
				if( $videoCount > 0 )
				{
					$base_calculation = 3;

					$totalVideoToReturn = $maxNumberOfRowsToBeViewed * $base_calculation;

					// if the latest season is not 1, the total video returned is as defined above
					if( $videoCount >= $totalVideoToReturn) 
					{
						//$totalVideoToReturn doesn't change
					}
					else 
					{
						$totalVideoToReturn = (int)((int)$videoCount / (int)$base_calculation) * $base_calculation;
					}

					$totalVideoToReturn + 1;

					// get the list of video
					$jsonVideo = $conn->doQuery("SELECT `v_franchise_id`,`v_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_url_poster`, '' `v_url_poster_landscape`, `t_name`, `t_id`, `t_url_poster_landscape`, `v_season`, `f_genre_id_1`,  '".$row->g_banner_url."' g_banner_url FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND f_genre_id_1=".$genreId." AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." group by v_id ORDER BY v_episode ASC LIMIT ".$totalVideoToReturn.";", 
										null,'json');
					$objVideo = json_decode($jsonVideo);
					$data[]=$objVideo->data->query_result;
				}
			}
			
		}

	}

	// TODO : fix the pagination here
	if(($start+1)<$count)
	{
		$nextPage=true;
	}
	else
	{
		$nextPage=false;
	}
	//$videoList=array("next_id"=>$lastFid[0], "data"=>$dataCat);
	$return["sta"] = "SUCCESS";
	$return["ret"]["dat"] = encrypt(json_encode(array("nextPage"=>$nextPage,"listVideo"=>$data)));
				
}

function check_session($conn, $sessionid)
{
	$json=$conn->doQuery("SELECT s_user_id from n_session where s_id=".$sessionid.";", null, 'json');
	//echo $json;
	return json_decode($json);
}
?>