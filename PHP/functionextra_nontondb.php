<?php
function get_initial_content($conn, $app_signature, $country_name, $sessionid)
{
	global $return;
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
			// TODO : remove hard code
			// TODO : Create a function that handle this piece of code so no code duplication
			// TODO : Limit to 6 if the total video is 6 or greater
			// TODO : Limit to 3 if the total video is less than 6
			// TODO : Don't show any banner if the total video is 0
			// TODO : The error check for $array3 should be done for $array1 and $array2 as well

			$json1 = $conn->doQuery("SELECT `v_id`,`v_franchise_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_url_poster`, `v_url_poster_landscape`, `t_name`, `t_id`, `t_url_poster_landscape` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=1 ORDER BY v_last_updated DESC LIMIT 6;", 
										null,'json');
			$json2 = $conn->doQuery("SELECT `v_id`,`v_franchise_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_url_poster`, `v_url_poster_landscape`, `t_name`, `t_id`, `t_url_poster_landscape` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=2 ORDER BY v_last_updated DESC LIMIT 6;", 
										null,'json');
			$json3 = $conn->doQuery("SELECT `v_id`,`v_franchise_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_url_poster`, `v_url_poster_landscape`, `t_name`, `t_id`, `t_url_poster_landscape` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=3 ORDER BY v_last_updated DESC LIMIT 6;", 
										null,'json');
			$objVideo1 = json_decode($json1);
			$objVideo2 = json_decode($json2);
			$objVideo3 = json_decode($json3);
			$count=0;
			$lpgroup=0;
			$data1=array();
			foreach($objVideo1->data->query_result as $row)
			{
				$data1[]=array(
								"v_id"=> $row->v_id,
								"v_franchise_id"=> $row->v_franchise_id,
								"v_title"=> $row->v_title,
								"v_url_youtube_id"=> $row->v_url_youtube_id,
								"v_url_cdn"=> $row->v_url_cdn,
								"v_prioritize_youtube"=> $row->v_prioritize_youtube,
								"v_url_poster"=> $row->v_url_poster,
								"v_url_poster_landscape"=> $row->v_url_poster_landscape,
								"t_name"=> $row->t_name
								);
				$count++;
			}
			
			$data2=array();
			foreach($objVideo2->data->query_result as $row)
			{
				$data2[]=array(
								"v_id"=> $row->v_id,
								"v_franchise_id"=> $row->v_franchise_id,
								"v_title"=> $row->v_title,
								"v_url_youtube_id"=> $row->v_url_youtube_id,
								"v_url_cdn"=> $row->v_url_cdn,
								"v_prioritize_youtube"=> $row->v_prioritize_youtube,
								"v_url_poster"=> $row->v_url_poster,
								"v_url_poster_landscape"=> $row->v_url_poster_landscape,
								"t_name"=> $row->t_name					
								);
				$count++;
			}
			
			$data3=array();
			foreach($objVideo3->data->query_result as $row)
			{
				$data3[]=array(
								"v_id"=> $row->v_id,
								"v_franchise_id"=> $row->v_franchise_id,
								"v_title"=> $row->v_title,
								"v_url_youtube_id"=> $row->v_url_youtube_id,
								"v_url_cdn"=> $row->v_url_cdn,
								"v_prioritize_youtube"=> $row->v_prioritize_youtube,
								"v_url_poster"=> $row->v_url_poster,
								"v_url_poster_landscape"=> $row->v_url_poster_landscape,
								"t_name"=> $row->t_name
								);
				$count++;
			}

			$array1=array('t_name'=>$objVideo1->data->query_result[0]->t_name,
						  't_banner'=>$objVideo1->data->query_result[0]->t_url_poster_landscape,
						  't_id'=>$objVideo1->data->query_result[0]->t_id,
						  'session_id'=>$sessionid,
						  'data'=>$data1);

			$array2=array('t_name'=>$objVideo2->data->query_result[0]->t_name,
						 't_banner'=>$objVideo2->data->query_result[0]->t_url_poster_landscape,
						 't_id'=>$objVideo2->data->query_result[0]->t_id,
						 'session_id'=>$sessionid,
						  'data'=>$data2);

			$array3=array('t_name'=> is_null($objVideo3->data->query_result[0]->t_name) ? '' : $objVideo3->data->query_result[0]->t_name ,
						  't_banner'=>is_null($objVideo3->data->query_result[0]->t_url_poster_landscape) ? '' : $objVideo3->data->query_result[0]->t_url_poster_landscape ,
						  't_id'=>is_null($objVideo3->data->query_result[0]->t_id) ? '' : $objVideo3->data->query_result[0]->t_id,
						  'session_id'=>$sessionid,
						  'data'=>$data3);

			$return["sta"] = "SUCCESS";
			$return["ret"]["dat"] = encrypt(json_encode(array(/*'session_id'=>$sessionid,*/$array1, $array2, $array3)));;
				
			//echo json_encode($objVideo3->data->query_result);
		}
	}
	else
	{
		$return["sta"] = "FAIL";
		$return["ret"]["msg"] = "APP SIGNATURE NO LONGER EXISTS";
	}
}

