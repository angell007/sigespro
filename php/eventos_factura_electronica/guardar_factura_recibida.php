<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');

include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';

$zip = new ZipArchive;
$comprimido = $zip->open($_FILES['archivo']['tmp_name']);
if ($comprimido === true) {
      // Declaramos la carpeta que almacenara ficheros descomprimidos temporalmente
      $carpeta = uniqid();
      $zip->extractTo("./$carpeta");
      $zip->close();

      $gestor = opendir($carpeta);
      $xml = '';
      $respuesta['tipo'] = 'success';
      $respuesta['Message'] = '';
      while (($archivo = readdir($gestor)) !== false) {

            $ruta_completa = $carpeta . "/" . $archivo;

            // Se muestran todos los archivos y carpetas excepto "." y ".."
            if ($archivo != "." && $archivo != "..") {
                  if (!is_dir($ruta_completa)) {
                        $extension = pathinfo($ruta_completa, PATHINFO_EXTENSION);
                        if ($extension == 'xml') {
                              $xml = file_get_contents($ruta_completa);



                              if ($xml == '') {

                                    // $respuesta['tipo'] = 'error';
                                    $respuesta['Message'] .= 'No se encontró informacion';
                                    // echo json_encode($respuesta);
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
                                    preg_match('/<cbc:ProfileID>(.*?)<\/cbc:ProfileID>/is', $xml, $tipo1);
                                    preg_match('/<cac:PaymentMeans>(.*?)<\/cac:PaymentMeans>/is', $xml, $pago);
                                    preg_match('/<cbc:ID>(.*?)<\/cbc:ID>/is', $pago[1], $pago);

                              
                                    // if ($tipo[1] == '01') {
                                          if ($tipo[1] == '01' && $pago[1] == '1') {
                                                $respuesta['tipo'] = 'error';
                                                $respuesta['Message'] .= "La Factura $codigo[1] no es venta a crédito <br>";
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
                                                            $respuesta['Message'] .= "La Factura $ant[Codigo_Factura]  Ya se encuentra en el sistema <br>";
                                                      } else {
                                                            foreach ($factura as $key => $value) {
                                                                  $oItem->$key = $value;
                                                            }
                                                            $oItem->save();
                                                            $id_Factura = $oItem->getId();

                                                            if ($id_Factura) {
                                                                  $respuesta['tipo'] = 'success';
                                                                  $respuesta['Message'] .= "Correcto, se ha registrado la factura $factura[Codigo_Factura]  <br>";
                                                            } else {
                                                                  $respuesta['tipo'] = 'error';
                                                                  $respuesta['Message'] .= 'Ha ocurrido un error al intentar guardar la informacion';
                                                            }
                                                      }
                                                } else {
                                                      $respuesta['tipo'] = 'error';
                                                      $respuesta['Message'] .= "La factura $codigo[1] no está dirigida a Proh o la Fecha de la fatura es anterior a 2022-11-01 <br>";
                                                }
                                          }
                                    // } else {
                                    //       $respuesta['tipo'] = 'error';
                                    //       $respuesta['Message'] .= 'El tipo de documento no es una factura electronica  <br>';
                                    // }
                              }
                        }
                        unlink($ruta_completa);
                  } else {
                        rmdir($ruta_completa);
                  }
            }
      }
      rmdir("./$carpeta");
} else {
      $respuesta['tipo'] = 'error';
      $respuesta['Message'] = 'No se ha cargado un archivo válido';
}
echo json_encode($respuesta);
