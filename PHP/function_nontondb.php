<?php

// $conn = connection to the db
// $called_by_client = true if called by nonton app/web, false if called by cms
// $the_keyword = keyword for listing filtered content
// $the_limit_per_page = display limit per page
// $the_page_number = current page number
function GetBannersList($conn, $called_by_client, $the_keyword, $the_limit_per_page, $the_page_number)
{
	global $return;
	$the_search_result = array();
	
	if( $called_by_client == true )
	{
		$json = $conn->doQuery("select count(*) as `count` from `n_banner` where `b_id`>=? and `b_is_active`=?;",array(1,1),'json');
		$objUser = json_decode($json);
		
		$temp_total_counts = $objUser->data->query_result[0]->count;
						
		$json = $conn->doQuery("select `b_id`,`b_title`,`b_franchise_id`,`b_franchise`,`b_video_id`,`b_video`,`b_desc`,`b_url_logo`,`b_url_background`,`b_url_link`,`b_is_active` from `n_banner` where `b_id`>=? and `b_is_active`=? ORDER BY `b_title` ASC ;",array(1, 1),'json');
		$objUser = json_decode($json);
	}
	else
	{
		$json = $conn->doQuery("select count(*) as `count` from `n_banner` where `b_id`>=?;",array(1),'json');
		$objUser = json_decode($json);
		
		$temp_total_counts = $objUser->data->query_result[0]->count;
						
		$json = $conn->doQuery("select `b_id`,`b_title`,`b_franchise_id`,`b_franchise`,`b_video_id`,`b_video`,`b_desc`,`b_url_logo`,`b_url_background`,`b_url_link`,`b_creator_id`,`b_is_active`,`b_date_added`,`b_last_updated` from `n_banner` where `b_id`>=? ORDER BY `b_title` ASC LIMIT ? OFFSET ?;",array(1, $the_limit_per_page,$the_page_number),'json');
		$objUser = json_decode($json);
		
		if( $the_keyword != "n/a" )
		{
			$json = $conn->doQuery("select count(*) as `count` from `n_banner` where `b_title` LIKE ?;",array("%$the_keyword%"),'json');
			$objUser = json_decode($json);
			
			$temp_total_counts = $objUser->data->query_result[0]->count;
							
			$json = $conn->doQuery("select `b_id`,`b_title`,`b_franchise_id`,`b_franchise`,`b_video_id`,`b_video`,`b_desc`,`b_url_logo`,`b_url_background`,`b_url_link`,`b_creator_id`,`b_is_active`,`b_date_added`,`b_last_updated` from `n_banner` where `b_title` LIKE ? ORDER BY `b_title` ASC LIMIT ? OFFSET ?;",array("%$the_keyword%", $the_limit_per_page,$the_page_number),'json');
			$objUser = json_decode($json);
		}
	}
									
	// if exists
	if(!empty($objUser->data->query_result))
	{
		$the_search_result = array();
		foreach($objUser->data->query_result as $key => $value)
		{
			$the_search_result[] = (string)$value->b_id;
								
			$temp_search_text = (string)$value->b_title;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
								
			$temp_search_text = (string)$value->b_franchise_id;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
								
			$temp_search_text = (string)$value->b_franchise;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
								
			$temp_search_text = (string)$value->b_video_id;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
								
			$temp_search_text = (string)$value->b_video;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
								
			$temp_search_text = (string)$value->b_desc;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
								
			$temp_search_text = (string)$value->b_url_logo;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
					
			$temp_search_text = (string)$value->b_url_background;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
						
			$temp_search_text = (string)$value->b_url_link;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
								
			$the_search_result[] = (string)$value->b_is_active;
			
			if( $called_by_client == false )
			{
				$the_search_result[] = (string)$value->b_creator_id;
				$the_search_result[] = (string)$value->b_date_added;
				$the_search_result[] = (string)$value->b_last_updated;
			}
		}
		$return["ret"]["dat"]["list"]["banner"] = $the_search_result;
		
		if( $called_by_client == true )
		{
			$return["ret"]["dat"]["list"]["ttlbanner"] = $temp_total_counts;
		}
							
		return $temp_total_counts;
	}
	else
	{
		return -1;
	}
}

