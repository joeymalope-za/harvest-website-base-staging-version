<?php
function getToken(){
	$curl = curl_init();
	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://accounts.zoho.com.au/oauth/v2/token',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS => array('refresh_token' => '1000.696f63474b5dbb1173162959d8860e15.9c62795fc4f907490321fd3afa9481d0','client_id' => '1000.PBLWDTQ1GWITMUY0C714DVGZQBQI5X','client_secret' => 'ac11526e2f74e803b708a8c9c6a03b7ce7de50042f','grant_type' => 'refresh_token'),
	  CURLOPT_HTTPHEADER => array(
		'Cookie: 3e285c6f31=ab4135fb07b081628e9395b1c3f85d5b; _zcsr_tmp=bdf43b16-cbad-422b-b338-7f52f41ef179; iamcsr=bdf43b16-cbad-422b-b338-7f52f41ef179'
	  ),
	));
	$response = curl_exec($curl);
	curl_close($curl);
	return json_decode($response, true);
}

function curlGet($url, $token){
	$curl = curl_init();
	curl_setopt_array($curl, array(
	  CURLOPT_URL => $url,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'GET',
	  CURLOPT_HTTPHEADER => array(
		'Authorization: Zoho-oauthtoken '.$token
	  ),
	));

	$response = curl_exec($curl);
	curl_close($curl);
	return json_decode($response, true);
}
