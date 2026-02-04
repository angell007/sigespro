<?
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');

    $dirName = isset($_REQUEST['folders']) ? $_REQUEST['folders'] : '';

   	CreateFolder($dirName);
	$nombres = SubirArchivos();
	
	echo json_encode($nombres);

	function CrearDirectorio($dirName){
		global $MY_FILE;

		if (!file_exists($MY_FILE."php/".$dirName)) {
			mkdir($MY_FILE."php/".$dirName, 0777);
			var_dump("directorio creado");
		}else{
			var_dump("ya existe el directorio!");
		}
	}

	function CreateFolder($rutaCarpetas){
		global $MY_FILE; 

		$folders = explode("/", $rutaCarpetas);
		$ruta_validar = $MY_FILE.$folders[0];

		foreach ($folders as $key => $name) {
			if ($name != '') {
				if ($key > 0) {
					$ruta_validar = $ruta_validar."/".$name;
				}

				if (!file_exists($ruta_validar)) {
					mkdir($ruta_validar, 0777, true);
				}
			}				
		}
	}

	function SubirArchivos(){
		global $MY_FILE;

		$nombres_archivos = array();

		foreach ($_FILES as $key => $file) {
			$posicion1 = strrpos($file['name'],'.')+1;
	        $extension1 =  substr($file['name'],$posicion1);
	        $extension1 =  strtolower($extension1);
	        $_filename1 = uniqid() . "." . $extension1;
	        $_file1 = $MY_FILE . "ARCHIVOS/ARCHIVOS_REMOTOS/" . $_filename1;
	        
	        $subido1 = move_uploaded_file($file['tmp_name'], $_file1);
	        if ($subido1) {
	        	
	        	array_push($nombres_archivos, $_filename1);
	        }
		}

		return json_encode($nombres_archivos);
	}
?>