// $conn = connection to the db
// $called_by_client = true if called by nonton app/web, false if called by cms
// $the_keyword = keyword for listing filtered content for cms, or filter for the video type for nonton app/web
// $the_limit_per_page = display limit per page
// $the_page_number = current page number
function GetVideoList($conn, $called_by_client, $the_keyword, $the_limit_per_page, $the_page_number, $sort_client_by_name = false)
{
	global $return;
	
	if( $called_by_client == true )
	{
		$json = $conn->doQuery("select count(*) as `count` from `n_type` where `t_is_active`=?;",array(1),'json');
		$objUser = json_decode($json);
		
		$temp_total_types = $objUser->data->query_result[0]->count;
		
		$the_video_list_details = array();
		$temp_total_franchises = 0;
		$the_video_list_types = array();
		$the_franchise_total_per_type = array();
						
		$jsonType = $conn->doQuery("select `t_id`,`t_name` from `n_type` where `t_is_active`=?;",array(1),'json');
		$objUserType = json_decode($jsonType);
			
		foreach($objUserType->data->query_result as $keyType => $valueType)
		{
			$the_video_list_types[] = (string)$valueType->t_id;
			$the_video_list_types[] = (string)$valueType->t_name;
			
			$the_type_id = (string)$valueType->t_id;
			
			$jsonTypeTotal = $conn->doQuery("select count(*) as `count` from `n_franchise` where `f_type_id`=? and `f_is_active`=?;",array($the_type_id,1),'json');
			$objUserTypeTotal = json_decode($jsonTypeTotal);
		
			$temp_total_franchises = $objUserTypeTotal->data->query_result[0]->count; 
			if( $temp_total_franchises > $the_limit_per_page )
			{
				$temp_total_franchises = $the_limit_per_page;
			}
			
			if( $sort_client_by_name == true )
			{
				$jsonFranchise = $conn->doQuery("select `f_id`,`f_name`,`f_url_poster`,`f_url_poster_landscape` from `n_franchise` where `f_type_id`=? and `f_is_active`=? ORDER BY `f_name` ASC LIMIT ? OFFSET ?;",array($the_type_id, 1,$the_limit_per_page,$the_page_number),'json');
				$objUserFranchise = json_decode($jsonFranchise);
			}
			else
			{
				$jsonFranchise = $conn->doQuery("select `f_id`,`f_name`,`f_url_poster`,`f_url_poster_landscape` from `n_franchise` where `f_type_id`=? and `f_is_active`=? ORDER BY `f_date_added` DESC LIMIT ? OFFSET ?;",array($the_type_id, 1,$the_limit_per_page,$the_page_number),'json');
				$objUserFranchise = json_decode($jsonFranchise);
			}
			
			$the_total_video = 0;
				
			foreach($objUserFranchise->data->query_result as $keyFranchise => $valueFranchise)
			{
				$the_franchise_url_poster = (string)$valueFranchise->f_url_poster;
				$the_franchise_url_poster_landscape = (string)$valueFranchise->f_url_poster_landscape;
				if( IsNullOrEmptyString($the_franchise_url_poster) )
				{
					$json = $conn->doQuery("select `v_id`,`v_franchise_id`,`v_franchise_name`,`v_title`,`v_season`,`v_episode`,`v_year_production`,`v_synopsis`,`v_duration`,`v_director_id`,`v_producer_id`,`v_casts_id`,`v_director`,`v_producer`,`v_casts`,`v_url_poster`,`v_url_poster_landscape`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_views`,`v_likes`,`v_dislikes`,`v_price`,`v_screenshot_url_1`,`v_screenshot_url_2`,`v_screenshot_url_3`,`v_screenshot_url_4`,`v_screenshot_url_5`,`v_is_active`,`v_uploader_admin_id`,`v_updater_admin_id`,`v_date_uploaded`,`v_last_updated` from `n_video` where `v_franchise_id`=? and `v_is_active`=? and `v_url_poster`!=? ORDER BY `v_date_uploaded` DESC LIMIT 1;",array((string)$valueFranchise->f_id, 1, ""),'json');
					$objUser = json_decode($json);
				}
				else
				{
					$json = $conn->doQuery("select `v_id`,`v_franchise_id`,`v_franchise_name`,`v_title`,`v_season`,`v_episode`,`v_year_production`,`v_synopsis`,`v_duration`,`v_director_id`,`v_producer_id`,`v_casts_id`,`v_director`,`v_producer`,`v_casts`,`v_url_poster`,`v_url_poster_landscape`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_views`,`v_likes`,`v_dislikes`,`v_price`,`v_screenshot_url_1`,`v_screenshot_url_2`,`v_screenshot_url_3`,`v_screenshot_url_4`,`v_screenshot_url_5`,`v_is_active`,`v_uploader_admin_id`,`v_updater_admin_id`,`v_date_uploaded`,`v_last_updated` from `n_video` where `v_franchise_id`=? and `v_is_active`=? ORDER BY `v_date_uploaded` DESC LIMIT 1;",array((string)$valueFranchise->f_id, 1),'json');
					$objUser = json_decode($json);
				}
				
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_video_list_details[] = GetVideoDetails($objUser, $called_by_client, !$called_by_client, $the_franchise_url_poster,$the_franchise_url_poster_landscape);
					$the_total_video = $the_total_video + 1;
				}	
			}
			
			$the_franchise_total_per_type[] = (string)$the_total_video;
		}
		
		//$return["ret"]["dat"]["list"]["ttlfranchise"] = $temp_total_franchises;
		
		$return["ret"]["dat"]["list"]["type"] = $the_video_list_types;
		$return["ret"]["dat"]["list"]["franchise"] = $the_franchise_total_per_type;
		$return["ret"]["dat"]["list"]["video"] = $the_video_list_details;
		
		return $temp_total_types;
	}
	else
	{
		$json = $conn->doQuery("select count(*) as `count` from `n_video` where `v_id`>=?;",array(1),'json');
		$objUser = json_decode($json);
		
		$temp_total_counts = $objUser->data->query_result[0]->count;
						
		$json = $conn->doQuery("select `v_id`,`v_franchise_id`,`v_franchise_name`,`v_title`,`v_season`,`v_episode`,`v_year_production`,`v_synopsis`,`v_duration`,`v_director_id`,`v_producer_id`,`v_casts_id`,`v_director`,`v_producer`,`v_casts`,`v_url_poster`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_views`,`v_likes`,`v_dislikes`,`v_price`,`v_screenshot_url_1`,`v_screenshot_url_2`,`v_screenshot_url_3`,`v_screenshot_url_4`,`v_screenshot_url_5`,`v_is_active`,`v_uploader_admin_id`,`v_updater_admin_id`,`v_date_uploaded`,`v_last_updated` from `n_video` where `v_id`>=? ORDER BY `v_title` ASC LIMIT ? OFFSET ?;",array(1, $the_limit_per_page,$the_page_number),'json');
		$objUser = json_decode($json);
						
		if( $the_keyword != "n/a" )
		{
			$json = $conn->doQuery("select count(*) as `count` from `n_video` where `v_title` LIKE ?;",array("%$the_keyword%"),'json');
			$objUser = json_decode($json);
		
			$temp_total_counts = $objUser->data->query_result[0]->count;
						
			$json = $conn->doQuery("select `v_id`,`v_franchise_id`,`v_franchise_name`,`v_title`,`v_season`,`v_episode`,`v_year_production`,`v_synopsis`,`v_duration`,`v_director_id`,`v_producer_id`,`v_casts_id`,`v_director`,`v_producer`,`v_casts`,`v_url_poster`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_views`,`v_likes`,`v_dislikes`,`v_price`,`v_screenshot_url_1`,`v_screenshot_url_2`,`v_screenshot_url_3`,`v_screenshot_url_4`,`v_screenshot_url_5`,`v_is_active`,`v_uploader_admin_id`,`v_updater_admin_id`,`v_date_uploaded`,`v_last_updated` from `n_video` where `v_title` LIKE ? ORDER BY `v_title` ASC LIMIT ? OFFSET ?;",array("%$the_keyword%", $the_limit_per_page,$the_page_number),'json');
			$objUser = json_decode($json);
		}
		
		// if exists
		if(!empty($objUser->data->query_result))
		{
			$return["ret"]["dat"]["list"]["video"] = GetVideoDetails($objUser, $called_by_client, !$called_by_client);
							
			return $temp_total_counts;
		}
	}
	
	return -1;
}

