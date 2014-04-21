<script language="JavaScript" src="sha256.js"></script>
<?php
$user ="score";
$pas  ="";
//$passHASH = js_myhash(js_myhash($pas)+'uug6aclgv7ei315eeb7t8j6vm7');
$passHASH="8369422919c90f31051cfc758a0e30bb91a6d925fd06cbe2a134d7d9b9c77435";
$send="name=$user&password=$passHASH";
echo $send;
$handler = curl_init();  
curl_setopt($handler, CURLOPT_URL, "http://localhost/boca/src/index.php");
curl_setopt($handler, CURLOPT_POSTFIELDS,true);  
curl_setopt($handler, CURLOPT_POSTFIELDS, $send);  
curl_setopt($handler, CURLOPT_COOKIEJAR,getcwd().'/bocaid');
curl_setopt($handler, CURLOPT_COOKIEFILE,getcwd().'/bocaid'); 
curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
curl_setopt($handler, CURLOPT_FRESH_CONNECT, true);
curl_setopt($handler, CURLOPT_COOKIESESSION,getcwd().'/bocaid');
curl_setopt($handler, CURLOPT_RETURNTRANSFER,1);
$response = curl_exec ($handler);
echo $response;
?>