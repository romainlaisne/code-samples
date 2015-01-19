<?php
$companyID = "fakeid";
$password = "******";
// start to build XML
$Xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>" . "<TrackRequest locale=\"en_US\" version=\"3.1\">" . "<SearchCriteria marketType=\"DOMESTIC\" originCountry=\"NL\">" . "<Account>" . "<Number>" . "000232494" . "</Number>" . "<CountryCode>" . "NL" . "</CountryCode>" . "</Account>" . "<Period>" . "<DateFrom>" . "20130620" . "</DateFrom>" . "<DateTo>" . "20130624" . "</DateTo>" . "</Period>" . "</SearchCriteria>" . "<LevelOfDetail>" . "<Complete destinationAddress=\"true\" originAddress=\"true\" package=\"true\" shipment=\"true\"/>" . "<POD format=\"URL\"/>" . "</LevelOfDetail>" . "</TrackRequest>";

chkServer("express.tnt.com", "443");

$Result = doPost($Xml);
$header = $Result[0];

// Show result
echo "<h3>Request</h3>";
echo "<b>Header:</b><br>\n<textarea style=\"height:165px;width:100%;\">" . $header . "\n</textarea>";
echo "<b>Content:</b><br>\n<textarea style=\"height:200px;width:100%;\">" . $Result[1] . "\n</textarea>";
echo "<b>Posted:</b><br>\n<textarea style=\"height:200px;width:100%;\">" . $posted . "\n</textarea>";

function doPost($postContent) {

	$postContent = urlencode("xml_in=" . $postContent);
	$postContent = str_replace("%3C", "<", $postContent);
	$postContent = str_replace("%3E", ">", $postContent);
	$postContent = str_replace("%3F", "?", $postContent);
	$postContent = str_replace("%27", "'", $postContent);
	$postContent = str_replace("%3D", "=", $postContent);
	$postContent = str_replace("+", " ", $postContent);
	$postContent = str_replace("%22", "\"", $postContent);
	$postContent = str_replace("%2F", "/", $postContent);

	$host = "express.tnt.com";
	$contentLen = strlen($postContent);

	$httpHeader = "POST /expressconnect/track.do HTTP/1.1\r\n" . "Host: $host\r\n" . "Authorization: Basic " . base64_encode("fakeid:fakepass") . "\r\n\r\n" . "User-Agent: PHP Script\r\n" . "Content-Type: application/x-www-form-urlencoded\r\n" . "Content-Length: $contentLen\r\n" . "Connection: close\r\n" . "\r\n";

	$httpHeader .= $postContent;
	global $posted;
	$posted = $httpHeader;

	try {

		$curl = curl_init('$host');
		curl_setopt($curl, CURLOPT_FAILONERROR, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($curl);
		echo $result;

	} catch (Exception $e) {
		echo "Error: " . $e -> getMessage();
	}

	return array('Error', 'Error - likely cause: fsockopen');
}

// check if a server is up by connecting to a port
function chkServer($host, $port) {
	$hostip = @gethostbyname($host);
	// resloves IP from Hostname returns hostname on failure

	if ($hostip == $host)// if the IP is not resloved
	{
		echo "Server is down or does not exist";
	} else {
		if (!$x = @fsockopen($hostip, $port, $errno, $errstr, 5))// attempt to connect
		{
			echo "Server is down";
		} else {
			echo "Server is up";
			if ($x) {
				@fclose($x);
				//close connection
			}
		}
	}
}
?>