// $conn = connection to the db
// $called_by_client = true if called by nonton app/web, false if called by cms
// $the_keyword = keyword for listing filtered content for cms, or filter for the video type for nonton app/web
// $the_limit_per_page = display limit per page
// $the_page_number = current page number
function GetVideoListBasedOnGenre($conn, $captureddata, $the_limit_per_page, $the_page_number, $sort_client_by_name = false)
{
	global $return;
	
	$the_video_id = (int)$captureddata->vidid;
	$the_genre_id_1 = (int)$captureddata->genre1;
	$the_genre_id_2 = (int)$captureddata->genre2;
	$the_genre_id_3 = (int)$captureddata->genre3;
	$the_genre_id_4 = (int)$captureddata->genre4;
	$the_genre_id_5 = (int)$captureddata->genre5;
	//if( $called_by_client == true )
	{
		$json = $conn->doQuery("select count(*) as `count` from `n_genre` where `g_is_active`=?;",array(1),'json');
		$objUser = json_decode($json);
		
		$temp_total_types = $objUser->data->query_result[0]->count;
		
		$the_video_list_details = array();
		$temp_total_franchises = 0;
		$the_video_list_types = array();
		$the_franchise_total_per_type = array();
						
		//$jsonType = $conn->doQuery("select `g_id`,`g_name` from `n_genre` where `g_is_active`=?;",array(1),'json');
		//$objUserType = json_decode($jsonType);
			
		//foreach($objUserType->data->query_result as $keyType => $valueType)
		{
			//$the_video_list_types[] = (string)$valueType->g_id;
			//$the_video_list_types[] = (string)$valueType->g_name;
			
			//$the_type_id = (string)$valueType->g_id;
			
			$jsonTypeTotal = $conn->doQuery("select count(*) as `count` from `n_franchise` where (`f_genre_id_1`=? or `f_genre_id_1`=? or `f_genre_id_1`=? or `f_genre_id_1`=? or `f_genre_id_1`=? or `f_genre_id_2`=? or `f_genre_id_2`=? or `f_genre_id_2`=? or `f_genre_id_2`=? or `f_genre_id_2`=? or `f_genre_id_3`=? or `f_genre_id_3`=? or `f_genre_id_3`=? or `f_genre_id_3`=? or `f_genre_id_3`=? or `f_genre_id_4`=? or `f_genre_id_4`=? or `f_genre_id_4`=? or `f_genre_id_4`=? or `f_genre_id_4`=? or `f_genre_id_5`=? or `f_genre_id_5`=? or `f_genre_id_5`=? or `f_genre_id_5`=? or `f_genre_id_5`=?) and `f_is_active`=?;",array($the_genre_id_1, $the_genre_id_2, $the_genre_id_3, $the_genre_id_4, $the_genre_id_5,$the_genre_id_1, $the_genre_id_2, $the_genre_id_3, $the_genre_id_4, $the_genre_id_5,$the_genre_id_1, $the_genre_id_2, $the_genre_id_3, $the_genre_id_4, $the_genre_id_5,$the_genre_id_1, $the_genre_id_2, $the_genre_id_3, $the_genre_id_4, $the_genre_id_5,$the_genre_id_1, $the_genre_id_2, $the_genre_id_3, $the_genre_id_4, $the_genre_id_5,1),'json');
			$objUserTypeTotal = json_decode($jsonTypeTotal);
		
			$temp_total_franchises = $objUserTypeTotal->data->query_result[0]->count; 
			if( $temp_total_franchises > $the_limit_per_page )
			{
				$temp_total_franchises = $the_limit_per_page;
			}
			
			if( $sort_client_by_name == true )
			{
				$jsonFranchise = $conn->doQuery("select `f_id`,`f_name`,`f_url_poster`,`f_url_poster_landscape` from `n_franchise` where (`f_genre_id_1`=? or `f_genre_id_1`=? or `f_genre_id_1`=? or `f_genre_id_1`=? or `f_genre_id_1`=? or `f_genre_id_2`=? or `f_genre_id_2`=? or `f_genre_id_2`=? or `f_genre_id_2`=? or `f_genre_id_2`=? or `f_genre_id_3`=? or `f_genre_id_3`=? or `f_genre_id_3`=? or `f_genre_id_3`=? or `f_genre_id_3`=? or `f_genre_id_4`=? or `f_genre_id_4`=? or `f_genre_id_4`=? or `f_genre_id_4`=? or `f_genre_id_4`=? or `f_genre_id_5`=? or `f_genre_id_5`=? or `f_genre_id_5`=? or `f_genre_id_5`=? or `f_genre_id_5`=?) and `f_is_active`=? ORDER BY `f_name` ASC LIMIT ? OFFSET ?;",array($the_genre_id_1, $the_genre_id_2, $the_genre_id_3, $the_genre_id_4, $the_genre_id_5,$the_genre_id_1, $the_genre_id_2, $the_genre_id_3, $the_genre_id_4, $the_genre_id_5,$the_genre_id_1, $the_genre_id_2, $the_genre_id_3, $the_genre_id_4, $the_genre_id_5,$the_genre_id_1, $the_genre_id_2, $the_genre_id_3, $the_genre_id_4, $the_genre_id_5,$the_genre_id_1, $the_genre_id_2, $the_genre_id_3, $the_genre_id_4, $the_genre_id_5, 1,$the_limit_per_page,$the_page_number),'json');
				$objUserFranchise = json_decode($jsonFranchise);
			}
			else
			{
				$jsonFranchise = $conn->doQuery("select `f_id`,`f_name`,`f_url_poster`,`f_url_poster_landscape` from `n_franchise` where (`f_genre_id_1`=? or `f_genre_id_1`=? or `f_genre_id_1`=? or `f_genre_id_1`=? or `f_genre_id_1`=? or `f_genre_id_2`=? or `f_genre_id_2`=? or `f_genre_id_2`=? or `f_genre_id_2`=? or `f_genre_id_2`=? or `f_genre_id_3`=? or `f_genre_id_3`=? or `f_genre_id_3`=? or `f_genre_id_3`=? or `f_genre_id_3`=? or `f_genre_id_4`=? or `f_genre_id_4`=? or `f_genre_id_4`=? or `f_genre_id_4`=? or `f_genre_id_4`=? or `f_genre_id_5`=? or `f_genre_id_5`=? or `f_genre_id_5`=? or `f_genre_id_5`=? or `f_genre_id_5`=?) and `f_is_active`=? ORDER BY `f_date_added` DESC LIMIT ? OFFSET ?;",array($the_genre_id_1, $the_genre_id_2, $the_genre_id_3, $the_genre_id_4, $the_genre_id_5,$the_genre_id_1, $the_genre_id_2, $the_genre_id_3, $the_genre_id_4, $the_genre_id_5,$the_genre_id_1, $the_genre_id_2, $the_genre_id_3, $the_genre_id_4, $the_genre_id_5,$the_genre_id_1, $the_genre_id_2, $the_genre_id_3, $the_genre_id_4, $the_genre_id_5,$the_genre_id_1, $the_genre_id_2, $the_genre_id_3, $the_genre_id_4, $the_genre_id_5, 1,$the_limit_per_page,$the_page_number),'json');
				$objUserFranchise = json_decode($jsonFranchise);
			}
			
			$the_total_video = 0;
				
			foreach($objUserFranchise->data->query_result as $keyFranchise => $valueFranchise)
			{
				$the_franchise_url_poster = (string)$valueFranchise->f_url_poster;
				$the_franchise_url_poster_landscape = (string)$valueFranchise->f_url_poster_landscape;
				/*if( IsNullOrEmptyString($the_franchise_url_poster) )
				{
					$json = $conn->doQuery("select `v_id`,`v_franchise_id`,`v_franchise_name`,`v_title`,`v_season`,`v_episode`,`v_year_production`,`v_synopsis`,`v_duration`,`v_director_id`,`v_producer_id`,`v_casts_id`,`v_director`,`v_producer`,`v_casts`,`v_url_poster`,`v_url_poster_landscape`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_views`,`v_likes`,`v_dislikes`,`v_price`,`v_screenshot_url_1`,`v_screenshot_url_2`,`v_screenshot_url_3`,`v_screenshot_url_4`,`v_screenshot_url_5`,`v_is_active`,`v_uploader_admin_id`,`v_updater_admin_id`,`v_date_uploaded`,`v_last_updated` from `n_video` where `v_franchise_id`=? and `v_is_active`=? and `v_url_poster`!=? and `v_id`!=? ORDER BY `v_date_uploaded` DESC LIMIT 1;",array((string)$valueFranchise->f_id, 1, "", $the_video_id),'json');
					$objUser = json_decode($json);
				}
				else*/
				{
					$json = $conn->doQuery("select `v_id`,`v_franchise_id`,`v_franchise_name`,`v_title`,`v_season`,`v_episode`,`v_year_production`,`v_synopsis`,`v_duration`,`v_director_id`,`v_producer_id`,`v_casts_id`,`v_director`,`v_producer`,`v_casts`,`v_url_poster`,`v_url_poster_landscape`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_views`,`v_likes`,`v_dislikes`,`v_price`,`v_screenshot_url_1`,`v_screenshot_url_2`,`v_screenshot_url_3`,`v_screenshot_url_4`,`v_screenshot_url_5`,`v_is_active`,`v_uploader_admin_id`,`v_updater_admin_id`,`v_date_uploaded`,`v_last_updated` from `n_video` where `v_franchise_id`=? and `v_is_active`=? and `v_id`!=? ORDER BY `v_date_uploaded` DESC LIMIT 1;",array((string)$valueFranchise->f_id, 1, $the_video_id),'json');
					$objUser = json_decode($json);
				}
				
				// if exists
				if(!empty($objUser->data->query_result))
				{
					$the_video_list_details[] = GetVideoDetails($objUser, true, false, $the_franchise_url_poster, $the_franchise_url_poster_landscape);
					$the_total_video = $the_total_video + 1;
				}	
			}
			
			$the_franchise_total_per_type[] = (string)$the_total_video;
		}
		
		//$return["ret"]["dat"]["list"]["ttlfranchise"] = $temp_total_franchises;
		
		//$return["ret"]["dat"]["list"]["type"] = $the_video_list_types;
		$return["ret"]["dat"]["list"]["franchise"] = $the_franchise_total_per_type;
		$return["ret"]["dat"]["list"]["video"] = $the_video_list_details;
		
		return $temp_total_types;
	}
	
	return -1;
}

