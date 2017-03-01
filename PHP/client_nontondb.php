<?php

function GetClientAPI($jsonaction, $data)
{
	global $return;

	switch( (string)$jsonaction )
	{
		case "cl_get_banner_list":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			$temp_total_counts = GetBannersList($conn, true, null, -1, -1);
					
			if( $temp_total_counts > 0 )
			{
				$return["sta"] = "OK";
				$return["ret"]["msg"] = "BANNER LIST - SUCCEEDED";
			}
			else
			{
				$return["sta"] = "OK";
				$return["ret"]["msg"] = "BANNER LIST - NO ENTRY";
			}	
			//$temp_total_counts = GetVideoList($conn, true, $the_keyword, $the_limit_per_page, $the_page_number);
						
						
				
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}
		
		case "cl_get_initial_data":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			$temp_total_counts = GetVideoList($conn, true, null, 7, 0, false);

			$country = GetCountryNameFromIp("202.158.37.178");

			if( $temp_total_counts > 0 )
			{
				$return["sta"] = "OK";
				$return["ret"]["msg"] = "INITIAL DATA - SUCCEEDED";
			}
			else
			{
				$return["sta"] = "OK";
				$return["ret"]["msg"] = "INITIAL DATA - NO ENTRY";
			}	
						
			$encryptjson = encrypt(json_encode($return["ret"]["dat"]));
			$return["ret"]["dat"] = $encryptjson;
			echo stripslashes(json_encode($return));
			break;
		}
		
		case "cl_get_video_details":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			$the_video_id = -1;

			if(array_key_exists_r("vidid", $captureddata))
			{
				$the_video_id = (int)$captureddata->vidid;
			}
			else if( array_key_exists_r("frncsid|sesn|epsd", $captureddata))
			{
				$the_requested_franchise_id = (int)$captureddata->frncsid;
				$the_requested_season = (int)$captureddata->sesn;
				$the_requested_episode = (int)$captureddata->epsd;

				$json = $conn->doQuery("select `v_id` from `n_video` where `v_franchise_id`=? and `v_season`=? and `v_episode`=? LIMIT 1;",array($the_requested_franchise_id,$the_requested_season,$the_requested_episode),'json');
				$objUser = json_decode($json);

				if(!empty($objUser->data->query_result))
				{
					$the_video_id = (int)$objUser->data->query_result[0]->v_id;
				}
			}

			if( $the_video_id != -1 )
			{
				$json = $conn->doQuery("select `v_id`,`v_franchise_id`,`v_franchise_name`,`v_title`,`v_season`,`v_episode`,`v_year_production`,`v_synopsis`,`v_duration`,`v_director_id`,`v_producer_id`,`v_casts_id`,`v_director`,`v_producer`,`v_casts`,`v_url_poster`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_views`,`v_likes`,`v_dislikes`,`v_price`,`v_screenshot_url_1`,`v_screenshot_url_2`,`v_screenshot_url_3`,`v_screenshot_url_4`,`v_screenshot_url_5`,`v_is_active`,`v_uploader_admin_id`,`v_updater_admin_id`,`v_date_uploaded`,`v_last_updated` from `n_video` where `v_id`=? LIMIT 1;",array($the_video_id),'json');
				$objUser = json_decode($json);
			
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_franchise_id = (string)$objUser->data->query_result[0]->v_franchise_id;
					
					$jsonFranchise = $conn->doQuery("select `f_company_id`,`f_company`,`f_genre_id_1`,`f_genre_id_2`,`f_genre_id_3`,`f_genre_id_4`,`f_genre_id_5` from `n_franchise` where `f_id`=? LIMIT 1;",array($the_franchise_id),'json');
					$objUserFranchise = json_decode($jsonFranchise);
					
					if(!empty($objUserFranchise->data->query_result))
					{
						$return["ret"]["dat"]["list"]["video"] = GetVideoDetails($objUser, true, true, "");
					
						$temp_search_text = (string)$value->f_company_id;
						if( IsNullOrEmptyString($temp_search_text) )
						{
							$temp_search_text = "n/a";
						}
						$return["ret"]["dat"]["list"]["video"][] = $temp_search_text;
						
						$temp_search_text = (string)$value->f_company;
						if( IsNullOrEmptyString($temp_search_text) )
						{
							$temp_search_text = "n/a";
						}
						$return["ret"]["dat"]["list"]["video"][] = $temp_search_text;
						
						$return["ret"]["dat"]["list"]["video"][] = (string)$objUserFranchise->data->query_result[0]->f_genre_id_1 == "-1" ? "-2" : (string)$objUserFranchise->data->query_result[0]->f_genre_id_1;
						$return["ret"]["dat"]["list"]["video"][] = (string)$objUserFranchise->data->query_result[0]->f_genre_id_2 == "-1" ? "-2" : (string)$objUserFranchise->data->query_result[0]->f_genre_id_2;
						$return["ret"]["dat"]["list"]["video"][] = (string)$objUserFranchise->data->query_result[0]->f_genre_id_3 == "-1" ? "-2" : (string)$objUserFranchise->data->query_result[0]->f_genre_id_3;
						$return["ret"]["dat"]["list"]["video"][] = (string)$objUserFranchise->data->query_result[0]->f_genre_id_4 == "-1" ? "-2" : (string)$objUserFranchise->data->query_result[0]->f_genre_id_4;
						$return["ret"]["dat"]["list"]["video"][] = (string)$objUserFranchise->data->query_result[0]->f_genre_id_5 == "-1" ? "-2" : (string)$objUserFranchise->data->query_result[0]->f_genre_id_5;

						$the_season_details = array();
						// getting total season and episode details
						$jsonSeason = $conn->doQuery("select MAX(v_season) as `max` from `n_video` where `v_franchise_id`=?;",array($the_franchise_id),'json');
						$objSeason = json_decode($jsonSeason);
						$the_latest_season = $objSeason->data->query_result[0]->max;

						for( $the_temp_index = 1; $the_temp_index <= $the_latest_season; ++$the_temp_index )
						{
							$jsonEpisode = $conn->doQuery("select count(*) as `count` from `n_video` where `v_franchise_id`=? and `v_season`=?;",array($the_franchise_id, $the_temp_index),'json');
							$objEpisode = json_decode($jsonEpisode);
							$the_latest_episode = $objEpisode->data->query_result[0]->count;

							$the_season_details[] = (int)$the_temp_index;
							$the_season_details[] = (int)$the_latest_episode;
						}

						$return["ret"]["dat"]["list"]["season"] = $the_season_details;
						
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "GET VIDEO DETAILS SUCCESSFUL";
					}
					else
					{
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "GET VIDEO DETAILS - Something is not right, video has no franchise";
					}
					
				}
				else
				{
					// then who's trying to create the new entry?
					$return["ret"]["dat"]["message"]["title"] = "ERROR";
					$return["ret"]["dat"]["message"]["body"] = "Video doesn't exist!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "GET VIDEO DETAILS - Video ID DOESN'T EXIST";
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
		
		case "cl_get_related_videos":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("vidid|genre1|genre2|genre3|genre4|genre5", $captureddata))
			{
				$temp_total_counts = GetVideoListBasedOnGenre($conn, $captureddata, 7, 0, false);
						
				if( $temp_total_counts > 0 )
				{
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "GET RELATED VIDEOS - SUCCEEDED";
				}
				else
				{
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "GET RELATED VIDEOS - NO ENTRY";
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
		
		// despite the name, this function actually has many functionality:
		// 1. create new user from manual registration/fb connect
		// 2. connect available user with fb account
		// 3. update user info
		case "cl_user_create":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("uid", $captureddata))
			{
				$the_user_id = (string)$captureddata->uid;

				$json = $conn->doQuery("select `u_id` from `n_user` where `u_id`=? LIMIT 1;",array($the_user_id),'json');
					
				$objUser = json_decode($json);
					
				// if already exists, then we only want to update if necessary
				if(!empty($objUser->data->query_result))
				{
					GetPayPerView($conn, "-1", $the_user_id);
		
					$return["ret"]["dat"]["message"]["title"] = "UPDATED";
					$return["ret"]["dat"]["message"]["body"] = "User profile acquired successfully!";
						
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "USER ACQUIRE - SUCCESSFUL";

					GetUserInfo($conn, "-1", $the_user_id);
				}
				else 
				{
					$return["ret"]["dat"]["message"]["title"] = "ERROR";
					$return["ret"]["dat"]["message"]["body"] = "User profile id doesn't exist!";
							
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "USER ACQUIRE - FAILED";
				}
			}
			// creating a new user manually without using facebook 
			else if(array_key_exists_r("fbid|fname|uname|email|bday|gender", $captureddata))
			{
				$the_fb_id = (string)$captureddata->fbid;
				$the_fullname = (string)$captureddata->fname;
				$the_username = (string)$captureddata->uname;
				$the_email = (string)$captureddata->email;
				$the_bday = (string)$captureddata->bday;
				$the_gender = (string)$captureddata->gender;

				$json = $conn->doQuery("select `u_id` from `n_user` where `u_fbid`=? LIMIT 1;",array($the_fb_id),'json');
					
				$objUser = json_decode($json);

				$the_create_new_account = true;
					
				// if already exists, then we only want to update if necessary
				if(!empty($objUser->data->query_result))
				{
					$json = $conn->doQuery("update `n_user` set `u_fullname`=?,`u_last_login`=? where `u_fbid`=?;",array($the_fullname,$conn->getDateTimeNow(),$the_fb_id),'json');
					
					$objUser = json_decode($json);

					GetPayPerView($conn, $the_fb_id);
		
					// if update succeeded
					if(strcmp($objUser->data->result,"ok")==0)
					{
						$return["ret"]["dat"]["message"]["title"] = "UPDATED";
						$return["ret"]["dat"]["message"]["body"] = "User profile updated successfully!";
						
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "USER UPDATE - SUCCESSFUL";
					}
					else
					{
						$return["ret"]["dat"]["message"]["title"] = "ERROR";
						$return["ret"]["dat"]["message"]["body"] = "User profile failed to update!";
						
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "USER UPDATE - FAILED";
					}

					GetUserInfo($conn, $the_fb_id);

					$the_create_new_account = false;
				}
				else 
				{
					// if this is empty, that means user doesn't exist
					// but we want to check the username first, maybe the person wants to connect his current account
					// with his fb account
					$json = $conn->doQuery("select `u_id` from `n_user` where `u_username`=? LIMIT 1;",array($the_username),'json');

					$objUser = json_decode($json);

					// if already exists, then we only want to update if necessary
					if(!empty($objUser->data->query_result))
					{
						$json = $conn->doQuery("update `n_user` set `u_fbid`=?,`u_fullname`=?,`u_birthday`=?,`u_gender`=?,`u_last_login`=? where `u_username`=?;",array($the_fb_id,$the_fullname,$the_bday,$the_gender,$conn->getDateTimeNow(),$the_username),'json');
						
						$objUser = json_decode($json);

						GetPayPerView($conn, $the_fb_id);
			
						// if update succeeded
						if(strcmp($objUser->data->result,"ok")==0)
						{
							$return["ret"]["dat"]["message"]["title"] = "UPDATED";
							$return["ret"]["dat"]["message"]["body"] = "User profile updated successfully!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "USER UPDATE - SUCCESSFUL";
						}
						else
						{
							$return["ret"]["dat"]["message"]["title"] = "ERROR";
							$return["ret"]["dat"]["message"]["body"] = "User profile failed to update!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "USER UPDATE - FAILED";
						}

						GetUserInfo($conn, $the_fb_id);

						$the_create_new_account = false;
					}
				}
				
				// it would never go here if it any of the above, unless there really is no existing user with this username or fbid
				if( $the_create_new_account == true )
				{
					$json = $conn->doQuery("insert into `n_user` (`u_fbid`,`u_fullname`,`u_username`,`u_email`,`u_birthday`,`u_gender`,`u_registration_date`,`u_last_login`,`u_subscription_start`,`u_subscription_end`) values (?,?,?,?,?,?,?,?,?,?);",array($the_fb_id,$the_fullname,$the_username,$the_email,$the_bday,$the_gender,$conn->getDateTimeNow(), $conn->getDateTimeNow(), $conn->getDateTimeNow(), $conn->getDateTimeNow()),'json');
						
					$objUser = json_decode($json);

					// if creation succeeded
					if(strcmp($objUser->data->result,"ok")==0)
					{
						$return["ret"]["dat"]["message"]["title"] = "CREATED";
						$return["ret"]["dat"]["message"]["body"] = "New user created successfully!";
						
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "USER CREATION - SUCCESSFUL";

						GetUserInfo($conn, $the_fb_id);
					}
					else
					{
						$return["ret"]["dat"]["message"]["title"] = "ERROR";
						$return["ret"]["dat"]["message"]["body"] = "User creation failed. Please try again!";
					
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "USER CREATION - FAILED TO INSERT NEW ENTRY TO DATABASE";
					}
				}
			}
			// creating a new user manually without using facebook 
			else if(array_key_exists_r("fname|uname|email|pword", $captureddata))
			{
				$the_fullname = (string)$captureddata->fname;
				$the_username = (string)$captureddata->uname;
				$the_email = (string)$captureddata->email;
				$the_password = hash_hmac('sha512', 'salt' . (string)$captureddata->pword, "nontondotcom");
				
				$json = $conn->doQuery("select `u_id`,`u_username`,`u_email` from `n_user` where `u_username`=? and `u_password`=? LIMIT 1;",array($the_username, $the_password),'json');
					
				$objUser = json_decode($json);

				if(empty($objUser->data->query_result))
				{
					$json = $conn->doQuery("select `u_id`,`u_username`,`u_email` from `n_user` where `u_email`=? and `u_password`=? LIMIT 1;",array($the_username, $the_password),'json');
					
					$objUser = json_decode($json);
				}

				// if already exists, then we only want to update if necessary
				if(!empty($objUser->data->query_result))
				{
					$the_user_id = (string)$objUser->data->query_result[0]->u_id;

					GetPayPerView($conn, "-1", $the_user_id);
		
					// if update succeeded
					if(strcmp($objUser->data->result,"ok")==0)
					{
						$return["ret"]["dat"]["message"]["title"] = "UPDATED";
						$return["ret"]["dat"]["message"]["body"] = "User profile updated successfully!";
						
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "USER LOGIN - SUCCESSFUL";
					}
					else
					{
						$return["ret"]["dat"]["message"]["title"] = "ERROR";
						$return["ret"]["dat"]["message"]["body"] = "User profile failed to update!";
						
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "USER LOGIN - FAILED";
					}

					GetUserInfo($conn, "-1", $the_user_id);
				}
				else
				{
					if( IsNullOrEmptyString($the_fullname) || IsNullOrEmptyString($the_email) )
					{
						$return["ret"]["dat"]["message"]["title"] = "ERROR";
						$return["ret"]["dat"]["message"]["body"] = "Wrong username/email or password combination to update!";
						
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "USER LOGIN - FAILED";
					}
					else
					{
						$json = $conn->doQuery("insert into `n_user` (`u_fbid`,`u_fullname`,`u_username`,`u_password`,`u_email`,`u_birthday`,`u_registration_date`,`u_last_login`,`u_subscription_start`,`u_subscription_end`) values (?,?,?,?,?,?,?,?,?,?);",array("-1",$the_fullname,$the_username,$the_password,$the_email,$conn->getDateTimeNow(),$conn->getDateTimeNow(), $conn->getDateTimeNow(), $conn->getDateTimeNow(), $conn->getDateTimeNow()),'json');
							
						$objUser = json_decode($json);

						// if creation succeeded
						if(strcmp($objUser->data->result,"ok")==0)
						{
							$json = $conn->doQuery("select `u_id` from `n_user` where `u_username`=? LIMIT 1;",array($the_username),'json');
								
							$objUser = json_decode($json);

							// if already exists, then we only want to update if necessary
							if(!empty($objUser->data->query_result))
							{
								$the_user_id = (string)$objUser->data->query_result[0]->u_id;

								$return["ret"]["dat"]["message"]["title"] = "CREATED";
								$return["ret"]["dat"]["message"]["body"] = "New user created successfully!";
								
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "USER CREATION - SUCCESSFUL";

								GetUserInfo($conn, "-1", $the_user_id);
							}
							else
							{
								$return["ret"]["dat"]["message"]["title"] = "ERROR";
								$return["ret"]["dat"]["message"]["body"] = "User creation failed. Please try again!";
							
								$return["sta"] = "OK";
								$return["ret"]["msg"] = "USER CREATION - FAILED TO GET NEWLY INSERTED DATA";
							}
						}
						else
						{
							$return["ret"]["dat"]["message"]["title"] = "ERROR";
							$return["ret"]["dat"]["message"]["body"] = "User creation failed. Please try again!";
						
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "USER CREATION - FAILED TO INSERT NEW ENTRY TO DATABASE";
						}
					}
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

		case "cl_user_edit":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("fbid|uname|email|bday|gender|phone|avtrurl", $captureddata))
			{
				$the_fb_id = (string)$captureddata->fbid;
				$the_username = (string)$captureddata->uname;
				$the_email = (string)$captureddata->email;
				$the_bday = (string)$captureddata->bday;
				$the_gender = (string)$captureddata->gender;
				$the_phone = (string)$captureddata->phone;
				$the_avatar_url = (string)$captureddata->avtrurl;

				$json = $conn->doQuery("select `u_username`,`u_birthday`,`u_gender`,`u_id`, `u_email`,`u_phone`,`u_avatar_url`,`u_is_active` from `n_user` where `u_fbid`=? LIMIT 1;",array($the_fb_id),'json');
					
				$objUser = json_decode($json);
					
				// if already exists, then we only want to update if necessary
				if(!empty($objUser->data->query_result))
				{
					$json = $conn->doQuery("update `n_user` set `u_username`=?,`u_email`=?,`u_birthday`=?,`u_gender`=?,`u_phone`=?,`u_avatar_url`=?,`u_last_login`=? where `u_fbid`=?;",array($the_username,$the_email,$the_bday,$the_gender,$the_phone,$the_avatar_url,$conn->getDateTimeNow(),$the_fb_id),'json');
					
					$objUser = json_decode($json);
		
					// if update succeeded
					if(strcmp($objUser->data->result,"ok")==0)
					{
						$return["ret"]["dat"]["message"]["title"] = "UPDATED";
						$return["ret"]["dat"]["message"]["body"] = "User profile updated successfully!";
						
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "USER UPDATE - SUCCESSFUL";
					}
					else
					{
						$return["ret"]["dat"]["message"]["title"] = "ERROR";
						$return["ret"]["dat"]["message"]["body"] = "User profile failed to update!";
						
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "USER UPDATE - FAILED";
					}

					GetUserInfo($conn, $the_fb_id);
				}
				else
				{
					$return["ret"]["dat"]["message"]["title"] = "ERROR";
					$return["ret"]["dat"]["message"]["body"] = "User doesn't exist. Please use the register button.";
				
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "USER UPDATE - USER DOESN'T EXIST";
				}
			}
			else if(array_key_exists_r("uid|fname|uname|email|bday|gender|phone|avtrurl", $captureddata))
			{
				$the_fullname = (string)$captureddata->fname;
				$the_username = (string)$captureddata->uname;
				$the_email = (string)$captureddata->email;
				$the_bday = (string)$captureddata->bday;
				$the_gender = (string)$captureddata->gender;
				$the_phone = (string)$captureddata->phone;
				$the_avatar_url = (string)$captureddata->avtrurl;
				$the_user_id = (string)$captureddata->u_id;
	
				$json = $conn->doQuery("select `u_username`,`u_birthday`,`u_gender`,`u_id`, `u_email`,`u_phone`,`u_avatar_url`,`u_is_active` from `n_user` where `u_username`=? and `u_id`=? LIMIT 1;",array($the_username,$the_user_id),'json');
					
				$objUser = json_decode($json);
					
				// if already exists, then we only want to update if necessary
				if(!empty($objUser->data->query_result))
				{
					$json = $conn->doQuery("update `n_user` set `u_fullname`=?,`u_email`=?,`u_birthday`=?,`u_gender`=?,`u_phone`=?,`u_avatar_url`=?,`u_last_login`=? where `u_id`=?;",array($the_fullname,$the_email,$the_bday,$the_gender,$the_phone,$the_avatar_url,$conn->getDateTimeNow(),$the_user_id),'json');
					
					$objUser = json_decode($json);
		
					// if update succeeded
					if(strcmp($objUser->data->result,"ok")==0)
					{
						$return["ret"]["dat"]["message"]["title"] = "UPDATED";
						$return["ret"]["dat"]["message"]["body"] = "User profile updated successfully!";
						
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "USER UPDATE - SUCCESSFUL";
					}
					else
					{
						$return["ret"]["dat"]["message"]["title"] = "ERROR";
						$return["ret"]["dat"]["message"]["body"] = "User profile failed to update!";
						
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "USER UPDATE - FAILED";
					}

					GetUserInfo($conn, "-1", $the_user_id);
				}
				else
				{
					$return["ret"]["dat"]["message"]["title"] = "ERROR";
					$return["ret"]["dat"]["message"]["body"] = "User doesn't exist. Please use the register button.";
				
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "USER UPDATE - USER DOESN'T EXIST";
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

		case "cl_user_change_username":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("uname|pword|newuname", $captureddata))
			{
				$the_new_username = (string)$captureddata->newuname;
				$the_username = (string)$captureddata->uname;
				$the_password = hash_hmac('sha512', 'salt' . (string)$captureddata->pword, "nontondotcom");

				$json = $conn->doQuery("select `u_id` from `n_user` where `u_username`=? and `u_password`=? LIMIT 1;",array($the_new_username, $the_password),'json');

				$objUser = json_decode($json);

				// if the new username exists then we cant change it to this name
				if(!empty($objUser->data->query_result))
				{
					$return["ret"]["dat"]["message"]["title"] = "FAILED";
					$return["ret"]["dat"]["message"]["body"] = "New username already exists!";
					
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "USERNAME CHANGE - FAILED";
				}
				else
				{
					$json = $conn->doQuery("select `u_id` from `n_user` where `u_username`=? and `u_password`=? LIMIT 1;",array($the_username, $the_password),'json');

					$objUser = json_decode($json);
						
					// if exists, then we want to change the username
					if(!empty($objUser->data->query_result))
					{
						$the_user_id = (string)$objUser->data->query_result[0]->u_id;

						$json = $conn->doQuery("update `n_user` set `u_username`=? where `u_id`=?;",array($the_new_username,$the_user_id),'json');
						
						$objUser = json_decode($json);
			
						// if update succeeded
						if(strcmp($objUser->data->result,"ok")==0)
						{
							$return["ret"]["dat"]["message"]["title"] = "UPDATED";
							$return["ret"]["dat"]["message"]["body"] = "Username updated successfully!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "USERNAME CHANGE - SUCCESSFUL";
						}
						else
						{
							$return["ret"]["dat"]["message"]["title"] = "ERROR";
							$return["ret"]["dat"]["message"]["body"] = "Username failed to be updated!";
							
							$return["sta"] = "OK";
							$return["ret"]["msg"] = "USERNAME CHANGE - FAILED";
						}

						GetUserInfo($conn, "-1", $the_user_id);
					}
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

		case "cl_user_change_password":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("uname|pword|newpword", $captureddata))
			{
				$the_username = (string)$captureddata->uname;
				$the_password = hash_hmac('sha512', 'salt' . (string)$captureddata->pword, "nontondotcom");
				$the_new_password = hash_hmac('sha512', 'salt' . (string)$captureddata->newpword, "nontondotcom");

				$json = $conn->doQuery("select `u_id` from `n_user` where `u_username`=? and `u_password`=? LIMIT 1;",array($the_username, $the_password),'json');

				$objUser = json_decode($json);
					
				// if exists, then we want to change the password
				if(!empty($objUser->data->query_result))
				{
					$the_user_id = (string)$objUser->data->query_result[0]->u_id;

					$json = $conn->doQuery("update `n_user` set `u_password`=? where `u_username`=? and `u_id`=?;",array($the_new_password,$the_username,$the_user_id),'json');
					
					$objUser = json_decode($json);
		
					// if update succeeded
					if(strcmp($objUser->data->result,"ok")==0)
					{
						$return["ret"]["dat"]["message"]["title"] = "UPDATED";
						$return["ret"]["dat"]["message"]["body"] = "User password updated successfully!";
						
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "USER CHANGE PASSWORD - SUCCESSFUL";
					}
					else
					{
						$return["ret"]["dat"]["message"]["title"] = "ERROR";
						$return["ret"]["dat"]["message"]["body"] = "User password failed to be updated!";
						
						$return["sta"] = "OK";
						$return["ret"]["msg"] = "USER CHANGE PASSWORD - FAILED";
					}

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

		case "cl_user_add_points":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("fbid|pts|ssid", $captureddata))
			{
				$the_fb_id = (string)$captureddata->fbid;
				$the_points = (string)$captureddata->pts;
				$the_session_id = (string)$captureddata->ssid;

				if( AddPoints($conn, $the_fb_id, $the_points, "-1", $the_session_id) )
				{
					GetUserInfo($conn, $the_fb_id);
				}
			}
			else if(array_key_exists_r("uid|pts|ssid", $captureddata))
			{
				$the_user_id = (string)$captureddata->uid;
				$the_points = (string)$captureddata->pts;
				$the_session_id = (string)$captureddata->ssid;

				if( AddPoints($conn, "-1", $the_points, $the_user_id, $the_session_id) )
				{
					GetUserInfo($conn, "-1",$the_user_id);
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

		case "cl_user_add_subscription":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("fbid|type|ssid", $captureddata))
			{
				$the_fb_id = (string)$captureddata->fbid;
				$the_subscription_type = (string)$captureddata->type;
				$the_session_id = (string)$captureddata->ssid;

				if( AddSubscription($conn, $the_fb_id, $the_subscription_type, "-1", $the_session_id) )
				{
					GetUserInfo($conn, $the_fb_id);
				}
			}
			else if(array_key_exists_r("uid|type|ssid", $captureddata))
			{
				$the_user_id = (string)$captureddata->uid;
				$the_subscription_type = (string)$captureddata->type;
				$the_session_id = (string)$captureddata->ssid;

				if( AddSubscription($conn, "-1", $the_subscription_type, $the_user_id, $the_session_id) )
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
		
		case "cl_user_pay_video":
		{
			$conn = new database();
					
            $return["evn"] = (string)$jsonaction;
			$captureddata = json_decode(decrypt($data->data));
			
			if(array_key_exists_r("fbid|vidid|vidname|ssid", $captureddata))
			{
				$the_fb_id = (string)$captureddata->fbid;
				$the_video_id = (string)$captureddata->vidid;
				$the_video_name = (string)$captureddata->vidname;
				$the_session_id = (string)$captureddata->ssid;

				if( AddPayPerView($conn, $the_fb_id, $the_video_id, $the_video_name, "-1", $the_session_id) )
				{
					GetUserInfo($conn, $the_fb_id);

					GetPayPerView($conn, $the_fb_id);
				}
			}
			else if(array_key_exists_r("uid|vidid|vidname|ssid", $captureddata))
			{
				$the_user_id = (string)$captureddata->uid;
				$the_video_id = (string)$captureddata->vidid;
				$the_video_name = (string)$captureddata->vidname;
				$the_session_id = (string)$captureddata->ssid;

				if( AddPayPerView($conn, "-1", $the_video_id, $the_video_name, $the_user_id, $the_session_id) )
				{
					GetUserInfo($conn, "-1", $the_user_id);

					GetPayPerView($conn, "-1", $the_user_id);
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
		
		default:
		{
			GetClientExtraAPI($jsonaction, $data);
			break;
		}	
	}
}

?>
