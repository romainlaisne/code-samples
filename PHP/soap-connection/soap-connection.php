<?php
function uploadWebCustomer($cid) {
	global $DEVSERVER;
	$location = ($DEVSERVER ? "https://testwebservices.fakedomain.nl/My3form/WebService_My3form_WS.asmx" : "https://webservices.fakedomain.nl/my3form/WebService_My3Form_WS.asmx");
	try {
		if ($DEVSERVER) { ini_set("soap.wsdl_cache_enabled", "0");
		}// disabling WSDL cache
		$wsdl = $location . "?WSDL";
		$ops = array("trace" => 1, "soap_version" => SOAP_1_2, "location" => $location, "exceptions" => 0);
		$client = new SoapClient($wsdl, $ops);
		$soapvar = makeWebCustXML($cid);
		$upload = $client -> UploadWebCustomerForSO(array("webcustomerupload" => $soapvar));
		$result = $client -> __getLastRequest();
		
	} catch (SoapFault $fault) {
		error_log("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", 1, "ict@3form.eu");

	}
}
?>