function GetVideoDetails($objUser, $called_by_client, $show_full_details, $the_franchise_url_poster = "", $the_franchise_url_poster_landscape = "")
{
	global $return;
	$the_search_result = array();
	
	foreach($objUser->data->query_result as $key => $value)
	{
		$the_search_result[] = (string)$value->v_id;
								
		$temp_search_text = (string)$value->v_franchise_id;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;
							
		$temp_search_text = (string)$value->v_franchise_name;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;
							
		$temp_search_text = (string)$value->v_title;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;
							
		$temp_search_text = (string)$value->v_url_poster;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = $the_franchise_url_poster;
		}
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;
							
		$temp_search_text = (string)$value->v_url_poster_landscape;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = $the_franchise_url_poster_landscape;
		}
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;
							
		$temp_search_text = (string)$value->v_url_youtube_id;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;
							
		$temp_search_text = (string)$value->v_url_cdn;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;
						
		$temp_search_text = (string)$value->v_prioritize_youtube;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "1";
		}
		$the_search_result[] = $temp_search_text;
							
		$the_search_result[] = (string)$value->v_price;
		
		$the_search_result[] = (string)$value->v_is_active;
		
		if( $called_by_client == false || $show_full_details == true )
		{
			$temp_search_text = (string)$value->v_season;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "1";
			}
			$the_search_result[] = $temp_search_text;
								
			$temp_search_text = (string)$value->v_episode;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "1";
			}
			$the_search_result[] = $temp_search_text;
								
			$temp_search_text = (string)$value->v_synopsis;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
								
			$the_search_result[] = (string)$value->v_views;
			$the_search_result[] = (string)$value->v_likes;
			$the_search_result[] = (string)$value->v_dislikes;
			
			if( $called_by_client == false )
			{
				$the_search_result[] = (string)$value->v_uploader_admin_id;
				$the_search_result[] = (string)$value->v_updater_admin_id;
				$the_search_result[] = (string)$value->v_date_uploaded;
				$the_search_result[] = (string)$value->v_last_updated;
			}						
									
			$temp_search_text = (string)$value->v_year_production;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
								
			$temp_search_text = (string)$value->v_duration;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "0";
			}
			$the_search_result[] = $temp_search_text;
			
			$temp_search_text = (string)$value->v_director_id;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
					
			$temp_search_text = (string)$value->v_producer_id;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
								
			$temp_search_text = (string)$value->v_casts_id;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
								
			$temp_search_text = (string)$value->v_director;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
								
			$temp_search_text = (string)$value->v_producer;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
								
			$temp_search_text = (string)$value->v_casts;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
								
			$temp_search_text = (string)$value->v_screenshot_url_1;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
								
			$temp_search_text = (string)$value->v_screenshot_url_2;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
								
			$temp_search_text = (string)$value->v_screenshot_url_3;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
								
			$temp_search_text = (string)$value->v_screenshot_url_4;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
								
			$temp_search_text = (string)$value->v_screenshot_url_5;
			if( IsNullOrEmptyString($temp_search_text) )
			{
				$temp_search_text = "n/a";
			}
			$the_search_result[] = $temp_search_text;
		}
	}
	
	return $the_search_result;
}

