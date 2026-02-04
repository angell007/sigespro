<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');


/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */


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
               
        $tipo_balance = strtoupper($tipo);
        
        $codigos ='
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">SALDO ACREEDOR</h4>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">Fecha Corte. '.fecha($_REQUEST['Fechas']).'</h5>
        ';

    $tercero = datosTercero();

    $movimientos = movimientos();

    $totales = [
        "valor_facturado" => 0,
        "valor_abono" => 0,
        "valor_saldo" => 0
    ];
     
    $contenido = '
        <table style="border-collapse:collapse;border:1px solid #000;">
            <tr style="font-size:10px">
                <td style="width:80px;padding:2px;border:1px solid #000;border-right:none;border-bottom:none">
                    <strong>Nombre:</strong>
                </td>
                <td style="width:318px;padding:2px;border:1px solid #000;border-left:none;border-right:none;border-bottom:none">
                    '.$tercero['Nombre_Proveedor'].'
                </td>
                <td style="width:80px;padding:2px;border:1px solid #000;border-left:none;border-right:none;border-bottom:none">
                    <strong>Ciudad:</strong>
                </td>
                <td style="width:240px;padding:2px;border:1px solid #000;border-left:none;;border-bottom:none">
                    '.$tercero['Municipio'].'
                </td>
            </tr>
            <tr style="font-size:10px">
                <td style="width:80px;padding:2px;border:1px solid #000;border-right:none;border-bottom:none">
                    <strong>Nit:</strong>
                </td>
                <td style="width:318px;padding:2px;border:1px solid #000;border-left:none;border-right:none;border-top:none;border-bottom:none">
                    '.$_REQUEST['proveedor'].'
                </td>
                <td style="width:80px;padding:2px;border:1px solid #000;border-left:none;border-right:none;border-top:none;border-bottom:none">
                    <strong>Fecha Ingreso:</strong>
                </td>
                <td style="width:240px;padding:2px;border:1px solid #000;border-left:none;border-top:none;border-bottom:none">
                    '.fecha($_REQUEST['fechas']).'
                </td>
            </tr>
            <tr style="font-size:10px">
                <td style="width:80px;padding:2px;border:1px solid #000;border-right:none;border-bottom:none">
                    <strong>Direccion:</strong>
                </td>
                <td style="width:318px;padding:2px;border:1px solid #000;border-left:none;border-right:none;border-top:none;border-bottom:none">
                    '.$tercero['Direccion'].'
                </td>
                <td style="width:80px;padding:2px;border:1px solid #000;border-left:none;border-right:none;border-top:none;border-bottom:none">
                    <strong>Tipo Proveedor:</strong>
                </td>
                <td style="width:240px;padding:2px;border:1px solid #000;border-left:none;border-top:none;border-bottom:none">
                    
                </td>
            </tr>
            <tr style="font-size:10px">
                <td style="width:80px;padding:2px;border:1px solid #000;border-right:none">
                    <strong>Telefono:</strong>
                </td>
                <td style="width:318px;padding:2px;border:1px solid #000;border-left:none;border-right:none;border-top:none">
                    '.$tercero['Telefono'].'
                </td>
                <td style="width:80px;padding:2px;border:1px solid #000;border-left:none;border-right:none;border-top:none">
                </td>
                <td style="width:240px;padding:2px;border:1px solid #000;border-left:none;border-top:none">
                </td>
            </tr>
        </table>
    ';

    $contenido .= '
        <table border="1" style="border-collapse:collapse;margin-top:20px">
            <tr style="font-size:10px">
                <th style="width:60px;background:#ccc;text-align:center;padding:2px">Cta</th>
                <th style="width:90px;background:#ccc;text-align:center;padding:2px">Nombre Cta</th>
                <th style="width:80px;background:#ccc;text-align:center;padding:2px">Numero Factura</th>
                <th style="width:60px;background:#ccc;text-align:center;padding:2px">Fecha</th>
                <th style="width:60px;background:#ccc;text-align:center;padding:2px">Fecha Venc</th>
                <th style="width:35px;background:#ccc;text-align:center;padding:2px">Plazo</th>
                <th style="width:35px;background:#ccc;text-align:center;padding:2px">Mora</th>
                <th style="width:80px;background:#ccc;text-align:center;padding:2px">Valor Facturado</th>
                <th style="width:70px;background:#ccc;text-align:center;padding:2px">Abono</th>
                <th style="width:70px;background:#ccc;text-align:center;padding:2px">Saldo</th>
            </tr>';

    foreach ($movimientos as $i => $value) {
        $contenido .= '<tr style="font-size:9px">
                <td style="width:60px;text-align:center;padding:2px">'.$value['Codigo'].'</td>
                <td style="width:90px;text-align:center;padding:2px">'.$value['Nombre_Cta'].'</td>
                <td style="width:80px;text-align:center;padding:2px">'.$value['Factura'].'</td>
                <td style="width:60px;text-align:center;padding:2px">'.fecha($value['Fecha']).'</td>
                <td style="width:60px;text-align:center;padding:2px">'.fecha($value['Fecha_Vencimiento']).'</td>
                <td style="width:35px;text-align:center;padding:2px">'.$value['Plazo'].'</td>
                <td style="width:35px;text-align:center;padding:2px">'.$value['Mora'].'</td>
                <td style="width:80px;text-align:right;padding:2px">$ '.number_format($value['Valor_Factura'],2,",",".").'</td>
                <td style="width:70px;text-align:right;padding:2px">$ '.number_format($value['Valor_Abono'],2,",",".").'</td>
                <td style="width:70px;text-align:right;padding:2px">$ '.number_format($value['Valor_Saldo'],2,",",".").'</td>
            </tr>';

        $totales['valor_facturado'] += $value['Valor_Factura'];
        $totales['valor_abono'] += $value['Valor_Abono'];
        $totales['valor_saldo'] += $value['Valor_Saldo'];
    }
    
     $contenido  .= '</table>';

    $contenido .= '
        <table border="1" style="border-collapse:collapse;margin-top:20px">
            <tr style="font-size:10px">
                <th style="width:147px;padding:2px;height:20px;vertical-align:middle;border-left:none;border-top:none;border-bottom:none"></th>
                <th style="width:120px;padding:2px;height:20px;vertical-align:middle;background:#ccc">TOTAL ESTADO CTA</th>
                <th style="width:250px;padding:2px;height:20px;vertical-align:middle;text-align:right">'.number_format($totales['valor_facturado'],2,",",".").'</th>
                <th style="width:90px;padding:2px;height:20px;vertical-align:middle;text-align:right">'.number_format($totales['valor_abono'],2,",",".").'</th>
                <th style="width:90px;padding:2px;height:20px;vertical-align:middle;text-align:right">'.number_format($totales['valor_saldo'],2,",",".").'</th>
            </tr>
        </table>
    ';



