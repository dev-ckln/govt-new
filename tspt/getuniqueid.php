<?php

global $fp,$conn;
$fp = fopen("/tmp/uniqloog.txt","a");

$ip=get_client_ip();


fprintf($fp,"%s","ip=$ip\n");


do_common();

$query = <<<EOT

insert into irctc_uniqid  (ip) values('$ip')

EOT;

fprintf($fp,"Going to run query=$query\n");

$res = mysqli_query($conn, $query);

fprintf($fp,"Total rows found=%d\n",mysqli_affected_rows($conn));

if (mysqli_affected_rows($conn) === 1)
{
        $query = <<<EOT

                SELECT LAST_INSERT_ID() id;
EOT;
        fprintf($fp,"Going to run query=$query\n");

        $res = mysqli_query($conn, $query);

        fprintf($fp,"Total rows found=%d\n",mysqli_num_rows($res));

        if (mysqli_num_rows($res) == 1)
        {

                $row = mysqli_fetch_assoc($res);
		$val = "tspt2$row[id]." . rand(1,999999);
                fprintf($fp, "returning %s\n",$val);
                echo "$val";

        }
}

fprintf($fp,"%s\n","----------------------------------------------");



function do_common() {
        global $conn;

        $conn = mysqli_connect("127.0.0.1", "raggupta_root", "ratnam0") or die(mysqli_error());

        mysqli_select_db($conn, "raggupta_gs") or die(mysqli_error());

}

function get_client_ip() {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
                $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
                $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
                $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
                $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
                $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
                $ipaddress = getenv('REMOTE_ADDR');
        else
                $ipaddress = 'UNKNOWN';
        return $ipaddress;
}


