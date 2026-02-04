<?php

include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';

class Unzip
{
	private $ruta = '';
	private $zip;
	private $ruta_base = '';
	public function __construct($ruta)
	{
		$this->zip = new ZipArchive;
		$this->ruta = $ruta;
	}
	public function uncompress()
	{
		$comprimido = $this->zip->open($this->ruta);
		if ($comprimido === true) {
			// Declaramos la carpeta que almacenara ficheros descomprimidos temporalmente
			$carpeta = uniqid();
			$this->ruta_base = $carpeta;
			$this->zip->extractTo("./$carpeta");
			$this->zip->close();

			$gestor = opendir($carpeta);
			$xml = '';
			while (($archivo = readdir($gestor)) !== false) {

				$ruta_completa = $carpeta . "/" . $archivo;

				// Se muestran todos los archivos y carpetas excepto "." y ".."
				if ($archivo != "." && $archivo != "..") {
					if (!is_dir($ruta_completa)) {
						$extension = pathinfo($ruta_completa, PATHINFO_EXTENSION);
						if ($extension == 'xml') {
							$xml = file_get_contents($ruta_completa);
						}
					} else {
						rmdir($ruta_completa);
					}
					unlink($ruta_completa);
				}
			}
			// Se elimina la carpeta temporal que se había creado
			rmdir("./$carpeta");

			if ($xml == '') {

				$respuesta['tipo'] = 'error';
				$respuesta['Message'] = 'No se encontró informacion';
				return ($respuesta);
			} else {
				$factura = [];
				// echo 'ok';
				preg_match('/<cac:SenderParty>(.*?)<\/cac:SenderParty>/is', $xml, $proveedor);
				preg_match('/CompanyID(.*?)CompanyID>/is', $proveedor[1], $proveedor);
				preg_match('/>(.*?)</is', $proveedor[1], $proveedor);

				preg_match('/<cac:ReceiverParty>(.*?)<\/cac:ReceiverParty>/is', $xml, $cliente);
				preg_match('/CompanyID(.*?)CompanyID>/is', $cliente[1], $cliente);
				preg_match('/>(.*?)</is', $cliente[1], $cliente);
				preg_match("/<cbc:ParentDocumentID>(.*?)<\/cbc:ParentDocumentID>/is", $xml, $codigo);

				preg_match('/schemeName="CUFE-SHA384">(.*?)<\/cbc:UUID>/is', $xml, $reference);
				preg_match('/<cbc:IssueDate>(.*?)<\/cbc:IssueDate>/is', $xml, $date);
				preg_match('/<cbc:InvoiceTypeCode(.*?)\/cbc:InvoiceTypeCode>/is', $xml, $tipo);
				preg_match('/>(.*?)</is', $tipo[1], $tipo);
				preg_match('/<cac:PaymentMeans>(.*?)<\/cac:PaymentMeans>/is', $xml, $pago);
				preg_match('/<cbc:ID>(.*?)<\/cbc:ID>/is', $pago[1], $pago);

				if ($tipo[1] == '01' ) { // ! cualquier documento se procesa ya que se hace el filtro desde el asunto del correo
					if ($pago[1] == '1') {
						$respuesta['tipo'] = 'error';
						$respuesta['Message'] = 'La Factura no es venta a crédito';
						return ($respuesta);
					} else {
						$factura['Nit_Cliente'] = $cliente[1];
						$factura['Id_Proveedor'] = $proveedor[1];
						$factura['Codigo_Factura'] = $codigo[1];
						$factura['Cufe'] = $reference[1];
						$factura['Fecha_Factura'] = $date[1];
						$factura['Tipo_Documento'] = $tipo[1];
						if ($factura['Nit_Cliente'] == '804016084' && $factura['Fecha_Factura'] >= '2022-11-01') { // * Inicio de la resolucion de acuses de facturas

							$oItem = new complex('Factura_Recibida', 'Cufe', "$factura[Cufe]", 'Str');
							$ant = $oItem->getData();

							if ($ant) {
								$respuesta['tipo'] = 'success';
								$respuesta['Message'] = 'La Factura ' . $ant['Codigo_Factura'] . ' Ya se encuentra en el sistema';
								return ($respuesta);
							}

							foreach ($factura as $key => $value) {
								$oItem->$key = $value;
							}
							$oItem->save();
							$id_Factura = $oItem->getId();

							if ($id_Factura) {
								$respuesta['tipo'] = 'success';
								$respuesta['Message'] = 'Correcto, se ha registrado la factura ' . $factura['Codigo_Factura'];
								return ($respuesta);
							} else {
								$respuesta['tipo'] = 'error';
								$respuesta['Message'] = 'Ha ocurrido un error al intentar guardar la informacion';
								return ($respuesta);
							}
						} else {
							$respuesta['tipo'] = 'error';
							$respuesta['Message'] = 'La factura no está dirigida a Proh O la Fecha de la fatura es anterior a 2022-11-01'; // * Inicio de la resolucion de acuses de facturas'
							return ($respuesta);
						}
					}
				} else {
					$respuesta['tipo'] = 'error';
					$respuesta['Message'] = 'El tipo de documento no es una factura electronica';
					return ($respuesta);
				}
			}
		} else {
			$this->deleteDirectory($this->ruta_base);
			$respuesta['tipo'] = 'error';
			$respuesta['Message'] = 'No se ha cargado un archivo válido';
			return $respuesta;
		}
	}
	private function deleteDirectory($dir)
	{
		if (!$dh = @opendir($dir)) {
			return;
		}

		while (false !== ($current = readdir($dh))) {
			if ($current != '.' && $current != '..') {
				if (!@unlink($dir . '/' . $current)) {
					deleteDirectory($dir . '/' . $current);
				}
			}
		}
		closedir($dh);
		@rmdir($dir);
	}
}