function GetUserInfo($conn, $the_fb_id, $the_user_id = "-1")
{
	global $return;
	$the_search_result = array();

	$json = $conn->doQuery("select `u_fullname`,`u_username`,`u_birthday`,`u_gender`,`u_subscription_start`,`u_subscription_end`,`u_id`, `u_email`,`u_phone`,`u_avatar_url`,`u_points`,`u_is_active` from `n_user` where `u_fbid`=? LIMIT 1;",array($the_fb_id),'json');
					
	$objUser = json_decode($json);

	if( $the_fb_id == "-1" && $the_user_id != "-1" )
	{
		$json = $conn->doQuery("select `u_fullname`,`u_username`,`u_birthday`,`u_gender`,`u_subscription_start`,`u_subscription_end`,`u_id`, `u_email`,`u_phone`,`u_avatar_url`,`u_points`,`u_is_active` from `n_user` where `u_id`=? LIMIT 1;",array($the_user_id),'json');
					
		$objUser = json_decode($json);
	}
					
	// if already exists, then we only want to update if necessary
	if(!empty($objUser->data->query_result))
	{
		$the_fullname = (string)$objUser->data->query_result[0]->u_fullname;
		$the_username = (string)$objUser->data->query_result[0]->u_username;
		$the_birthday = (string)$objUser->data->query_result[0]->u_birthday;
		$the_gender = (string)$objUser->data->query_result[0]->u_gender;
		$the_subscription_start = (string)$objUser->data->query_result[0]->u_subscription_start;
		$the_subscription_end = (string)$objUser->data->query_result[0]->u_subscription_end;
		$the_user_id = (string)$objUser->data->query_result[0]->u_id;
		$the_email = (string)$objUser->data->query_result[0]->u_email;
		$the_phone = (string)$objUser->data->query_result[0]->u_phone;
		$the_avatar_url = (string)$objUser->data->query_result[0]->u_avatar_url;
		$the_points = (string)$objUser->data->query_result[0]->u_points;
		$the_is_active = (string)$objUser->data->query_result[0]->u_is_active;

		$temp_search_text = $the_user_id;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;

		$temp_search_text = $the_fb_id;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;

		$temp_search_text = $the_fullname;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;

		$temp_search_text = $the_username;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;

		$temp_search_text = $the_email;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;

		$temp_search_text = $the_birthday;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;

		$temp_search_text = $the_gender;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;

		$temp_search_text = $the_phone;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;

		$temp_search_text = $the_avatar_url;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;

		$temp_search_text = $the_points;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;

		$temp_search_text = $the_subscription_start;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;

		$temp_search_text = $the_subscription_end;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;

		$temp_search_text = $the_is_active;
		if( IsNullOrEmptyString($temp_search_text) )
		{
			$temp_search_text = "n/a";
		}
		$the_search_result[] = $temp_search_text;

		$return["ret"]["dat"]["user"]["data"] = $the_search_result;
	}
	else
	{
		$return["sta"] = "FAIL";
		$return["ret"]["msg"] = "GET USER - SOMETHING IS WRONG, USER NO LONGER EXISTS";
	}				
}

