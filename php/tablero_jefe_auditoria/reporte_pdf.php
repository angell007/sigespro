<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');

$condicion = '';

if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
	$fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
	$fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
	$condicion .= 'WHERE DATE_FORMAT(DC.Fecha, "%Y-%m-%d") BETWEEN "'.$fecha_inicio.'" AND "'. $fecha_fin.'"';
}
if (isset($_REQUEST['dep']) && $_REQUEST['dep'] != "") {
	if ($condicion != "") {
		$condicion .= " AND D.Id_Departamento=$_REQUEST[dep]";
	} else {
		$condicion .= "WHERE D.Id_Departamento=$_REQUEST[dep]";
	}
}
if (isset($_REQUEST['pto']) && $_REQUEST['pto'] != "") {
	if ($condicion != "") {
		$condicion .= " AND DC.Id_Punto_Dispensacion=$_REQUEST[pto]";
	} else {
		$condicion .= "WHERE DC.Id_Punto_Dispensacion=$_REQUEST[pto]";
	}
}
if (isset($_REQUEST['func']) && $_REQUEST['func'] != "") {
	if ($condicion != "") {
		$condicion .= " AND D.Identificacion_Funcionario=$_REQUEST[func]";
	} else {
		$condicion .= "WHERE D.Identificacion_Funcionario=$_REQUEST[func]";
	}
}

/* FUNCIONES BASICAS */
function fecha($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	return $date[2] . "/". $date[1] ."/". $date[0];
}
/* FIN FUNCIONES BASICAS*/

$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);

ob_start(); // Se Inicializa el gestor de PDF

/* HOJA DE ESTILO PARA PDF*/
$style='<style>
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
.titular{
    font-size: 11px;
    text-transform: uppercase;
    margin-bottom: 0;
  }
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/

$query=" SELECT DC.Cuota_Ingresada,DP.*, CONCAT(F.Nombres,' ', F.Apellidos) as Funcionario, D.Nombre as Departamento, PD.Nombre as Punto, (SELECT CONCAT_WS(' ',P.Primer_Nombre,P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) FROM Paciente P WHERE P.Id_Paciente=DP.Numero_Documento) AS Paciente, (SELECT R.Nombre FROM Paciente P INNER JOIN Regimen R ON P.Id_Regimen=R.Id_Regimen WHERE P.Id_Paciente=DP.Numero_Documento) AS Regimen, (SELECT N.Nombre FROM Paciente P INNER JOIN Nivel N ON P.Id_Nivel=N.Id_Nivel WHERE P.Id_Paciente=DP.Numero_Documento) as Nivel
FROM Dispensacion DP
INNER JOIN Diario_Cajas_Dispensacion DC
ON DP.Id_Diario_Cajas_Dispensacion=DC.Id_Diario_Cajas_Dispensacion
INNER JOIN Funcionario F
ON DC.Identificacion_Funcionario=F.Identificacion_Funcionario 
INNER JOIN Punto_Dispensacion PD 
ON DC.Id_Punto_Dispensacion=PD.Id_Punto_Dispensacion
INNER JOIN Departamento D 
ON PD.Departamento=D.Id_Departamento ".$condicion." 
ORDER BY DP.Id_Diario_Cajas_Dispensacion DESC";


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$dispensaciones= $oCon->getData();
unset($oCon);

$products = [];

$rowspan = 0;
$rowspan_final = 0;

