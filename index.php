<?php

if( empty($_POST['nricfin']) || empty($_POST['name']) || empty($_POST['passport']) ) {
	exit('Incomplete params');
}

$ch = curl_init();

curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1" );
curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
curl_setopt($ch, CURLOPT_URL, "https://eponline.mom.gov.sg/epol/PEPOLENQM007NextAction.do");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "requesterNRICFIN=".$_POST['nricfin']."&requesterName=".urlencode($_POST['name']));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects 
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable 

$res = curl_exec($ch);

$valid = true;

$dom = new DOMDocument;
$dom->loadHTML($res);
foreach($dom->getElementsByTagName('td') as $node)
{
	$pool = explode(" ", $node->nodeValue);

	foreach( $pool as $v ) {
		if( strpos($v, 'Please enter a valid') !== false ) {
			$valid = false;
			break 2;
		}
	}
}

$data = [];

if( $valid ){

	curl_setopt($ch, CURLOPT_URL, "https://eponline.mom.gov.sg/epol/PEPOLENQM008SubmitAction.do");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "travelDocNo=".$_POST['passport']);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

	$res = curl_exec($ch);

	$dom = new DOMDocument;
	$dom->loadHTML($res);
	$status = false;
	foreach($dom->getElementsByTagName('td') as $node)
	{
		$pool = explode(" ", $node->nodeValue);

		foreach( $pool as $v ) {
			if( strpos($v, 'Status') !== false && ( strpos($v, 'Pending') !== false || strpos($v, 'Rejected') !== false ) || strpos($v, 'Approved') !== false || strpos($v, 'Issued') !== false ) {
				$status = $v;
				break 2;
			}
		}
	}

	$data['msg'] = $status;

} else {
	$data['msg'] = 'Error: invalid credentials';
}

if(array_key_exists('callback', $_GET)){
	header('Content-Type: text/javascript; charset=utf8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Max-Age: 3628800');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

    $callback = $_GET['callback'];
    echo $callback.'('.json_encode($data).')';
} else {
	header('Content-Type: application/json; charset=utf8');
	echo json_encode($data);
}
