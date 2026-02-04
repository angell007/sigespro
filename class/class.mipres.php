<?php
	class Mipres{

		private $https = 'https://';
		private $dominio = 'wsmipres.sispro.gov.co/WSSUMMIPRESNOPBS/api/';
		private $dominioReporteFacturacion = 'wsmipres.sispro.gov.co/WSFACMIPRESNOPBS/api/';
		private $nit='804016084';
		private $token_general='A722142C-9E85-47BA-B8A3-397B813A00A5';
		private $token='';
		private $ruta_api = '';
		private $ruta_api_fact = '';

		function __construct(){
			$this->ruta_api = $this->https.$this->dominio;
	        $this->ruta_api_fact = $this->https.$this->dominioReporteFacturacion;
			$this->GetToken();
		}

		function __destruct(){
			$this->ruta_api = '';
			unset($this->ruta_api);
		}

		public function GetDireccionamientoPorFecha($fecha){
		

			$result='';
			$url=$this->ruta_api.'DireccionamientoXFecha/'.$this->nit."/".$this->token.'/'.$fecha;
			//echo $url;
		    $cliente = curl_init($url);
			curl_setopt($cliente, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($cliente);			
			curl_close($cliente);	

			return json_decode($result,true);
		}
		public function GetDireccionamientoPorPrescripcion($prescripcion){
		
			$result='';
			$url=$this->ruta_api.'DireccionamientoXPrescripcion/'.$this->nit."/".$this->token.'/'.$prescripcion;
			//echo $url;
		    $cliente = curl_init($url);
			curl_setopt($cliente, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($cliente);			
			curl_close($cliente);	

			return json_decode($result,true);
		}
		public function GetDireccionamientoPorPrescripcionRaw($prescripcion){
		
			$result = '';
			$url = $this->ruta_api.'DireccionamientoXPrescripcion/'.$this->nit."/".$this->token.'/'.$prescripcion;
			$cliente = curl_init($url);
			curl_setopt($cliente, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($cliente);
			$http_code = curl_getinfo($cliente, CURLINFO_HTTP_CODE);
			$error = curl_error($cliente);
			curl_close($cliente);

			return [
				'raw' => $result,
				'http_code' => $http_code,
				'error' => $error,
				'url' => $url,
			];
		}
		public function AnularProgramacion($idprogramacion){
		
			$result='';
			$url=$this->ruta_api.'AnularProgramacion/'.$this->nit."/".$this->token.'/'.$idprogramacion;
			//echo $url;
		    $cliente = curl_init($url);
		    curl_setopt($cliente, CURLOPT_HTTPHEADER , array('Content-Type: application/json','Content-Length: ' . strlen($idprogramacion)));
		    curl_setopt($cliente,CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($cliente, CURLOPT_RETURNTRANSFER, true);
			
			$result = curl_exec($cliente);	
			//var_dump($result);
			curl_close($cliente);	

			return json_decode($result,true);
		}
		public function AnularEntrega($identrega){
		
			$result='';
			$url=$this->ruta_api.'AnularEntrega/'.$this->nit."/".$this->token.'/'.$identrega;
		//	echo $url;
		    $cliente = curl_init($url);
		    curl_setopt($cliente, CURLOPT_HTTPHEADER , array('Content-Type: application/json','Content-Length: ' . strlen($identrega)));
			curl_setopt($cliente, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($cliente,CURLOPT_CUSTOMREQUEST, 'PUT');
			$result = curl_exec($cliente);			
			curl_close($cliente);	

			return json_decode($result,true);
		}
		public function AnularReporteEntrega($identrega){
		
			$result='';
			$url=$this->ruta_api.'AnularReporteEntrega/'.$this->nit."/".$this->token.'/'.$identrega;
			//echo $url;
		    $cliente = curl_init($url);
		    curl_setopt($cliente, CURLOPT_HTTPHEADER , array('Content-Type: application/json','Content-Length: ' . strlen($identrega)));
			curl_setopt($cliente, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($cliente,CURLOPT_CUSTOMREQUEST, 'PUT');
			$result = curl_exec($cliente);			
			curl_close($cliente);	

			return json_decode($result,true);
		}

	private function GetToken(){
		$url=$this->ruta_api."GenerarToken/".$this->nit."/".$this->token_general;
		$cliente = curl_init($url);
		curl_setopt($cliente, CURLOPT_RETURNTRANSFER, true);
		$this->token = str_replace('"','',curl_exec($cliente));
		curl_close($cliente);	
	}
	private function GetTokenFact(){
		$url=$this->ruta_api_fact."GenerarToken/".$this->nit."/".$this->token_general;
		$cliente = curl_init($url);
		curl_setopt($cliente, CURLOPT_RETURNTRANSFER, true);
		$this->token = str_replace('"','',curl_exec($cliente));
		curl_close($cliente);	
	}

	public function Programacion($data){
		$result='';
		$url=$this->ruta_api.'Programacion/'.$this->nit."/".$this->token;
		$data_json=json_encode($data);
		//echo $url;
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HTTPHEADER , array('Content-Type: application/json','Content-Length: ' . strlen($data_json)));
		curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($curl, CURLOPT_POSTFIELDS,$data_json);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($curl);
		curl_close($curl);
		return json_decode($result,true);
	}

	public function ReportarEntrega($data){
		$result = '';
		$url = $this->ruta_api.'Entrega/'.$this->nit."/".$this->token;
		$data_json = json_encode($data);

		try{	
			// Registrar entrada
			error_log("ReportarEntrega - Inicio". date('Y-m-d H:i:s'));
			error_log("ReportarEntrega - URL: $url");
			error_log("ReportarEntrega - Datos entrada: " . print_r($data, true));

			//Iniciamos el curl
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER , array('Content-Type: application/json','Content-Length: ' . strlen($data_json)));
			curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($curl, CURLOPT_POSTFIELDS,$data_json);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			if (curl_errno($curl)) {
            // Si hay error de CURL, lanzar excepci¨®n
            	throw new Exception('cURL error: ' . curl_error($curl));
        	}
			$result = curl_exec($curl);
			curl_close($curl);
			error_log("ReportarEntrega - Respuesta: $result");
		}catch(Exception $e){
			error_log("ReportarEntrega - Error: " . $e->getMessage());
		}
		error_log("ReportarEntrega - Fin". date('Y-m-d H:i:s'));
		return json_decode($result,true);
	}

	public function ReportarEntregaEfectiva($data){
		$result='';
		$url=$this->ruta_api.'ReporteEntrega/'.$this->nit."/".$this->token;
		$data_json=json_encode($data);
		//echo $url;
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HTTPHEADER , array('Content-Type: application/json','Content-Length: ' . strlen($data_json)));
		curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($curl, CURLOPT_POSTFIELDS,$data_json);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($curl);
		curl_close($curl);

		return json_decode($result,true);
	}
	
	public function ReporteFacturacion($data){
	    $this->GetTokenFact();
	    
	    $result='';
		$url=$this->ruta_api_fact.'Facturacion/'.$this->nit."/".$this->token;
		$data_json=json_encode($data);
	//echo $url; exit;
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HTTPHEADER , array('Content-Type: application/json','Content-Length: ' . strlen($data_json)));
		curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($curl, CURLOPT_POSTFIELDS,$data_json);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($curl);
		curl_close($curl);
		return json_decode($result,true);
	}
	
	public function ConsultaProgramacion($prescripcion){
		$result='';
		
		$url=$this->ruta_api.'ProgramacionXPrescripcion/'.$this->nit."/".$this->token."/".$prescripcion;
		//echo $url."<br><br>";
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($curl);
		curl_close($curl);

		return json_decode($result,true);
	}
	public function ConsultaEntrega($prescripcion){
		$result='';
		$url=$this->ruta_api.'EntregaXPrescripcion/'.$this->nit."/".$this->token."/".$prescripcion;
		//echo $url;
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($curl);
		curl_close($curl);
		return json_decode($result,true);
	}
	public function ConsultaEntregaEfectiva($prescripcion){
		$result='';
		
		$url=$this->ruta_api.'ReporteEntregaXPrescripcion/'.$this->nit."/".$this->token."/".$prescripcion;
		//echo $url;
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($curl);
		curl_close($curl);

		return json_decode($result,true);
	}
	
		/**
		 * Anula una Factura
		 * 
		 * Este metodo consulta una api de mipres para anular una entrega
		 * @param mixed $idfactura
		 * 
		 * @return object
		 */
		public function AnularReporteFacturacion($idfactura){
	    	$this->GetTokenFact();
			$result='';
			$url=$this->ruta_api_fact.'FacturacionAnular/'.$this->nit."/".$this->token.'/'.$idfactura;
		    $cliente = curl_init($url);
		    curl_setopt($cliente, CURLOPT_HTTPHEADER , array('Content-Type: application/json','Content-Length: ' . strlen($idfactura)));
			curl_setopt($cliente, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($cliente,CURLOPT_CUSTOMREQUEST, 'PUT');
			$result = curl_exec($cliente);			
			curl_close($cliente);	

			return json_decode($result,true);
		}
	
}