foreach ($dispensaciones as $i => $value) {
			
	if (array_key_exists($value["Id_Diario_Cajas_Dispensacion"], $products)) {
		$array = [
		
			"Codigo" => $value["Codigo"], 
			"Numero_Documento" => $value["Numero_Documento"],
			"Paciente" => $value["Paciente"],
			"Nivel" => $value["Nivel"],
			"Regimen" => $value["Regimen"],
			"Cuota"=>$value["Cuota"],
			"Punto"=>$value["Punto"],
			"Cuota_Ingresada"=>$value["Cuota_Ingresada"]
		];
		array_push($products[$value["Id_Diario_Cajas_Dispensacion"]], $array);
	} else {
		$products[$value["Id_Diario_Cajas_Dispensacion"]] = [
			[
			
				"Codigo" => $value["Codigo"], 
				"Numero_Documento" => $value["Numero_Documento"],
				"Paciente" => $value["Paciente"],
				"Nivel" => $value["Nivel"],
				"Regimen" => $value["Regimen"],
				"Cuota"=>$value["Cuota"],
				"Punto"=>$value["Punto"],
				"Cuota_Ingresada"=>$value["Cuota_Ingresada"]
			]
		];
	}
}

    $contenido = '
    	<table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
				<tr>
					<th style="width:80px;font-weight:bold;text-align:center;background:#cecece;;border:1px solid #cccccc;">Codigo</th>
					<th style="width:150px;font-weight:bold;text-align:center;background:#cecece;;border:1px solid #cccccc;">Identificación Paciente</th>
					<th style="width:450px;font-weight:bold;text-align:left;background:#cecece;;border:1px solid #cccccc;">Paciente</th>
					<th style="width:50px;font-weight:bold;text-align:center;background:#cecece;;border:1px solid #cccccc;">Nivel</th>
					<th style="width:70px;font-weight:bold;text-align:center;background:#cecece;;border:1px solid #cccccc;">Regimen</th>
					<th style="width:70px;font-weight:bold;text-align:right;background:#cecece;;border:1px solid #cccccc;">Cuota</th>
					<th style="width:450px;font-weight:bold;text-align:center;background:#cecece;;border:1px solid #cccccc;">Punto</th>
					<th style="width:80px;font-weight:bold;text-align:center;background:#cecece;;border:1px solid #cccccc;">Cuota Ingresada</th>
				</tr>';

			$totalCuotaIngresa = 0;

			foreach ($products as $categoria => $prod) {
				//$contenido .= '<tr>';
				foreach ($prod as $i => $value) {
					$contenido .= '<tr>';
					$contenido .= '<td style="text-align:center;border:1px solid #cccccc;">'.$value['Codigo'] .' -- '.$i.'</td>';
					$contenido .= '<td style="text-align:center;border:1px solid #cccccc;">'. $value['Numero_Documento'] .'</td>';
					$contenido .= '<td style="text-align:left;border:1px solid #cccccc;">'. $value['Paciente'] .'</td>';
					$contenido .= '<td style="text-align:center;border:1px solid #cccccc;">'. $value['Nivel'] .'</td>';
					$contenido .= '<td style="text-align:center;border:1px solid #cccccc;">'. $value["Regimen"] .'</td>';
					$contenido .= '<td style="text-align:right;border:1px solid #cccccc;">'. $value["Cuota"] .'</td>';
					$contenido .= '<td style="text-align:center;border:1px solid #cccccc;">'. $value["Punto"] .'</td><td></td></tr>';

					$totalCuotaIngresa += $value["Cuota_Ingresada"];

					/* if ($i == 0) {

						$rowspan_final = calcularRowsSpan(count($prod), $rowspan);

						$rowspan = $rowspan_final;

						$contenido.='<td style="background-color:#f3f3f3;border:1px solid #cccccc;text-align:center;" rowspan="'.$rowspan_final.'">'. $value["Cuota_Ingresada"] . '</td>  </tr>';
						
					} elseif (is_int(($i+1)/39) && $i != (count($prod)-1)) {
						$rowspan_final = calcularRowsSpan(count($prod), $rowspan);

						$rowspan = $rowspan_final;

						$contenido.='<td style="background-color:#f3f3f3;border:1px solid #cccccc;text-align:center;" rowspan="'.$rowspan_final.'">'. $value["Cuota_Ingresada"] . '</td>  </tr>';
					} else{
						$contenido .= '</tr>';
					} */
				}
			
			
				
			}

		$contenido .= '
			<tr>
				<td colspan="7" style="border:1px solid #cccccc;"><strong>Totales:</strong></td>
				<td align="right" style="border:1px solid #cccccc;">$.'.number_format($totalCuotaIngresa,2,",",".").'</td>

			</tr>
		';

	$contenido .= '
		</table>';
	
 
/* FIN SWITCH*/

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="width:200px;" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'/IMAGENES/LOGOS/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:410px;font-weight:thin;font-size:14px;line-height:20px;">
                    '.$config["Nombre_Empresa"].'<br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    TEL: '.$config["Telefono"].'
                  </td>
                </tr>
              </tbody>
            </table><hr style="border:1px dotted #ccc;width:730px;">';
/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm">
                <div class="page-content" >'.
                    $cabecera.
                    $contenido.'
                </div>
            </page>';
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('L', array(405,180), 'Es', true, 'UTF-8', array(5, 5, 2, 8));
    $html2pdf->writeHTML($content);
    $direc = 'Plan_de_Cuentas('.date("d-m-Y").').pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

function TransformarValor($value){
	if ($value == 'N' || $value == '' || is_null($value)) {
		return 'NO';
	}else{
		return 'SI';
	}
}

function calcularRowsSpan($resulSet, $rowsSpanAnt) {

	$rowspanfinal;

	if ($resulSet > 39) {
		$rowspan_final = $resulSet - $rowsSpanAnt;
	} elseif (($resulSet + $rowsSpanAnt) > 39) {
		$rowspan_final = 39 - $rowsSpanAnt;
	} else {
		$rowspan_final = $resulSet;
	}

	return $rowspan_final;
}

?>