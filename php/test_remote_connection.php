<?
	require_once('../class/class.guardar_archivos_dev.php');

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    // var_dump($_FILES);
    // exit;
    
    $storer = new FileStorer();
    $nombres = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'folder/test/');

    var_dump(json_decode($nombres));
    // var_dump(json_decode($nombres));
	
	//$url =  "https://sigespro.corvuslab.co/php/webservices/store_remote_files.php?dir=directorioPrueba";
	// $url =  "https://192.168.40.201/php/webservices/store_remote_files.php?dir=directorioPrueba";

	// $output = new CURLFile($_FILES['archivo']['tmp_name'], $_FILES['archivo']['type'], $_FILES['archivo']['name']);

	// $files = array(
	// 	$output
 //    );
	
	// $cliente = curl_init($url);
	// curl_setopt($cliente, CURLOPT_POST, true);
	// curl_setopt($cliente, CURLOPT_POSTFIELDS, $files);
	// curl_setopt($cliente, CURLOPT_RETURNTRANSFER, true);
	// $result = curl_exec($cliente);
	// curl_close($cliente);

	// var_dump($result);
?>