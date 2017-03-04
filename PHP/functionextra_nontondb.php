<?php
function get_initial_content($conn, $app_signature, $country_name)
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
			$json = $conn->doQuery("SELECT `v_id`,`v_franchise_id`,`v_title`,`v_url_youtube_id`,`v_url_cdn`,`v_prioritize_youtube`,`v_url_poster`, `v_url_poster_landscape`FROM `n_video` v, `n_franchise` f WHERE v_is_active =1 AND v_franchise_id=f.f_id AND (UPPER(f_country) LIKE '%".$country_name."%' OR UPPER(f_country )LIKE '%ALL%') ORDER BY v_last_updated DESC;", 
										null,'json');
			$objVideo = json_decode($json);
			$return["sta"] = "SUCCESS";
			$return["ret"]["msg"] = $objVideo->data->query_result;
			//echo json_encode($objVideo);
		}
	}
	else
	{
		$return["sta"] = "FAIL";
		$return["ret"]["msg"] = "APP SIGNATURE NO LONGER EXISTS";
	}
}
?>