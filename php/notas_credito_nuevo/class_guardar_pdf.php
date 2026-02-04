<?php


require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');


class Nota_Credito_Pdf
{

	private $id, $ruta;
	public function __construct($id, $ruta, $tipo = 'Nota_Credito')
	{
		$this->id = $id;
		$this->ruta = $ruta;
		$this->tipo = $tipo;
	}

	private function fecha($str)
	{
		$parts = explode(" ", $str);
		$date = explode("-", $parts[0]);
		return $date[2] . "/" . $date[1] . "/" . $date[0];
	}
	/* FIN FUNCIONES BASICAS*/
	public function generarPdf()
	{
		/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
		$oItem = new complex('Configuracion', "Id_Configuracion", 1);
		$config = $oItem->getData();
		unset($oItem);
		/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

		/* DATOS DEL ARCHIVO A MOSTRAR */
		$oItem = new complex($this->tipo, "Id_" . $this->ipo, $this->id);
		$data = $oItem->getData();
		unset($oItem);
		/* FIN DATOS DEL ARCHIVO A MOSTRAR */

		ob_start(); // Se Inicializa el gestor de PDF

		/* HOJA DE ESTILO PARA PDF*/
		$style = '<style>
			.page-content{
			width:750px;
			}
			.row{
			display:inlinie-block;
			width:750px;
			}
			.td-header{
			font-size:15px;
			line-height: 20px;
			}
			</style>';
		/* FIN HOJA DE ESTILO PARA PDF*/

		/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */
		switch ($this->tipo) {
			case 'Nota_Credito': {



					$query = 'SELECT * FROM Nota_Credito_Global 
           				 WHERE Id_Nota_Credito_Global = ' . $this->id;
					$oCon = new consulta();
					$oCon->setQuery($query);
					$nota_credito = $oCon->getData();


					$query = "SELECT F.Codigo, R.Tipo_Resolucion 
						FROM  $nota_credito[Tipo_Factura]  F
						INNER JOIN Resolucion R ON R.Id_Resolucion = F.Id_Resolucion

						WHERE F.Id_$nota_credito[Tipo_Factura] = $nota_credito[Id_Factura]   AND R.Tipo_Resolucion = 'Resolucion_Electronica'       
						";

					$oCon = new consulta();
					$oCon->setQuery($query);
					$res = $oCon->getData();

					unset($oCon);
					if ($nota_credito) {
						/*  $nota_credito['Observaciones'] = utf8_decode($nota_credito['Observaciones'] ); */

						$query = ' SELECT P.* , IFNULL(C.Nombre," ") AS Motivo,
								( (P.Impuesto)/100) * ( P.Cantidad * (P.Precio_Nota_Credito) ) as Total_Impuesto
							FROM Producto_Nota_Credito_Global P
								LEFT JOIN Causal_No_Conforme C ON C.Id_Causal_No_Conforme = P.Id_Causal_No_Conforme
							WHERE P.Id_Nota_Credito_Global = ' . $nota_credito['Id_Nota_Credito_Global'];
						$oCon = new consulta();

						$oCon->setTipo('Multiple');
						$oCon->setQuery($query);
						$descripciones_nota = $oCon->getData();
						unset($oCon);



						//decodificar caracteres especiales 
						/*     foreach ($descripciones_nota as $key => $descripcion) {
            # code...
            $descripciones_nota[$key]['Observacion'] = utf8_decode($descripcion['Observacion']);
        } */

						#Factura datos
						$tercero = 'Cliente';
						if ($nota_credito['Tipo_Factura'] == 'Documento_No_Obligados') {
							$tercero = 'Proveedor';
						}
						$query = "SELECT Id_$nota_credito[Tipo_Factura] AS Id_Factura, Codigo , Fecha_Documento,  Id_$tercero";

						if ($nota_credito['Tipo_Factura'] == 'Factura_Administrativa' || $nota_credito['Tipo_Factura'] == 'Documento_No_Obligados') {
							#
							$query .= ", Tipo_$tercero ";
						}



						#dato factura
						$query .= ' FROM ' . $nota_credito['Tipo_Factura'] . ' 
        						WHERE Id_' . $nota_credito['Tipo_Factura'] . ' = ' . $nota_credito['Id_Factura'];
						$oCon = new consulta();
						$oCon->setQuery($query);

						$nota_credito['Factura'] = $oCon->getData();
						unset($oCon);

						#dato cliente

						if ($nota_credito['Tipo_Factura'] == 'Factura_Administrativa') {
							#
							$query = $this->queryClientesFacturaAdministrativa($nota_credito['Factura']['Tipo_Cliente'], $nota_credito['Factura']['Id_Cliente']);
						} else if ($nota_credito['Tipo_Factura'] == 'Documento_No_Obligados') {

							$query = $this->queryClientesFacturaAdministrativa($nota_credito['Factura']['Tipo_Proveedor'], $nota_credito['Factura']['Id_Proveedor']);
						} else {
							$query = $this->queryClientesFacturaAdministrativa('Cliente', $nota_credito['Factura']['Id_Cliente']);
						}

						#dato factura

						$oCon = new consulta();
						$oCon->setQuery($query);

						$nota_credito['Cliente'] = $oCon->getData();
						unset($oCon);

						$Nota_Credito = $nota_credito;
						$Productos_Nota = $descripciones_nota;
					}

					$oItem = new complex('Funcionario', "Identificacion_Funcionario", $Nota_Credito["Id_Funcionario"]);
					$recibe = $oItem->getData();
					unset($oItem);

					$codigos = '
						<span style="margin:5px 0 0 0;font-size:16px;line-height:10px;">Nota Crédito Electronica</span>
						<h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">' . $Nota_Credito["Codigo"] . '</h3>
						<h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">' . $this->fecha($Nota_Credito["Fecha"]) . '</h5> ';
					$contenido = '<table style="">
						<tr>
						<td style="width:720px; padding-right:0px;">
							<table cellspacing="0" cellpadding="0" style="text-transform:uppercase;">
								<tr>
								<th  style=" width:230px;font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Cliente</th>
								<th  style=" width:250px;font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Factura</th>
								<th  style=" width:250px;font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Fecha Factura</th>
								</tr>
								<tr>
								<td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">
								' . $Nota_Credito["Cliente"]['Nombre_Cliente'] . '
								</td>
								<td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc; text-align:center;">
								' . $Nota_Credito["Factura"]['Codigo'] . '
								</td>
								<td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">
								' . $this->fecha($Nota_Credito['Factura']['Fecha_Documento']) . '
								</td>
								</tr>
							</table>
						</td>
						</tr>
					</table>
					<table style="margin-top:10px">
						<tr>
						<td style="font-size:10px;width:710px;background:#e9eef0;border-radius:5px;padding:8px;">
							<strong>Observaciones</strong><br>
							' . $Nota_Credito["Observaciones"] . '
						</td>
						</tr>
					</table>
					<table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
						<tr>
						<td style="width:10px;background:#cecece;;border:1px solid #cccccc;"></td>
						<td style="width:280px;max-width:320px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
							Producto
						</td>
						
						<td style="width:100;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
							Motivo
						</td>
						<td style="width:100px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
							Observaciones
						</td>
						<td style="width:30px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
							Iva
						</td>
						<td style="width:65px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
							T. Iva
						</td>
						<td style="width:95px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
							Total
						</td>
						
						</tr>';

					$max = 0;
					$total = 0;
					$total_iva = 0;
					foreach ($Productos_Nota as $prod) {
						$max++;
						$contenido .= '<tr>
							<td style="vertical-align:middle;width:10px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">' . $max . '</td>
							<td style="vertical-align:middle;padding:3px 2px;width:280px;max-width:280px;font-size:9px;text-align:left;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">' . $prod["Nombre_Producto"] . '</td>
							<td style="vertical-align:middle;width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">' . $prod["Motivo"] . '</td>
							<td style="vertical-align:middle;width:90px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">' . $prod["Observacion"] . '</td>
							
							<td style="vertical-align:middle;width:20px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">' . $prod["Impuesto"] . '</td>
							
							<td style="vertical-align:middle;width:65px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">' . number_format($prod["Total_Impuesto"], 2, ",", ".") . '</td>
							<td style="vertical-align:middle;width:95px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">' . number_format($prod["Valor_Nota_Credito"], 2, ",", ".") . '</td>
						
               					</tr>';

						$total += $prod["Valor_Nota_Credito"];
						$total_iva += $prod["Total_Impuesto"];
					}
					$contenido .= '<tr>
						<td colspan="7" style="text-align:center">CUDE:' . $nota_credito["Cude"] . '</td>
						</tr>';

					$contenido .= '</table>';

					$contenido .= '<table style="margin-top:10px;background:#e9eef0;border-radius:5px;padding:8px;padding:20px 10px">
					
						<tr style="font-size:10px">
						<td style="width:630px; font-size:10px; text-align:right"><strong>IVA</strong> </td>
						<td style="width:75px; text-align:right"> $' . number_format($total_iva, 2, ",", ".") . '</td>
						</tr>
						
						<tr style="font-size:10px">
						<td  style="width:630px; text-align:right"><strong>TOTAL</strong> </td>
						<td style="width:75px;text-align:right"> $' . number_format($total, 2, ",", ".") . '</td>
						</tr>
						</table>';


					$contenido .= '<table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
						<tr>
						<td style="width:720px;border:1px solid #cccccc;">
							<strong>Persona Elabora</strong><br><br><br><br><br>
							' . $recibe["Nombres"] . " " . $recibe["Apellidos"] . '
						</td> 
						
						</tr>
						</table>';

					break;
				}
		}
		//echo $contenido;exit;
		/* FIN SWITCH*/

		/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
		$cabecera = '<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="' . $_SERVER["DOCUMENT_ROOT"] . '/IMAGENES/LOGOS/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:350px;font-weight:thin;font-size:14px;line-height:20px;">
                    ' . $config["Nombre_Empresa"] . '<br> 
                    N.I.T.: ' . $config["NIT"] . '<br> 
                    ' . $config["Direccion"] . '<br> 
                    TEL: ' . $config["Telefono"] . '
                  </td>
                  <td style="width:185px;text-align:right">
                        ' . $codigos . '
                  </td>
                  <td style="width:130px;">';

		if ($res["Tipo_Resolucion"] != "Resolucion_Electronica") {
			$nombre_fichero =  $_SERVER["DOCUMENT_ROOT"] . 'IMAGENES/QR/' . $nota_credito["Codigo_Qr"];
		} else {
			$nombre_fichero =  $_SERVER["DOCUMENT_ROOT"] . 'ARCHIVOS/FACTURACION_ELECTRONICA/' . $nota_credito["Codigo_Qr"];
		}


		if ($nota_credito["Codigo_Qr"] == '' || !file_exists($nombre_fichero)) {

			$cabecera .= '<img src="' . $_SERVER["DOCUMENT_ROOT"] . 'assets/images/sinqr.png' . '" style="max-width:100%;margin-top:-10px;" />';
		} else {

			$cabecera .= '<img src="' . $nombre_fichero . '" style="max-width:100%;margin-top:-10px;" />';
		}

		$cabecera .= '
                  </td>
                </tr>
              </tbody>
            </table><hr style="border:1px dotted #ccc;width:730px;">';
		/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/

		/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
		$content = '<page backtop="0mm" backbottom="0mm">
                <div class="page-content" >' .
			$cabecera .
			$contenido . '
                </div>
            </page>';
		/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
		//echo $content;exit;
		try {
			/* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
			$html2pdf = new HTML2PDF('P', 'LETTER', 'Es', true, 'UTF-8', array(4, 4, 4, 4));
			$html2pdf->writeHTML($content);
			$direc = $this->ruta; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
			ob_clean();

			$html2pdf->Output($_SERVER['DOCUMENT_ROOT'] . $direc, "F"); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
		} catch (\Throwable $e) {
			echo $e;
			exit;
		}
	}
	
private function queryClientesFacturaAdministrativa($tipoCliente, $id_cliente)
{
    $query = 'SELECT ';
    if ($tipoCliente == 'Funcionario') {
        $query .= ' IFNULL(CONCAT(C.Primer_Nombre," ",C.Primer_Apellido),C.Nombres)  AS Nombre_Cliente ,
                C.Identificacion_Funcionario AS Id_Cliente,
                C.Direccion_Residencia AS Direccion_Cliente,
                IFNULL(C.Telefono,C.Celular) AS Telefono,
                " " AS Ciudad_Cliente,
                "1" AS  Condicion_Pago
                FROM ' . $tipoCliente . '  C
                WHERE Identificacion_Funcionario = ' . $id_cliente;

    } else if ($tipoCliente == 'Cliente') {
        $query .= ' C.Nombre  AS Nombre_Cliente ,
        C.Id_Cliente AS Id_Cliente,
        C.Direccion AS Direccion_Cliente,
        IFNULL(C.Telefono_Pagos,C.Celular) AS Telefono,
        M.Nombre AS Ciudad_Cliente,
        IFNULL(C.Condicion_Pago,1) AS  Condicion_Pago
        FROM Cliente  C
        INNER JOIN Municipio M ON M.Id_Municipio = C.Id_Municipio
        WHERE Id_' . $tipoCliente . ' = ' . $id_cliente;

    } else if ($tipoCliente == 'Proveedor') {
        $query .= ' C.Nombre  AS Nombre_Cliente ,
        C.Id_Proveedor AS Id_Cliente,
        C.Direccion AS Direccion_Cliente,
        IFNULL(C.Telefono,C.Celular) AS Telefono,
        M.Nombre AS Ciudad_Cliente,
        IFNULL(C.Condicion_Pago,1) AS  Condicion_Pago
        FROM Proveedor  C
        INNER JOIN Municipio M ON M.Id_Municipio = C.Id_Municipio
        WHERE Id_' . $tipoCliente . ' = ' . $id_cliente;
    }

    return $query;
}
}