/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:410px;font-weight:thin;font-size:14px;line-height:20px;">
                    '.$config["Nombre_Empresa"].'<br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    TEL: '.$config["Telefono"].'
                  </td>
                  <td style="width:250px;text-align:right">
                        '.$codigos.'
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
    $html2pdf = new HTML2PDF('P', 'A4', 'Es', true, 'UTF-8', array(5, 5, 5, 5));
    $html2pdf->writeHTML($content);
    $direc = $data["Codigo"].'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

function datosTercero() {
    $query = "SELECT *, IF(CONCAT_WS(' ',
    C.Primer_Nombre,
    C.Segundo_Nombre,
    C.Primer_Apellido,
    C.Segundo_Apellido) != '',
CONCAT_WS(' ',
    C.Primer_Nombre,
    C.Segundo_Nombre,
    C.Primer_Apellido,
    C.Segundo_Apellido),
C.Razon_Social) AS Nombre_Proveedor, (SELECT Nombre FROM Municipio WHERE Id_Municipio = C.Id_Municipio) AS Municipio FROM Proveedor C WHERE Id_Proveedor = $_REQUEST[proveedor]";

        $oCon= new consulta();
        $oCon->setQuery($query);
        $tercero= $oCon->getData();
        unset($oCon);

        return $tercero;
}

function movimientos() {
    $query = "SELECT
        PC.Codigo,
        PC.Nombre AS Nombre_Cta, 
        DATE(MC.Fecha_Movimiento) AS Fecha,
        MC.Documento AS Factura,
        (CASE PC.Naturaleza
        WHEN 'C' THEN (SUM(MC.Haber))
        ELSE (SUM(MC.Debe))
        END) AS Valor_Factura,
        (CASE PC.Naturaleza
            WHEN 'C' THEN (SUM(MC.Debe))
            ELSE (SUM(MC.Haber))
        END) AS Valor_Abono,
        (CASE PC.Naturaleza
            WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
            ELSE (SUM(MC.Debe) - SUM(MC.Haber))
        END) AS Valor_Saldo,
        PC.Naturaleza AS Nat,
        MC.Nit,
        IF(CONCAT_WS(' ',
                    C.Primer_Nombre,
                    C.Segundo_Nombre,
                    C.Primer_Apellido,
                    C.Segundo_Apellido) != '',
            CONCAT_WS(' ',
                    C.Primer_Nombre,
                    C.Segundo_Nombre,
                    C.Primer_Apellido,
                    C.Segundo_Apellido),
            C.Razon_Social) AS Nombre_Proveedor,
        DATE_ADD(DATE(MC.Fecha_Movimiento), INTERVAL IF(C.Condicion_Pago IN (0,1),0,C.Condicion_Pago) DAY) AS Fecha_Vencimiento,

        IF(C.Condicion_Pago IN (0,1),0,C.Condicion_Pago) AS Plazo,

        IFNULL(IF(C.Condicion_Pago > 1,
				IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago,
					DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago,
					0),
				0),
			0) AS Mora
            
        FROM
        Movimiento_Contable MC
            INNER JOIN
        Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
            INNER JOIN
        Proveedor C ON C.Id_Proveedor = MC.Nit
        WHERE
        MC.Estado != 'Anulado'
        AND (PC.Codigo LIKE '2335%')
            AND DATE(MC.Fecha_Movimiento) <= '$_REQUEST[Fechas]'
            AND MC.Nit = $_REQUEST[proveedor]
        GROUP BY MC.Id_Plan_Cuenta , MC.Documento
        HAVING Valor_Saldo != 0
        ORDER BY MC.Fecha_Movimiento";

        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $movimientos= $oCon->getData();
        unset($oCon);

        return $movimientos;
}

function fecha($fecha) {
    return $fecha != '' ? date('d/m/Y', strtotime($fecha)) : '';
}


?>