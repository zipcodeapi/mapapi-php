<?php

require_once('RealTimeMapApi.class.php');

$mapapi1 = new RealTimeMapApi('btJHjMJb', 'LGzCW2LS8SDVn3d6', '/tmp/realtimemapapi_server_socket');
$mapapi2 = new RealTimeMapApi('YSc55rfn', 'k7vl2nHvCFfN40YT');

$endTime = time() + 10;
while (time() <= $endTime)
{
	$mapapi1->sendPoints(array(
		array('lat' => 29.7628 + (3-3*rand(0,1000000)/1000000), 'lng' => -95.3831 + (3-3*rand(0,1000000)/1000000), 'c'=>'#ff0000'),
		array('lat' => 35.7628 + (3-3*rand(0,1000000)/1000000), 'lng' =>  -85.3831 + (3-3*rand(0,1000000)/1000000), 'r' => 10, 'c' => '#ff0000', 'c2' => '#0000ff'),
		array('lat' => 35.9 + (3-3*rand(0,1000000)/1000000), 'lng' => -85.7 + (3-3*rand(0,1000000)/1000000), 'r' => 10),
		array('zipcode'=>'90210')
	));

	$mapapi2->sendPoints(array(
		array('lat' => 31 + (3-3*rand(0,1000000)/1000000), 'lng' => -90 + (3-3*rand(0,1000000)/1000000)),
		array('lat' => 35 + (3-3*rand(0,1000000)/1000000), 'lng' => -80 + (3-3*rand(0,1000000)/1000000)),
		array('lat' => 38 + (3-3*rand(0,1000000)/1000000), 'lng' => -95 + (3-3*rand(0,1000000)/1000000))
	));

	usleep(250000);
}
