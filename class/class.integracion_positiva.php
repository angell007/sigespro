<?php
include_once('class.complex.php');
include_once('class.consulta.php');
date_default_timezone_set("America/Bogota");

class Fase2
{
	private $url_test = "https://cuidatesting.conexia.com.co";
	private $url_prueba = "https://cuidacapacitacion.conexia.com.co";
	private $url_produccion = "https://positivacuida.positiva.gov.co";
	private $url_autenticacion = "";
	private $url_dispensacion = "";
	private $user = "USERPROH";
	private $pass = "MKsB99TBN&q6GHoQE@@neg";
	private $respuesta = [];
	private $json = [];
	private $soporte ;
	private $fecha_dispensacion ;
	private $files, $cantidad_entregada, $causal, $observacion, $tipo_archivo, $numeroAutorizacion, $producto_dispensado, $postFiles, $funcionario;


	/**
	 * @param string $numeroAutorizacion numero de autorizacion enviada por Positiva
	 * @param array $file debe contener un unico archivo en la clave 'files'
	 * @param string $cantidad_entregada cantidad que se dispensa de lo autorizado
	 * @param string $causal Codigo que representa la causal de entrega o no entrega. Ej: 'ETO', 'GFA'
	 * @param string $observacion Se requiere enviar una observacion ej: 'Contacto Fallido'
	 * @param string $tipo_archivo Para determinar si el tipo de Archivo es un soporte de entrega u otro soporte que se requiera
	 */
	function __construct($numeroAutorizacion, $file, $cantidad_entregada, $causal = null, $observacion = 'Entrega exitosa', $tipo_archivo = 'SE', $funcionario = '12345', $fecha_dispensacion = null)
	{
		$this->numeroAutorizacion = $numeroAutorizacion;
		$this->cantidad_entregada = $cantidad_entregada;
		$this->causal = $causal;
		$this->observacion = $observacion;
		$this->tipo_archivo = $tipo_archivo;
		$this->files = $file;
		$this->funcionario = $funcionario;
		$this->fecha_dispensacion = $fecha_dispensacion;
		$base = $this->url_produccion;

		$this->url_autenticacion =  $base. '/integracion/api/autenticar';
		$this->url_dispensacion = $base . '/integracion/api/autorizaciones/dispensacion';
	}

	/**
	 * Envio de informacion de la integracion al endpoint de positiva
	 */
	public function Enviar()
	{

		$comunicacion = $this->getToken();
		$comunicacion = (array) json_decode($comunicacion, true);
		$this->respuesta = $comunicacion;
		$this->producto_dispensado = $this->getDatosAutorizacion($this->numeroAutorizacion);
		if ($this->producto_dispensado) {
			$this->respuesta = $this->enviarComunicacionPositiva($comunicacion['data']['token'], $this->producto_dispensado);
			$this->guardarRespuesta($this->respuesta);
		}
		return $this->respuesta;
	}
	private function getDatosAutorizacion($numeroAutorizacion)
	{

		$query = "SELECT  PDA.serviciosAutorizados, 
			PDA.numeroAutorizacion as Autorizacion,
			PDA.RLnumeroSolicitudSiniestro as Solicitud, 
			P.Nombre_Comercial as Nombre_Producto,
			PDA.*,
			PDA.fechaHoraAutorizacion AS Fecha_Dispensacion,
			(
			CASE
				WHEN PNP.Precio IS NOT NULL THEN PNP.Precio
				WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio
				ELSE 0
			END
			) AS Precio_Venta_Factura, 
			PDA.AFtipoDocumento AS Tipo_Documento, 
			PDA.AFnumeroDocumento AS Id_Paciente, 
			PRG.Precio AS Regulado, 
			PNP.Precio AS Contrato
			
			FROM 		Positiva_Data PDA
			Left JOIN Paciente PAC ON PAC.Id_Paciente = PDA.AFnumeroDocumento
			Left Join Producto_Dispensacion PD on PD.Id_Dispensacion = PDA.Id_Dispensacion 
			Left Join Producto P on P.Id_Producto = PD.Id_Producto
			LEFT JOIN Precio_Regulado PRG ON PD.Cum = PRG.Codigo_Cum
			LEFT JOIN(
				SELECT
				PNP.*, LPN.Id_Cliente as Nit
				FROM Producto_Contrato PNP
				INNER JOIN Contrato LPN ON LPN.Id_Contrato = PNP.Id_Contrato
				WHERE LPN.Tipo_Contrato = 'Eps'
			) PNP ON PD.Cum = PNP.Cum   and PNP.NIT = PAC.Nit
			Where PDA.numeroAutorizacion = $numeroAutorizacion ";
		$oCon = new consulta();
		$oCon->setQuery($query);
		$producto = $oCon->getData();

		$producto['serviciosAutorizados'] = str_replace(["\n", "\r", "\t"], " ", $producto['serviciosAutorizados']);
		$producto['serviciosAutorizados'] = (array) json_decode($producto['serviciosAutorizados'], true);
		return $producto;
	}

