<?
	class FileStorer{

		private $credenciales_servidor = array('username' => '', 'password' => '');
		// private $ruta_servidor_remoto_base = 'https://sigespro.corvuslab.co/';
		private $https = 'https://';
		private $dominio = '192.168.40.201';
		//private $dominio = '192.168.40.201';
		private $ruta_servidor_remoto_webservice = '';
		private $host_domain = '';

		function __construct(){
			$this->ruta_servidor_remoto_webservice = $this->https.$this->dominio.'/php/webservices/';
			$this->host_domain = $_SERVER['HTTP_HOST'];
		}

		function __destruct(){
			$this->credenciales_servidor = array();
			$this->ruta_servidor = '';
			unset($this->credenciales_servidor);
			unset($this->ruta_servidor);
		}

		public function UploadFileToRemoteServer($files, $archivo_carga_remota, $ruta){
			global $MY_FILE;

			$result = '';
			$nombres_archivos = array();

			$url_remota =  $this->ruta_servidor_remoto_webservice.$archivo_carga_remota.'.php';

			if ($this->host_domain == $this->dominio) {
				$this->CreateFolder($ruta);

				foreach ($files as $key => $file) {
					$posicion1 = strrpos($file['name'],'.')+1;
			        $extension1 =  substr($file['name'],$posicion1);
			        $extension1 =  strtolower($extension1);
			        $_filename1 = uniqid() . "." . $extension1;
					$_file1 = $MY_FILE .$ruta. $_filename1;
					
			        $subido1 = move_uploaded_file($file['tmp_name'], $_file1);
			        if ($subido1) {
						
            			@chmod ( $_file1, 0777 );
			        	array_push($nombres_archivos, $_filename1);
			        }
				}

				$result = json_encode($nombres_archivos);
			}else{
				$archivos = array('folders'=>$ruta);

				foreach ($files as $key => $file) {				
					$output = new CURLFile($file['tmp_name'], $file['type'], $file['name']);
					$archivos[] = $output;
				}
				
				$cliente = curl_init($url_remota);
				curl_setopt($cliente, CURLOPT_SAFE_UPLOAD, true);
				curl_setopt($cliente, CURLOPT_HTTPHEADER , array('Content-Type: multipart/form-data;'));
				curl_setopt($cliente, CURLOPT_POST, true);
				curl_setopt($cliente, CURLOPT_POSTFIELDS, $archivos);
				curl_setopt($cliente, CURLOPT_RETURNTRANSFER, true);
				$result = curl_exec($cliente);
				curl_close($cliente);	
			}

			return json_decode($result);
		}

		public function UploadFileToRemoteServerWithName($files, $archivo_carga_remota, $ruta,$nombre){
			global $MY_FILE;

			$result = '';
			

			$url_remota =  $this->ruta_servidor_remoto_webservice.$archivo_carga_remota.'.php';

			if ($this->host_domain == $this->dominio) {
				$this->CreateFolder($ruta);
				
				foreach ($files as $key => $file) {
					$posicion1 = strrpos($file['name'],'.')+1;
			       
			        $_file1 = $MY_FILE .$ruta. $nombre;
			        
			        $subido1 = move_uploaded_file($file['tmp_name'], $_file1);
			        if ($subido1) {			        	
            			@chmod ( $_file1, 0777 );
			        	
			        }
				}
				$result = json_encode($nombre);
				
			}else{
				$archivos = array('folders'=>$ruta);

				foreach ($files as $key => $file) {				
					$output = new CURLFile($file['tmp_name'], $file['type'], $nombre);
					$archivos[] = $output;
				}
				
				$cliente = curl_init($url_remota);
				curl_setopt($cliente, CURLOPT_SAFE_UPLOAD, true);
				curl_setopt($cliente, CURLOPT_HTTPHEADER , array('Content-Type: multipart/form-data;'));
				curl_setopt($cliente, CURLOPT_POST, true);
				curl_setopt($cliente, CURLOPT_POSTFIELDS, $archivos);
				curl_setopt($cliente, CURLOPT_RETURNTRANSFER, true);
				$result = curl_exec($cliente);
				curl_close($cliente);	
			}

			return json_decode($result);
		}

		public function CreateFolder($rutaCarpetas){
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

		public function UpdateFile($rutaArchivo, $updatedData){
			global $MY_FILE;

			$url_archivo = $MY_FILE.$rutaArchivo;

			file_put_contents($url_archivo, $updatedData);
    		chmod($url_archivo, 0644);
		}
	}
?>