<?php


header('Access-Control-Allow-Origin: *');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$fp=fopen("/var/www/html/govtschemes.in/sites/default/files/abc","w");

//fprintf($fp,"%s",print_r($_POST,true));
//file_put_contents("/tmp/fp11",print_r($_POST,true));

if(isset($_POST['data'])){

	$stockyards=array();

	$data=json_decode($_POST['data']);

	fprintf($fp,"%s",print_r($data,true));

	for($i=0;$i<count($data->d);++$i)
	{
		$obj = $data->d[$i];
//		print("{$obj->District}, {$obj->Stockyard}\n");

		$stockyards[$obj->Stockyard]=$obj->District;
	}


	fprintf($fp,"%s\n",print_r($stockyards,true));

	file_put_contents("/var/www/html/govtschemes.in/sites/default/files/stockyards.json",json_encode($stockyards));

}



function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}
