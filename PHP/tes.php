<?php

function get_video_list($conn, $start, $franchiseid, $category,$country_name)
{
	global $return;
	$the_search_result = array();

	$isEpisodic = false;
	
	$json = $conn->doQuery("SELECT `v_franchise_id`,`v_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_url_poster`, `v_url_poster_landscape`, `t_name`, `t_id`, `t_url_poster_landscape`, `v_season` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." GROUP BY v_franchise_id ORDER BY v_last_updated DESC LIMIT ".$start.",5;", 
		null,'json');
	//echo json_encode($json);
	$objVideo = json_decode($json);

	$totalFranchise = 0;
	
	//$json = $conn->doQuery("SELECT count(*) total FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." ORDER BY v_last_updated DESC;", null,'json');
	//$count = json_decode($json)->data->query_result[0]->total;
	
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
			$jsonVideo = $conn->doQuery("SELECT `v_franchise_id`,`v_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,'' `v_url_poster`, `v_url_poster_landscape`, `t_name`, `t_id`, `t_url_poster_landscape`, `v_season` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND v_franchise_id=f.f_id AND v_franchise_id='".$row->v_franchise_id."' AND  (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." group by v_id ORDER BY v_episode ASC LIMIT ".$totalVideoToReturn.";", 
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
		for( $genreId = 1; $genreId < 5; ++$genreId )
		{
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
					if( $videoCount >= $totalVideoToReturn) )
					{
						//$totalVideoToReturn doesn't change
					}
					else 
					{
						$totalVideoToReturn = (int)((int)$videoCount / (int)$base_calculation) * $base_calculation;
					}

					$totalVideoToReturn + 1;

					// get the list of video
					$jsonVideo = $conn->doQuery("SELECT `v_franchise_id`,`v_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_url_poster`, `v_url_poster_landscape`, `t_name`, `t_id`, `t_url_poster_landscape`, `v_season`, `f_genre_id_1` FROM `n_video` v, `n_franchise` f, `n_type` t WHERE v_is_featured=1 AND v_is_active =1 AND f_genre_id_1=".$genreId." AND (UPPER(f_country_access) LIKE '%".strtoupper($country_name)."%' OR UPPER(f_country_access )LIKE '%ALL%') AND t.t_id =f.f_type_id AND t.t_id=".$category." group by v_id ORDER BY v_episode ASC LIMIT ".$totalVideoToReturn.";", 
										null,'json');
					$objVideo = json_decode($jsonVideo);
					$data[]=$objVideo->data->query_result;
				}
			}
		}

	}

	// TODO : fix the pagination here
	/*if(($start+1)<$count)
	{
		$nextPage=true;
	}
	else
	{
		$nextPage=false;
	}*/
	//$videoList=array("next_id"=>$lastFid[0], "data"=>$dataCat);
	$return["sta"] = "SUCCESS";
	$return["ret"]["dat"] = encrypt(json_encode(array("listVideo"=>$data)));
				
}