function get_video_list($conn, $limit, $franchiseid, $category,$country_name)
{
	global $return;
	$the_search_result = array();
			// TODO : why are you checking franchise id, why did you put this line below "and f_id > ".$franchiseid."
			// shouldn't we only check the type and return every franchise with this type? Maybe I'm understanding this code wrong, let me know

			// TODO : where is the pagination? There is only a limit set but no indication where to start counting

			$json = $conn->doQuery("SELECT `f_id`, `f_url_poster_landscape`, `t_name`  
												FROM  `n_franchise` f, `n_type` t WHERE t.t_id =f.f_type_id AND t.t_id=".$category." and f_id > ".$franchiseid." ORDER BY f_date_added DESC LIMIT ".$limit.";", 
										null,'json');
			$objFranchise = json_decode($json);
			$banner=null;
			$dataTotal=null;
			$dataCat=array();
			$lastFid=array();
			foreach($objFranchise->data->query_result as $row)
			{
				$json = $conn->doQuery("SELECT distinct(`v_id`),`v_franchise_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_url_poster`, `v_url_poster_landscape` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=".$row->f_id." AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') ORDER BY v_last_updated DESC;", 
									    null,'json');
				$objVideo = json_decode($json);
				$dataVid=array();
					foreach($objVideo->data->query_result as $rows)
					{
						$dataVid[]=array(
								"v_id"=> $rows->v_id,
								"v_franchise_id"=> $rows->v_franchise_id,
								"v_title"=> $rows->v_title,
								"v_url_youtube_id"=> $rows->v_url_youtube_id,
								"v_url_cdn"=> $rows->v_url_cdn,
								"v_prioritize_youtube"=> $rows->v_prioritize_youtube,
								"v_url_poster"=> $rows->v_url_poster,
								"v_url_poster_landscape"=> $rows->v_url_poster_landscape
								);
					}
				$dataCat[]=array(
								"f_id"=> $row->f_id,
								"f_url_poster_landscape"=> $row->f_url_poster_landscape,
								"t_name"=>$row->t_name,
								"data_vid"=>$dataVid);	
				if($lastFid[0]<$row->f_id)
					$lastFid[0]=$row->f_id;
			}
			
			$videoList=array("next_id"=>$lastFid[0], "data"=>$dataCat);
			$return["sta"] = "SUCCESS";
			$return["ret"]["dat"] = encrypt(json_encode($videoList));
				
}