function AddPointsInternal($initial_pts, $added_pts)
{
	$max_points = 999999;
	$final_points = $initial_pts + $added_pts;
	if( $final_pts < 0 )
	{
		$final_pts = 0;
	}
	else if( $final_pts > $max_points )
	{
		$final_pts = $max_points;
	}

	return $final_pts;
}

function AddPoints($conn, $the_fb_id, $the_points, $the_user_id = "-1")
{
	$json = $conn->doQuery("select `u_id`,`u_points` from `n_user` where `u_fbid`=? LIMIT 1;",array($the_fb_id),'json');
					
	$objUser = json_decode($json);

	if( $the_fb_id == "-1" && $the_user_id != "-1" )
	{
		$json = $conn->doQuery("select `u_id`,`u_points` from `n_user` where `u_id`=? LIMIT 1;",array($the_user_id),'json');
				
		$objUser = json_decode($json);
	}
		
	// if already exists, then we only want to update if necessary
	if(!empty($objUser->data->query_result))
	{
		$the_user_points = (string)$objUser->data->query_result[0]->u_points;
		$the_final_points = AddPointsInternal($the_user_points, $the_points);
					
		$json = $conn->doQuery("update `n_user` set `u_points`=?,`u_last_login`=? where `u_fbid`=?;",array($the_final_points,$conn->getDateTimeNow(),$the_fb_id),'json');
					
		$objUser = json_decode($json);
			
		// if update succeeded
		if(strcmp($objUser->data->result,"ok")==0)
		{
			$return["ret"]["dat"]["popup"]["title"] = "POINTS ADDED";
			$return["ret"]["dat"]["popup"]["body"] = "User points added successfully!";
			
			$return["sta"] = "OK";
			$return["ret"]["msg"] = "ADD POINTS - SUCCESSFUL";

			return true;
		}
		else
		{
			$return["ret"]["dat"]["popup"]["title"] = "ERROR";
			$return["ret"]["dat"]["popup"]["body"] = "User points failed to be added!";
			
			$return["sta"] = "OK";
			$return["ret"]["msg"] = "ADD POINTS - FAILED";
		}
	}
	else
	{
		$return["ret"]["dat"]["popup"]["title"] = "ERROR";
		$return["ret"]["dat"]["popup"]["body"] = "User doesn't exist. Please use the register button.";
	
		$return["sta"] = "OK";
		$return["ret"]["msg"] = "ADD POINTS - USER DOESN'T EXIST";
	}
					
	return false;
}

function GetSecondsInOneDay()
{
	return 86400; // 60 x 60 x 24
}

