<?
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');

	$url_remota =  'https://sigesproph.com.co/php/webservices/PORTAL_CLIENTES/get_dispensaciones_30_dias_atras.php';

	// $dispensaciones = curl_init($url_remota);
	// curl_setopt($dispensaciones, CURLOPT_RETURNTRANSFER, true);
	// var_dump("antes de");
	// $result = curl_exec($dispensaciones);
	// var_dump("despues de");
	// curl_close($dispensaciones);	

 //    var_dump($result);

    $url = "https://api-dian.sigesproph.com.co/";
 $handle = curl_init();
// Set the url
curl_setopt($handle, CURLOPT_URL, $url);
// Set the result output to be a string.
curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
 
$output = curl_exec($handle);
 
if(!$output)
echo "curl_errno ".curl_errno($handle).":". curl_error($handle);
echo $output;

curl_close($handle);
?>