function search_franchise($conn, $kwd, $country_name, $start)
{
	global $return;

	// TODO : I might have understood it wrong, but this code right here checks the genre
	// while for search, you need to check if the TITLE and ACTOR contains specific keywords
	$json = $conn->doQuery("SELECT count(*) total from n_franchise f 
							LEFT JOIN n_genre as g1 on f_genre_id_1=g1.g_id 
							LEFT JOIN n_genre as g2 on f_genre_id_2=g2.g_id 
							LEFT JOIN n_genre as g3 on f_genre_id_3=g3.g_id 
							LEFT JOIN n_genre as g4 on f_genre_id_4=g4.g_id 
							LEFT JOIN n_genre as g5 on f_genre_id_5=g5.g_id
								and upper(f_country_access) like '%".strtoupper($country_name)."%';", 
										null,'json');
	$count = json_decode($json)->data->query_result[0]->total;
	$json = $conn->doQuery("SELECT f_id, f_url_poster, f_url_poster_landscape, f_synopsis, f_name, f_company, ifnull(g1.g_id, '') g_id1, ifnull(g1.g_name, '') g_name1, ifnull(g2.g_id, '') g_id2,  ifnull(g2.g_name, '') g_name2, ifnull(g3.g_id,'') g_id3, ifnull(g3.g_name,'') g_name3, ifnull(g4.g_id,'') g_id4,   ifnull(g4.g_name,'') g_name4,ifnull(g5.g_id, '') g_id5,  ifnull(g5.g_name, '') g_name5
 from n_franchise f 
							LEFT JOIN n_genre as g1 on f_genre_id_1=g1.g_id 
							LEFT JOIN n_genre as g2 on f_genre_id_2=g2.g_id 
							LEFT JOIN n_genre as g3 on f_genre_id_3=g3.g_id 
							LEFT JOIN n_genre as g4 on f_genre_id_4=g4.g_id 
							LEFT JOIN n_genre as g5 on f_genre_id_5=g5.g_id
								and upper(f_name) like '%".strtoupper($kwd)."%' and upper(f_country_access) like '%".strtoupper($country_name)."%'
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

	// TODO : Auto complete needs to search from Franchise name, Video name and Actor name
	$json = $conn->doQuery("SELECT `f_name`,`v_franchise_id` FROM `n_video` v, `n_franchise` f WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND upper(f_name) like '%".strtoupper($kwd)."%' ORDER BY v_last_updated DESC LIMIT 10;", 
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

function get_video_detail($conn, $activityId=null, $videoId=null, $duration=null,$nShares=null, $resolution=null, $timeEnd=null, $favorite)
{
	global $return;

	// TODO : Insert to n_activity table

	$json=$conn->doQuery("select v_id, t_name, v_title, f_genre_id_1, f_genre_id_2,f_genre_id_3,f_genre_id_4,f_genre_id_5,f_company, v_franchise_id, v_synopsis, v_url_youtube_id,v_season, v_episode, v_year_production, v_director, v_casts, v_price from n_video v, n_franchise f, n_type t where t.t_id =f.f_type_id and v_id=".$videoId." and v.v_franchise_id=f.f_id",
						null,'json');
	$objVideoDetail=json_decode($json);
	$json=$conn->doQuery("select v_id, v_title, v_url_poster, v_url_poster_landscape,v_season, v_episode, v_year_production, v_director, v_casts, v_price from n_video where v_franchise_id=".$objVideoDetail->data->query_result[0]->v_franchise_id." and v_season=".$objVideoDetail->data->query_result[0]->v_season,
						null,'json');
	$objEpisode=json_decode($json);
	$json=$conn->doQuery("select distinct(v_id) v_id, v_franchise_id, v_title, v_url_youtube_id,v_url_cdn, v_prioritize_youtube, v_url_poster, v_url_poster_landscape from n_video v, n_franchise f where v.v_franchise_id=f.f_id and f_genre_id_1 in (".$objVideoDetail->data->query_result[0]->f_genre_id_1.",".$objVideoDetail->data->query_result[0]->f_genre_id_2.", ".$objVideoDetail->data->query_result[0]->f_genre_id_3.",".$objVideoDetail->data->query_result[0]->f_genre_id_4.",".$objVideoDetail->data->query_result[0]->f_genre_id_5.") 
							or f_genre_id_2 in (".$objVideoDetail->data->query_result[0]->f_genre_id_1.",".$objVideoDetail->data->query_result[0]->f_genre_id_2.", ".$objVideoDetail->data->query_result[0]->f_genre_id_3.",".$objVideoDetail->data->query_result[0]->f_genre_id_4.",".$objVideoDetail->data->query_result[0]->f_genre_id_5.") 
							or f_genre_id_3 in (".$objVideoDetail->data->query_result[0]->f_genre_id_1.",".$objVideoDetail->data->query_result[0]->f_genre_id_2.", ".$objVideoDetail->data->query_result[0]->f_genre_id_3.",".$objVideoDetail->data->query_result[0]->f_genre_id_4.",".$objVideoDetail->data->query_result[0]->f_genre_id_5.") 
							or f_genre_id_4 in (".$objVideoDetail->data->query_result[0]->f_genre_id_1.",".$objVideoDetail->data->query_result[0]->f_genre_id_2.", ".$objVideoDetail->data->query_result[0]->f_genre_id_3.",".$objVideoDetail->data->query_result[0]->f_genre_id_4.",".$objVideoDetail->data->query_result[0]->f_genre_id_5.") 
							or f_genre_id_5 in (".$objVideoDetail->data->query_result[0]->f_genre_id_1.",".$objVideoDetail->data->query_result[0]->f_genre_id_2.", ".$objVideoDetail->data->query_result[0]->f_genre_id_3.",".$objVideoDetail->data->query_result[0]->f_genre_id_4.",".$objVideoDetail->data->query_result[0]->f_genre_id_5.") 
							and v_id !=".$objVideoDetail->data->query_result[0]->v_id,
						null,'json');
	$objSimiliarVideo=json_decode($json);
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
	  "views"=>"4",
	  "favorite"=>$favorite,
	  "similiar_video"=>$objSimiliarVideo->data->query_result,
	  "episode"=> $objEpisode->data->query_result
	  );
	//array_push($objVideoDetail->data->query_result, $objEpisode->data->query_result);
	$return["sta"] = "SUCCESS";

	// TODO : what is this activity id 23? why is it hard coded?
	$return["ret"]["dat"] = encrypt(json_encode(array("activityId"=>"23","video"=>$data)));;
	
}

function insert_activity($conn, $videoId, $duration,$nShares, $resolution, $timeEnd )
{
	$json=$conn->doQuery("insert into n_activity (av_session_id, av_video_id, av_video_name) values (".$sessionid.",".$videoId.",".$videoName.");",
						null, 'json');
	return json_decode($json);					
}

function update_activity()
{
	$json=$conn->doQuery("update n_activity set av_duration=".$duration.", av_share=".$share.", av_resolution=".$resolution." where av_id=".$av_id,
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
	// TODO : replied_to_id should be null or 0 if it's not replyign to any comment, right now in the database everything is replying to id 1
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
		//echo "test";
	}
	else
	{
		$json = $conn->doQuery("insert into n_user (U_fbid, U_device_id, U_referral_id,U_username, U_password, U_fullname,U_gender,U_birthday, U_points, u_avatar_url, u_email) 
								values ('".facebookid."','".$deviceid."','".$referalid."','".$username."','".$password."','".fullname."','".$gender."',null,10, '".$photourl."', '".$email."');", 
											null,'json');
		//echo json_encode($json);
		// TODO : dont hardcode points, put it in variable
		$insert=json_decode($json);
		$data=array("u_id"=>$insert->data->query_id,
					"points"=>10
				);
	
		$return["sta"] = "SUCCESS";
		$return["ret"]["dat"] = encrypt(json_encode($data));
	}
		
}

function load_user($conn, $uname, $password, $facebookid)
{
	global $return;
	if($email == '')
	{
		$json = $conn->doQuery("SELECT `u_id`, `u_points` FROM `n_user` u WHERE `u_fbid` = '".$facebookid."';", 
										null,'json');
		$objUser = json_decode($json);
	}
	else
	{
		$json = $conn->doQuery("SELECT `u_id`, `u_points` FROM `n_user` u WHERE `u_email` = '".$uname."' and u_password='".$password."';", 
										null,'json');
		$objUser = json_decode($json);
	}
	if($objUser->data->query_result[0]==null)
	{
		$return["sta"] = "EMAIL/PASSWORD NOT VALID";
	}
	else
	{
		$return["sta"] = "SUCCESS";
	}		
		$return["ret"]["dat"] = encrypt(json_encode($objUser->data->query_result[0]));
	
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
		$amount=null;
	}
	else
	{
		$point=null;
		$amount=$video->data->query_result[0]->v_price;
	}
		
	$json = $conn->doQuery("insert into n_pay_per_view (ppv_user_id, ppv_video_id, ppv_session_id,ppv_points_used, ppv_amount_paid) 
							values ('".$userid."','".$videoid."','".$sid."','".$point."', '".$amount."');", 
										null,'json');
	
	$json = $conn->doQuery("SELECT `u_id`, `u_points` FROM `n_user` u WHERE `u_username` = '".$userid."';", 
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
		$date=date('Y-m-d', strtotime(' +7 day'));
	else
		$date=date('Y-m-d', strtotime(' +2 month'));
	$json = $conn->doQuery("insert into n_pay_subscription (ps_user_id, ps_session_id,ps_type) 
							values ('".$userid."','".$sid."','".$type."');", 
										null,'json');
	
	$json = $conn->doQuery("SELECT `u_id`, `u_points` FROM `n_user` u WHERE `u_username` = '".$userid."';", 
										null,'json');
	$user=json_decode($json);
	$data=array(
				"u_points"=>$user->data->query_result[0]->u_points,
				//"videoid"=>$videoid,
				"expirationdate"=>$date
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
?>