function AddSubscription($conn, $the_fb_id, $the_subscription_type, $the_user_id = "-1")
{
	global $return;
	
	$json = $conn->doQuery("select `u_id`,`u_fullname`,`u_subscription_start`,`u_subscription_end` from `n_user` where `u_fbid`=? LIMIT 1;",array($the_fb_id),'json');
					
	$objUser = json_decode($json);
		
	if( $the_fb_id == "-1" && $the_user_id != "-1" )
	{
		$json = $conn->doQuery("select `u_id`,`u_fullname`,`u_subscription_start`,`u_subscription_end` from `n_user` where `u_id`=? LIMIT 1;",array($the_user_id),'json');
	
		$objUser = json_decode($json);
	}

	// if already exists, then we only want to update if necessary
	if(!empty($objUser->data->query_result))
	{
		$the_subscription_start = strtotime((string)$objUser->data->query_result[0]->u_subscription_start);
		$the_subscription_end = strtotime((string)$objUser->data->query_result[0]->u_subscription_end);
		$the_user_fullname = (string)$objUser->data->query_result[0]->u_fullname;
		$the_user_id = (string)$objUser->data->query_result[0]->u_id;

		if( strtotime((string)$conn->getDateTimeNow()) > $the_subscription_end )
		{
			$the_subscription_start = $conn->getDateTimeNow();
			$the_subscription_end = $conn->getDateTimeNow();
		}
		else
		{
			$the_subscription_start = date('Y-m-d H:i:s', $the_subscription_start);
			$the_subscription_end = date('Y-m-d H:i:s', $the_subscription_end);
		}

		$isSubscriptionTypeValid = false;
		
		$the_new_subscription_end = $the_subscription_end;
		$poop = $the_subscription_start;

		switch( $the_subscription_type )
		{
			case "week":
			{
				$the_new_subscription_end = date('Y-m-d H:i:s', strtotime((string)$the_new_subscription_end) + (GetSecondsInOneDay() * 7));
				//$the_new_subscription_end->modify('+7 days');
				$isSubscriptionTypeValid = true;
				break;
			}

			case "month":
			{
				$the_new_subscription_end = date('Y-m-d H:i:s', strtotime($the_new_subscription_end) + (GetSecondsInOneDay() * 30));
				//$the_new_subscription_end->modify('+30 days');
				$isSubscriptionTypeValid = true;
				break;
			}
		}
					
		if( $isSubscriptionTypeValid == true )
		{
			//$json = $conn->doQuery("update `n_user` set `u_subscription_start`=?,`u_subscription_end`=?,`u_last_login`=? where `u_fbid`=?;",array($the_subscription_start->format('Y-m-d H:i:s'),$the_new_subscription_end->format('Y-m-d H:i:s'),$conn->getDateTimeNow(),$the_fb_id),'json');

			$json = $conn->doQuery("update `n_user` set `u_subscription_start`=?,`u_subscription_end`=?,`u_last_login`=? where `u_fbid`=?;",array($the_subscription_start,$the_new_subscription_end,$conn->getDateTimeNow(),$the_fb_id),'json');
						
			$objUser = json_decode($json);
				
			// if update succeeded
			if(strcmp($objUser->data->result,"ok")==0)
			{
				$json = $conn->doQuery("insert into `n_pay_subscription` (`ps_user_id`,`ps_user_fullname`,`ps_date_paid`,`ps_type`) values (?,?,?,?);",array($the_user_id,$the_user_fullname,$conn->getDateTimeNow(),$the_subscription_type),'json');
						
				$objUser = json_decode($json);

				// if creation succeeded
				if(strcmp($objUser->data->result,"ok")==0)
				{
					$return["ret"]["dat"]["popup"]["title"] = "SUBSCRIPTION UPDATED";
					$return["ret"]["dat"]["popup"]["body"] = "User subscription updated successfully!";
					
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "USER SUBSCRIPTION - SUCCESSFUL ".$poop;
				}
				else
				{
					$return["ret"]["dat"]["popup"]["title"] = "ERROR";
					$return["ret"]["dat"]["popup"]["body"] = "User subscription failed to be added!";
					
					$return["sta"] = "OK";
					$return["ret"]["msg"] = "USER SUBSCRIPTION - FAILED TO BE ADDED";
				}

				return true;
			}
			else
			{
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "User subscription failed to be added!";
				
				$return["sta"] = "OK";
				$return["ret"]["msg"] = "USER SUBSCRIPTION - FAILED TO UPDATE PROFILE ";
			}
		}
		else
		{
			$return["sta"] = "OK";
			$return["ret"]["msg"] = "USER SUBSCRIPTION - WRONG SUBSCRIPTION TYPE ".$the_subscription_type;
		}
	}
	else
	{
		$return["ret"]["dat"]["popup"]["title"] = "ERROR";
		$return["ret"]["dat"]["popup"]["body"] = "User doesn't exist. Please use the register button.";
	
		$return["sta"] = "OK";
		$return["ret"]["msg"] = "USER SUBSCRIPTION - USER DOESN'T EXIST";
	}
					
	return false;
}