	private function enviarComunicacionPositiva($token, $producto_dispensado)
	{
		if (count($producto_dispensado['serviciosAutorizados']) > 0) {
			$codigo = $producto_dispensado['serviciosAutorizados'][0]['codigo'];
		} else {
			$codigo = ($producto_dispensado['Cum_Autorizado'] != '' &&  $producto_dispensado['Cum_Autorizado'] != '') ?  $producto_dispensado['Cum_Autorizado'] != '' : $producto_dispensado['Cum'];
		}
		$producto_dispensado['Fecha_Dispensacion'] = $this->fecha_dispensacion? $this->fecha_dispensacion: $producto_dispensado['Fecha_Dispensacion'];
		$fecha_dispensacion = date('Y-m-d', strtotime($producto_dispensado['Fecha_Dispensacion'])) . ' 00:00:00';
		$formulada = $producto_dispensado['serviciosAutorizados'][0]['cantidad'];
		$cantidad_reportada = $this->cantidadReportada();
		$faltante = $formulada - $cantidad_reportada ;
		$faltante <0 ? $faltante = 0 : $faltante;

		if ($this->cantidad_entregada>0) {
		    $this->cantidad_entregada = $this->cantidad_entregada > $faltante? $faltante: $this->cantidad_entregada;


			$causal = ($faltante > $this->cantidad_entregada) ? 'ENA' : 'ETO';

			if ($causal =='ENA') {
				$this->cantidad_entregada =$cantidad_reportada? $formulada - $cantidad_reportada: $this->cantidad_entregada;
				$proxima_Entrega = date('Y-m-d H:i:s', strtotime(' +2 day'));
				$servicios_dispensados['fechaProximaEntrega'] = $proxima_Entrega;
				// $causal= 'IAD';
			}
			$this->causal = $this->causal ? $this->causal : $causal;
		}
		$this->cantidad_entregada=$this->cantidad_entregada!=='' ?$this->cantidad_entregada: $faltante;

		$dispensacion['tipoDocumento'] = $producto_dispensado['Tipo_Documento'];
		$dispensacion['numeroDocumento'] = $producto_dispensado['Id_Paciente'];
		$dispensacion['numeroSolicitud'] = $producto_dispensado['Solicitud'];
		$this->numeroAutorizacion = $producto_dispensado['Autorizacion'];
		$dispensacion['numeroAutorizacion'] = $producto_dispensado['Autorizacion'];
		$servicios_dispensados['codigo'] = $codigo;
		// $cl = $producto_dispensado['serviciosAutorizados'][0]['codigoLegal'] != '' ? $producto_dispensado['serviciosAutorizados'][0]['codigoLegal'] : $producto_dispensado['Cum'];
		$servicios_dispensados['codigoLegal'] = $codigo;
		$servicios_dispensados['descripcion'] = ($producto_dispensado['serviciosAutorizados'][0]['descripcion']);
		$servicios_dispensados['cantidadEntregada'] = $this->cantidad_entregada;
		$servicios_dispensados['fechaDispensacion'] = $fecha_dispensacion;
		$servicios_dispensados['valorUnitario'] = $producto_dispensado['Precio_Venta_Factura'];
		$servicios_dispensados['valorTotal'] = $producto_dispensado['Precio_Venta_Factura'] * $this->cantidad_entregada;



		$servicios_dispensados['causal'] = $this->causal ? $this->causal : $causal;
		$servicios_dispensados['observacion'] = $this->observacion;

		$adjuntos = null;
		$dispensacion['serviciosDispensados'][] = $servicios_dispensados;

		$ext = '';
		
		if ($this->files['files']) {
			$formato = explode('/', $this->files['files']['type']);
			$adjuntos['nombreArchivo'] = str_replace($ext, '',  $this->files['files']['name']);
			$adjuntos['formato'] = strtoupper($formato[1]);
			$adjuntos['tipoDocumento'] = $formato[1] != "" ? $this->tipo_archivo : "";
			$adjuntos['idArchivo'] = null;
			$dispensacion['adjuntos'] = array();

			// echo json_encode($this->files['files']); exit;
		}
		if($adjuntos){
		    
		$dispensacion['adjuntos'][]= $adjuntos;
		}
		else{
	        $dispensacion['adjuntos'] = [];
		}

		$this->json = (array('dispensacion' => json_encode($dispensacion)));
		return $this->do_post_request($this->url_dispensacion, $this->json, $token, $this->files);
	}

