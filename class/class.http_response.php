<?php
class HttpResponse
{
	private $Web_Response = array('codigo' => 'success', 'titulo' => 'Exito', 'mensaje' => 'Solicitud Exitosa', 'query_result' => '');
	private $Response_Codes = array('success', 'error', 'warning', 'info');

	function GetRespuesta()
	{
		return $this->Web_Response;
	}

	function SetRespuesta($codigo, $titulo, $mensaje)
	{
		$response_code_count = count($this->Response_Codes);

		if ($codigo < 0 || $codigo > $response_code_count) {
			throw new OutOfRangeException("Código requerido fuera de rango");
		} else {
			$this->Web_Response['codigo'] = $this->Response_Codes[$codigo];
			$this->Web_Response['titulo'] = $titulo;
			$this->Web_Response['mensaje'] = $mensaje;
		}
	}

	function SetDatosRespuesta($data)
	{
		$this->Web_Response['query_result'] = $data;
	}
}