function AddPayPerView($conn, $the_fb_id, $the_video_id, $the_video_name, $the_user_id = "-1")
{
	global $return;
	
	$json = $conn->doQuery("select `u_id`,`u_fullname` from `n_user` where `u_fbid`=? LIMIT 1;",array($the_fb_id),'json');
					
	$objUser = json_decode($json);

	if( $the_fb_id == "-1" && $the_user_id != "-1" )
	{
		$json = $conn->doQuery("select `u_id`,`u_fullname` from `n_user` where `u_id`=? LIMIT 1;",array($the_user_id),'json');
				
		$objUser = json_decode($json);
	}
		
	// if already exists, then we only want to update if necessary
	if(!empty($objUser->data->query_result))
	{
		$the_user_fullname = (string)$objUser->data->query_result[0]->u_fullname;
		$the_user_id = (string)$objUser->data->query_result[0]->u_id;

		$json = $conn->doQuery("select `ppv_id`,`ppv_date_started`,`ppv_date_ended` from `n_pay_per_view` where `ppv_user_id`=? and `ppv_video_id`=? and `ppv_video_name`=? and `ppv_video_ended`>? LIMIT 1;",array($the_user_id, $the_video_id, $the_video_name, $conn->getDateTimeNow()),'json');
					
		$objUser = json_decode($json);

		// if exists, then we dont need to pay this for so we only check this if it's empty
		if(empty($objUser->data->query_result))
		{
			$the_date_end = $conn->getDateTimeNow();
			$the_date_end = date('Y-m-d H:i:s', strtotime((string)$the_date_end) + (GetSecondsInOneDay() * 2));
				
			//$the_date_end->modify('+2 days');

			$json = $conn->doQuery("insert into `n_pay_per_view` (`ppv_user_id`,`ppv_user_fullname`,`ppv_video_id`,`ppv_video_name`,`ppv_date_started`,`ppv_date_ended`) values (?,?,?,?,?,?);",array($the_user_id,$the_user_fullname,$the_video_id, $the_video_name,$conn->getDateTimeNow(),$the_date_end),'json');
						
			$objUser = json_decode($json);

			// if creation succeeded
			if(strcmp($objUser->data->result,"ok")==0)
			{
				$return["ret"]["dat"]["popup"]["title"] = "UPDATED";
				$return["ret"]["dat"]["popup"]["body"] = "User pay per view added successfully!";
				
				$return["sta"] = "OK";
				$return["ret"]["msg"] = "PAY PER VIEW - SUCCESSFULLY ADDED";
			}
			else
			{
				$return["ret"]["dat"]["popup"]["title"] = "ERROR";
				$return["ret"]["dat"]["popup"]["body"] = "User pay per view failed to be added!";
				
				$return["sta"] = "OK";
				$return["ret"]["msg"] = "PAY PER VIEW - FAILED";
			}
		}
		else
		{
			$return["ret"]["dat"]["popup"]["title"] = "ERROR";
			$return["ret"]["dat"]["popup"]["body"] = "Pay per view already bought for this video!";
			
			$return["sta"] = "OK";
			$return["ret"]["msg"] = "PAY PER VIEW - FAILED";
		}
	}
	else
	{
		$return["ret"]["dat"]["popup"]["title"] = "ERROR";
		$return["ret"]["dat"]["popup"]["body"] = "User doesn't exist. Please use the register button.";
	
		$return["sta"] = "OK";
		$return["ret"]["msg"] = "PAY PER VIEW - USER DOESN'T EXIST";
	}
					
	return false;
}

function GetPayPerView($conn, $the_fb_id, $the_user_id = "-1")
{
	global $return;
	$the_search_result = array();

	$json = $conn->doQuery("select `u_id`,`u_fullname` from `n_user` where `u_fbid`=? LIMIT 1;",array($the_fb_id),'json');
					
	$objUser = json_decode($json);

	if( $the_fb_id == "-1" && $the_user_id != "-1" )
	{
		$json = $conn->doQuery("select `u_id`,`u_fullname` from `n_user` where `u_id`=? LIMIT 1;",array($the_user_id),'json');
					
		$objUser = json_decode($json);
	}
		
	// make sure user exists
	if(!empty($objUser->data->query_result))
	{
		$the_user_fullname = (string)$objUser->data->query_result[0]->u_fullname;
		$the_user_id = (string)$objUser->data->query_result[0]->u_id;

		$json = $conn->doQuery("select `ppv_video_id`,`ppv_video_name`,`ppv_date_started`,`ppv_date_ended` from `n_pay_per_view` where `ppv_user_id`=? and `ppv_date_ended`>=?;",array($the_user_id, strtotime((string)$conn->getDateTimeNow())),'json');
					
		$objUser = json_decode($json);

		// if exists, then we dont need to pay this for so we only check this if it's empty
		if(!empty($objUser->data->query_result))
		{
			foreach($objUser->data->query_result as $key => $value)
			{
				$temp_search_text = (string)$value->ppv_video_id;
				if( IsNullOrEmptyString($temp_search_text) )
				{
					$temp_search_text = "n/a";
				}
				$the_search_result[] = $temp_search_text;

				$temp_search_text = (string)$value->ppv_video_name;
				if( IsNullOrEmptyString($temp_search_text) )
				{
					$temp_search_text = "n/a";
				}
				$the_search_result[] = $temp_search_text;

				$temp_search_text = (string)$value->ppv_date_started;
				if( IsNullOrEmptyString($temp_search_text) )
				{
					$temp_search_text = "n/a";
				}
				$the_search_result[] = $temp_search_text;

				$temp_search_text = (string)$value->ppv_date_ended;
				if( IsNullOrEmptyString($temp_search_text) )
				{
					$temp_search_text = "n/a";
				}
				$the_search_result[] = $temp_search_text;
			}

			$return["ret"]["dat"]["list"]["ppv"] = $the_search_result;
		}
		else
		{
			
		}
	}
}

?>