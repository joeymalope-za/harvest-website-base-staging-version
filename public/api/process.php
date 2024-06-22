<?php
require_once("functions.php");
$organisationId = 7002692282;
$token =  getToken();
//$token = "1000.460019bc51b46add04d8a78a933febd4.05ccd4aa9be21e3539c20edc6bc26720";
$url = 'https://www.zohoapis.com.au/inventory/v1/items/82518000000060001/image?organization_id='.$organisationId;
$res = curlGet($url, $token['access_token']);
echo "<pre>";
print_r($res);