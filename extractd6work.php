<?php

		$today=date('d-m-Y',strtotime('yesterday midnight'));

		$time=strtotime('today');


		$userarr= getusersmapping($time);

		print("Getting revision info\n");
		$revarr= getRevisionInfo($time);

		print("Getting created today\n");
		$createdarr= getCreatedToday($time);

	        if( count($createdarr) ==0)
		{
			exit(0);
		}

		$html='<html><body>';

		$uidcreated=[];

		for($i=0;$i<count($createdarr);++$i)
		{

			if( array_key_exists($createdarr[$i][1],$uidcreated))
			{
				$uidcreated[$createdarr[$i][1]]++;
			}
			else
			{
				$uidcreated[$createdarr[$i][1]] = 1;	
			}

		}

		$tmp='';
		foreach ( $uidcreated as  $uid=>$value)
		{
			$tmp.=  "<li>$userarr[$uid]($uidcreated[$uid]) </li>";


		}

		$dt=date('d-m-Y',$time);

		$html .=<<<EOT
		<h2>Page creations: $dt</h2>
		<ol>$tmp</ol>
EOT;

//node modifications


		$uidmodified=[];

		for($i=0;$i<count($revarr);++$i)
		{

			if( array_key_exists($revarr[$i][1],$uidmodified))
			{
				$uidmodified[$revarr[$i][1]]++;
			}
			else
			{
				$uidmodified[$revarr[$i][1]] = 1;	
			}

		}

		$tmp='';
		foreach ( $uidmodified as  $uid=>$value)
		{
			$tmp.=  "<li>$userarr[$uid]($value) </li>";


		}

		$html .=<<<EOT
		<h2>Page Changes:</h2>
		<ol>$tmp</ol>
EOT;


//employee work series
$empstats =  [];   ///  empstats[uid][time]= (changed/modified,nid,title)

for($i=0;$i<count($createdarr);++$i)
{
	if(! array_key_exists($createdarr[$i][1],$empstats ))
	{
		$empstats[$createdarr[$i][1]] = [];	
	}


	$empstats [$createdarr[$i][1]][$createdarr[$i][0]] =$createdarr[$i];


}


for($i=0;$i<count($revarr);++$i)
{
	if(! array_key_exists($revarr[$i][1],$empstats ))
	{
		$empstats[$revarr[$i][1]] = [];	
	}

	if(!array_key_exists($revarr[$i][0],$empstats [$revarr[$i][1]] )){   //skip those have been created

		$empstats [$revarr[$i][1]][$revarr[$i][0]] =$revarr[$i];		
	}

}

print("Printing Empstats");
print_r($empstats);

$emphtml='<h2>All Employee Work</h2>';

foreach( $empstats  as  $uid=>$arr)
{
ksort($arr);
$user=$userarr[$uid];

$emphtml.="<h3>$user work</h3>";

$lasttime = null;

foreach($arr as $timestamp => $dataarr)
{

$dt=date('H:i:s',$timestamp);

$diff='';
if($lasttime)
{
$start    = new DateTime($lasttime);
$end      = new DateTime($dt);
$diff72     = $start->diff($end);
$diff=$diff72->format("%H:%I:%S");

}
else
$diff="00:00:00";

$lasttime = $dt;

if($dataarr[4] =="created")
{
	$color="green";
}
else{
	$color="brown";
}

$emphtml.= "<div>  $dt<span style='color:brown'>($diff)</span>  <a target='_blank' href='https://www.govtschemes.in/node/$dataarr[3]'>$dataarr[3]</a>   ".
htmlspecialchars($dataarr[2]) . "   <span style='color:$color'>$dataarr[4]</span>  </div>"."\r\n";
 

}

	
}

$html.=$emphtml;


$html.="</body></html>\n";


print($html);

sendhtmlemain($html,$time);

function sendhtmlemain($html,$time)
{
$headers = "MIME-Version: 1.0" . "\r\n"; 
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n"; 
 
$fromName="Anurag";
$from="contactus@govtschemes.in";
$to="rag.raggupta@gmail.com;shahrukhanika@gmail.com";

// Additional headers 
$headers .= 'From: '.$fromName.'<'.$from.'>' . "\r\n"; 
//$headers .= 'Cc: jyotianika1002@gmail.com' . "\r\n"; 

 
// Send email 
if(mail($to, "Govscheme  All Employee Work:" .date('d/M/Y',$time), $html, $headers))
{ 
    echo 'Email has sent successfully.'; 
}else{ 
   echo 'Email sending failed.'; 
}
}

function getCreatedToday($timestamp){

	$beginning_of_day = strtotime("midnight", $timestamp);
	$end_of_day = strtotime("tomorrow", $timestamp) - 1;

	$createdToday=array();
	$node_storage =\Drupal::entityTypeManager()->getStorage('node');
	$node_query = $node_storage->getQuery()->condition('created', $beginning_of_day, '>')
					       ->condition('created', $end_of_day, '<');
	$node_query->condition('type', 'scheme');
	$nids = $node_query->execute();

	$nodes = $node_storage->loadMultiple($nids);

	foreach ($nodes as $node){


			array_push($createdToday,array($node->created->value,$node->getOwnerID(),$node->getTitle(),$nid,'created'));

	}

	/*foreach ($nids as $nid) {
		$node = \Drupal\node\Entity\Node::load($nid);
		if($node->created->value>=$beginning_of_day  && $node->created->value  <=$end_of_day)
			array_push($createdToday,array($node->created->value,$node->getOwnerID(),$node->getTitle(),$nid,'created'));
	}*/

	return $createdToday;
}

function getusersmapping(){
	$users = array();

	$sql = "SELECT uid,name FROM users_field_data  ";

 	$database = \Drupal::database();

	$result = $database->query($sql );

	foreach ($result as $obj ) {

		$users[$obj->uid]=$obj->name;

	}

	return $users;

}

function getRevisionInfo($timestamp){
	$beginning_of_day = strtotime("midnight", $timestamp);
        $end_of_day = strtotime("tomorrow", $timestamp) - 1;

        $createdToday=array();
        $node_storage =\Drupal::entityTypeManager()->getStorage('node');
        $node_query = $node_storage->getQuery();
        $node_query->condition('type', 'scheme');
        $nids = $node_query->execute();

	foreach ($nids as $nid) {
		$node = \Drupal\node\Entity\Node::load($nid);

		//load the vid
		$vids = \Drupal::entityTypeManager()->getStorage('node')->revisionIds($node);

		foreach($vids as $vid)
		{
			$vnode=\Drupal::entityTypeManager()->getStorage('node')->loadRevision($vid);

			if($vnode->changed->value>=$beginning_of_day  && $vnode->changed->value  <=$end_of_day)
			{
			     array_push($createdToday,array($vnode->changed->value,$vnode->getRevisionUser()->id(),$vnode->getTitle(),$nid,'modified'));
			}
		}
	}
        return $createdToday;
}
