<?php
header('content-type: text/plain');
function FunctionName($carpeta, $contador=0)
{

	$gestor = opendir($carpeta);
	$xml = '';
	while (($archivo = readdir($gestor)) !== false) {

		$ruta_completa = $carpeta . "/" . $archivo;

		// Se muestran todos los archivos y carpetas excepto "." y ".."
		if ($archivo != "." && $archivo != "..") {
			if (!is_dir($ruta_completa)) {
				$contenido='';
				$contenido = file_get_contents($ruta_completa);
				// var_dump($contenido); exit;
				$str = str_replace("", '', $contenido);
				
				file_put_contents("$ruta_completa", $str); 
								
				
			} else {
				// echo "carpeta"; exit;
				FunctionName($ruta_completa);
			}
			// unlink($ruta_completa);
		}
	}
	
}
FunctionName('/home/sigesproph');