	private function do_post_request($url, $postdata, $token, $files = null)
	{

		$data = "";
		$boundary = "------" . strtoupper(substr(md5(random_int(0, 32000)), 0, 10));
		// if (count($this->files)) {

		foreach ($postdata as $key => $val) {
			$data .= "--$boundary\n";
			$data .= "Content-Disposition: form-data; name=\"" . $key . "\"\n\n" . ($val) . "\n";
		}
		$this->postFiles = '';
		$name = '';
		$key = 'files';
		$data .= "--$boundary\n";
		$this->postFiles .= "Content-Disposition: form-data; name=\"{$key}\"; ";
		foreach ($files as $file) {
			$name = $file ? $file['name'] : $name;
			if ($file) {
				$this->soporte = $file['tmp_name'];
				$fileContents = file_get_contents($file['tmp_name']);
				$this->postFiles .= "filename=\"{$name}\"\n";
				$this->postFiles .= "Content-Type: $file[type] \r\n";
				$this->postFiles .= "Content-Transfer-Encoding: binary\n\n";
				$this->postFiles .= $fileContents . "\n";
			} else {
				// $data .= "--$boundary--\n";
			}
		}
		$this->postFiles .= "--$boundary--\n";
		$data .= $this->postFiles;
		$header = '';
		$header = "Authorization: $token\r\n";
		$header .= "Content-Type: multipart/form-data;boundary=$boundary\r\n";
		$headers = array(
			"Authorization: $token",
			"Content-Type: multipart/form-data;boundary=$boundary"
		);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			$response["Estado"] = "error";
			$response["Error"] = '# Error : ' . curl_error($ch);
		} elseif ($result) {
			$response = json_decode($result, true);
		}
		if (!$response){
			$response['httpResponseCode'] = curl_getinfo($ch, CURLINFO_RESPONSE_CODE  ); 
			$response['error_mensaje']= $result;
			$response['URL']= $this->url_dispensacion;
		}
		return $response;
	}
	private function do_post_request2($url, $postdata, $token, $files = null)
	{

		$data = "";
		$boundary = "------" . strtoupper(substr(md5(random_int(0, 32000)), 0, 10));
		// if (count($this->files)) {

		foreach ($postdata as $key => $val) {
			$data .= "--$boundary\n";
			$data .= "Content-Disposition: form-data; name=\"" . $key . "\"\n\n" . ($val) . "\n";
		}
		$this->postFiles = '';
		$name = '';
		$key = 'files';
		$data .= "--$boundary\n";
		$this->postFiles .= "Content-Disposition: form-data; name=\"{$key}\"; ";
		foreach ($files as $file) {
			$name = $file ? $file['name'] : $name;
			if ($file) {
				$this->soporte = $file['tmp_name'];
				$fileContents = file_get_contents($file['tmp_name']);
				$this->postFiles .= "filename=\"{$name}\"\n";
				$this->postFiles .= "Content-Type: $file[type] \r\n";
				$this->postFiles .= "Content-Transfer-Encoding: binary\n\n";
				$this->postFiles .= $fileContents . "\n";
			} else {
				// $data .= "--$boundary--\n";
			}
		}
		$this->postFiles .= "--$boundary--\n";
		$data .= $this->postFiles;
		$header = '';
		$header = "Authorization: $token\r\n";
		$header .= "Content-Type: multipart/form-data;boundary=$boundary\r\n";
		$headers = array(
			"Authorization: $token",
			"Content-Type: multipart/form-data;boundary=$boundary"
		);
		
// 		echo $data; exit;

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$result = curl_exec($ch);
		
		if (curl_errno($ch)) {
			$response["Estadoe"] = "error";
			$response["Error"] = '# Error : ' . curl_error($ch);
		} elseif ($result) {
			$response = json_decode($result, true);
		}
		if (!$response){
			$response['httpResponseCode'] = curl_getinfo($ch, CURLINFO_RESPONSE_CODE  ); 
			$response['error_mensaje']= $result;
			$response['URL']= $this->url_dispensacion;
		}
		return $response;
	}


	private function getToken()
	{

		$payload = array('usuario' => $this->user, 'clave' => $this->pass);
		$data = json_encode(($payload));
		$headers = array(
			"Content-Type: application/json"
		);

		$result = [];
		$ch = curl_init($this->url_autenticacion);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_TIMEOUT, 100);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 100);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			$response["Estado"] = "error";
			$response["Error3"] = '# Error : ' . curl_error($ch);
		} elseif ($result) {
			return $result;
		}
		exit;
	}

	private function guardarRespuesta($error = null)
	{
	    $codigo = $error ? $error['httpResponseCode'] : $this->respuesta['httpResponseCode'];
		if($codigo ==401){
			$comunicacion = $this->getToken();
			$comunicacion =(array) json_decode($comunicacion, true);
			$this->respuesta = $this->do_post_request($this->url_dispensacion, $this->json, $comunicacion['data']['token'], $this->files);
		    $codigo = $this->respuesta['httpResponseCode'];
		}
		
		$oItem = new complex('Envio_Evento_Positiva', 'Envio_Evento_Positiva');
		$oItem->Identificacion_Funcionario = $this->funcionario;
		$oItem->Numero_Autorizacion = $this->numeroAutorizacion;
		$oItem->Cantidad_Entregada = $this->cantidad_entregada;
		$oItem->Codigo_Causal_Positiva = $this->causal;
		$oItem->Observacion = $this->observacion;
		$oItem->Json_Dispensacion = $this->json['dispensacion'];
		$oItem->Archivo = $this->soporte;
		$oItem->Respuesta = json_encode($this->respuesta);
		$oItem->Exito = $codigo;
		$oItem->Fecha_Envio = date('Y-m-d H:i:s');
		$oItem->save();
	}

	private function cantidadReportada()
	{
		$query = "SELECT SUM(Cantidad_Entregada) as Cantidad from Envio_Evento_Positiva
			Where Exito like '200'
			and Numero_Autorizacion = $this->numeroAutorizacion";
		$oCon = new consulta();
		$oCon->setQuery($query);
		$cantidad = (int)$oCon->getData()['Cantidad'];

		return $cantidad;
	}
	
	public function reProcesarEnvio(){
		$id= $this->numeroAutorizacion;
		$comunicacion = $this->getToken();
		$comunicacion =(array) json_decode($comunicacion, true);
		$oItem = new complex('Envio_Evento_Positiva', 'Id_Envio_Evento_Positiva', $id);
		$datos = $oItem->getData();

		$row = [];
		if( $datos['Archivo']){
			$ruta= $datos['Archivo'];
			$row['name'] = pathinfo($ruta, PATHINFO_BASENAME);
			$row['type'] = "application/".pathinfo($ruta, PATHINFO_EXTENSION);
			$row['tmp_name'] = $ruta; 
		} 
		


		$files['files'] = $row;
		$datos['Json_Dispensacion'] = str_replace(["\n", "\r", "\t"], " ", $datos['Json_Dispensacion']);
		
		$json = json_decode($datos['Json_Dispensacion'], true);
		$producto_dispensado = $this->getDatosAutorizacion($datos['Numero_Autorizacion']);



		
		if(!$json['serviciosDispensados'][0]['codigo'] || !$json['serviciosDispensados'][0]['descripcion'] || !$json['serviciosDispensados'][0]['codigoLegal'] ){
			if (count($producto_dispensado['serviciosAutorizados']) > 0) {
				$codigo = $producto_dispensado['serviciosAutorizados'][0]['codigo'];
			} else {
				$codigo = ($producto_dispensado['Cum_Autorizado'] != '' &&  $producto_dispensado['Cum_Autorizado'] != '') ?  $producto_dispensado['Cum_Autorizado'] != '' : $producto_dispensado['Cum'];
			}
			$json['serviciosDispensados'][0]['codigo'] = $codigo;
			// $cl = $producto_dispensado['serviciosAutorizados'][0]['codigoLegal'] != '' ? $producto_dispensado['serviciosAutorizados'][0]['codigoLegal'] : $producto_dispensado['Cum'];
			$json['serviciosDispensados'][0]['codigoLegal'] = $codigo;
			$json['serviciosDispensados'][0]['descripcion'] = ($producto_dispensado['serviciosAutorizados'][0]['descripcion']);
		}
		
		// echo json_encode($json); exit
		$formulada = $producto_dispensado['serviciosAutorizados'][0]['cantidad'];
		$this->numeroAutorizacion = $datos['Numero_Autorizacion'];
		$cantidad_reportada = $this->cantidadReportada();

		$faltante = $formulada - $cantidad_reportada ;
		$faltante <0 ? $faltante = 0 : $faltante;



		if(!$json['serviciosDispensados'][0]['cantidadEntregada'] && $datos['Codigo_Causal_Positiva']=='ETO'){
			$datos['Cantidad_Entregada'] = $cantidad_reportada? $faltante: $datos['Cantidad_Entregada'];
            $json['serviciosDispensados'][0]['cantidadEntregada'] = $datos['Cantidad_Entregada'];
		}

		if($faltante < $json['serviciosDispensados'][0]['cantidadEntregada'] && $faltante >0 && $json['serviciosDispensados'][0]['cantidadEntregada']){
			$datos['Cantidad_Entregada'] = $faltante;
			$json['serviciosDispensados'][0]['cantidadEntregada'] = $datos['Cantidad_Entregada'];
		}else{
		     if(!$datos['Cantidad_Entregada']){
				$datos['Cantidad_Entregada']=0;
                $json['serviciosDispensados'][0]['cantidadEntregada'] = $datos['Cantidad_Entregada'];
			}
		}
        $json['serviciosDispensados'][0]['valorTotal'] = $json['serviciosDispensados'][0]['cantidadEntregada'] * $json['serviciosDispensados'][0]['valorUnitario'];


		// echo json_encode($producto_dispensado); exit;
		$this->json = (array('dispensacion' => json_encode($json)));
		$respuesta = $this->do_post_request2($this->url_dispensacion, $this->json, $comunicacion['data']['token'], $files);


		$codigo = $respuesta['httpResponseCode'];
		if($codigo ==401){
			$comunicacion = $this->getToken();
			$comunicacion =(array) json_decode($comunicacion, true);
			$respuesta = $this->do_post_request2($this->url_dispensacion, $this->json, $comunicacion['data']['token'], $files);
		}
		$oItem = new complex('Envio_Evento_Positiva', 'Id_Envio_Evento_Positiva', $id);
		
		$oItem->Reprocesada = $this->json['dispensacion'];
		$oItem->Json_Dispensacion = $this->json['dispensacion'];
		$respuesta ? $oItem->Exito = $respuesta['httpResponseCode'] : $this->respuesta['httpResponseCode'];
		$oItem->Respuesta = json_encode($respuesta);
		$oItem->Cantidad_Entregada = $datos['Cantidad_Entregada'];
		$oItem->save();
		echo json_encode($respuesta);
		return $respuesta;
	}
}

