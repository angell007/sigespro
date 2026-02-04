<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');

$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

/* FUNCIONES BASICAS */
function fecha($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	return $date[2] . "/". $date[1] ."/". $date[0];
}
/* FIN FUNCIONES BASICAS*/

/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

/* DATOS DEL ARCHIVO A MOSTRAR */
/*$oItem = new complex($tipo,"Id_".$tipo,$id);
$data = $oItem->getData();
unset($oItem);
/* FIN DATOS DEL ARCHIVO A MOSTRAR */

ob_start(); // Se Inicializa el gestor de PDF

/* HOJA DE ESTILO PARA PDF*/
$style='<style>
.page-content{
width:750px;
pading:0;
}
.row{
display:inlinie-block;
width:750px;
}
.td-header{
    font-size:10px;
    line-height: 11px;
}
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/
//clientes
//proveedores
//comprobantes
//factura_comprobante
//cuenta contable comprobante
//retenciones_comprobante
/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */

    $query_comprobante = '
        SELECT DISTINCT
            C.*,
            (CASE
                WHEN C.Id_Cliente != 0 THEN IFNULL((SELECT Nombre FROM Cliente WHERE Id_Cliente = C.Id_Cliente),(SELECT CONCAT_WS(" ",Nombres,Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = C.Id_Cliente))
                WHEN C.Id_Proveedor != 0 THEN IFNULL((SELECT Nombre FROM Proveedor WHERE Id_Proveedor = C.Id_Proveedor),(SELECT CONCAT_WS(" ",Nombres,Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = C.Id_Proveedor))
            END) AS NombreTercero,
            (CASE
                WHEN C.Id_Cliente != 0 THEN IFNULL((SELECT Direccion FROM Cliente WHERE Id_Cliente = C.Id_Cliente),(SELECT Direccion_Residencia FROM Funcionario WHERE Identificacion_Funcionario = C.Id_Cliente))
                WHEN C.Id_Proveedor != 0 THEN IFNULL((SELECT Direccion FROM Proveedor WHERE Id_Proveedor = C.Id_Proveedor),(SELECT Direccion_Residencia FROM Funcionario WHERE Identificacion_Funcionario = C.Id_Proveedor))
            END) AS DireccionTercero,
            (CASE
                WHEN C.Id_Cliente != 0 THEN IFNULL((SELECT M.Nombre FROM Cliente C2 INNER JOIN Municipio M ON C2.Ciudad = M.Id_Municipio WHERE C2.Id_Cliente = C.Id_Cliente),"")
                WHEN C.Id_Proveedor != 0 THEN IFNULL((SELECT M.Nombre FROM Proveedor P INNER JOIN Municipio M ON M.Id_Municipio = P.Ciudad WHERE P.Id_Proveedor = C.Id_Proveedor),"")
            END) AS CiudadTercero,
            (CASE
                WHEN C.Id_Cliente != 0 THEN IFNULL((SELECT Celular FROM Cliente WHERE Id_Cliente = C.Id_Cliente),(SELECT Celular FROM Funcionario WHERE Identificacion_Funcionario = C.Id_Cliente))
                WHEN C.Id_Proveedor != 0 THEN IFNULL((SELECT Telefono FROM Proveedor WHERE Id_Proveedor = C.Id_Proveedor),(SELECT Celular FROM Funcionario WHERE Identificacion_Funcionario = C.Id_Proveedor))
            END) AS TelefonoTercero,
            (CASE
                WHEN C.Id_Cliente != 0 THEN C.Id_Cliente
                WHEN C.Id_Proveedor != 0 THEN C.Id_Proveedor
            END) AS NitTercero,
            FP.Nombre AS FormaPago,
            PC.Nombre AS PlanCuenta,
            CONCAT_WS(" ", F.Nombres, F.Apellidos) AS NombreFuncionario
        FROM
            Comprobante C
        INNER JOIN Forma_Pago FP ON C.Id_Forma_Pago = FP.Id_Forma_Pago
        INNER JOIN Plan_Cuentas PC ON C.Id_Cuenta = PC.Id_Plan_Cuentas
        INNER JOIN Funcionario F ON C.Id_Funcionario = F.Identificacion_Funcionario
        WHERE
            C.Id_Comprobante = '.$id;

    $oCon= new consulta();
    $oCon->setQuery($query_comprobante);
    $comprobante = $oCon->getData();
    unset($oCon);

    $query_retenciones_comprobante = '
        SELECT 
            RC.*,
            R.Nombre AS Retencion,
            FC.Factura
        FROM
        Retencion_Comprobante RC
        INNER JOIN Comprobante C ON RC.Id_Comprobante = C.Id_Comprobante
        INNER JOIN Retencion R ON R.Id_Retencion = RC.Id_Retencion
        LEFT JOIN Factura_Comprobante FC ON FC.Id_Factura = RC.Id_Factura AND RC.Id_Comprobante = FC.Id_Comprobante
        WHERE
            RC.Id_Comprobante = '.$id;

    $oCon= new consulta();
    $oCon->setQuery($query_retenciones_comprobante);
    $oCon->setTipo('Multiple');
    $comprobante_retenciones = $oCon->getData();
    unset($oCon);

    $select_fields = $comprobante['Tipo_Movimiento'] == 'General' ? 'PC.*, CCC.Subtotal AS Subtotal' : 'FV.*, FC.Valor AS Subtotal';

    $join = $comprobante['Tipo_Movimiento'] == 'General' ? ' INNER JOIN Cuenta_Contable_Comprobante CCC ON C.Id_Comprobante = CCC.Id_Comprobante
                                                            INNER JOIN Plan_Cuentas PC ON CCC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas' 
                                                            : ' INNER JOIN Factura_Comprobante FC ON C.Id_Comprobante = FC.Id_Comprobante
                                                                INNER JOIN Factura_Venta FV ON FC.Id_Factura = FV.Id_Factura_Venta';

    $query_fac_ccontable_comprobante = '  
        SELECT 
            '.$select_fields.'
        FROM
            Comprobante C '
            .$join.'
        WHERE
            C.Id_Comprobante = '.$id;

            //var_dump($query_fac_ccontable_comprobante);

    $oCon= new consulta();
    $oCon->setQuery($query_fac_ccontable_comprobante);
    $oCon->setTipo('Multiple');
    $comprobante_fac_ccontable = $oCon->getData();
    unset($oCon);

    $subtotal =  0;
    $total_retenciones = 0;
    $total_ajuste = 0;
    $total_descuentos = 0;
    $total = 0;

    if (count($comprobante_fac_ccontable) > 0) {
    	foreach ($comprobante_fac_ccontable as $key) {
    		$subtotal += $key['Subtotal'];
    	}
    }

    
if ($comprobante['Tipo_Movimiento'] == 'Factura') {
    $query = "SELECT * FROM Factura_Comprobante WHERE Id_Comprobante = $comprobante[Id_Comprobante]";
} else {
    $query = "SELECT CCC.*, CONCAT(PC.Codigo, ' - ', PC.Nombre) AS Cuenta_Contable FROM Cuenta_Contable_Comprobante CCC INNER JOIN Plan_Cuentas PC ON CCC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas WHERE CCC.Id_Comprobante = $comprobante[Id_Comprobante]";
}

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$conceptos = $oCon->getData();
unset($oCon);

$contenido_conceptos = '';

foreach ($conceptos as $value) {
    if ($comprobante['Tipo_Movimiento'] == 'Factura') {
        $contenido_conceptos .= "
            <tr>
            <td style='width:580px;max-width:400px;border:1px solid #cccccc'>Pago de la Factura $value[Factura]</td>
            <td style='text-align:right;width:150px;max-width:100px;border:1px solid #cccccc'>$.".number_format($value['Valor'],2,",",".")."</td>
            </tr>
        ";

        if ($value['ValorDescuento'] > 0) { // Para reflejar los descuentos
            $descuentos = getDescuentosFactura($value['Id_Factura'],$value['Id_Comprobante']);

            foreach ($descuentos as $desc) {
                $total_descuentos += $desc['Valor'];
                $contenido_conceptos .= "
                <tr>
                <td style='width:580px;max-width:400px;border:1px solid #cccccc'>Descuento de la Factura $value[Factura]</td>
                <td style='text-align:right;width:150px;max-width:100px;border:1px solid #cccccc'>$.".number_format($desc['Valor'],2,",",".")."</td>
                </tr>
            ";
            }
        }

        if ($value['ValorMayorPagar'] > 0) { // Para reflejar los ajustes
            $contenido_conceptos .= "
                <tr>
                <td style='width:580px;max-width:400px;border:1px solid #cccccc'>Ajuste de la Factura $value[Factura]</td>
                <td style='text-align:right;width:150px;max-width:100px;border:1px solid #cccccc'>$.".number_format($value['ValorMayorPagar'],2,",",".")."</td>
                </tr>";

                $total_ajuste = $value['ValorMayorPagar'];
        }
    } else {
        $contenido_conceptos .= "
            <tr>
            <td style='width:580px;max-width:400px;border:1px solid #cccccc'>$value[Cuenta_Contable] - $value[Observaciones]</td>
            <td style='text-align:right;width:150px;max-width:100px;border:1px solid #cccccc'>$.".number_format($value['Valor'],2,",",".")."</td>
            </tr>
        ";
    }
}
      
        
 $nit=$comprobante['Id_Cliente']=='0' ? $comprobante['Id_Proveedor'] : $comprobante['Id_Cliente'];
//$monto=number_format($data['Monto'],2,".",",");

 $retenciones = 'No se aplicaron retenciones';
 $ind = 0;

 foreach ($comprobante_retenciones as $key) {
     $factura = $key['Factura'] != '' ? 'FRA. ' . $key['Factura'] . ' - ' : '';
 	$total_retenciones += $key['Valor'];
 	if ($ind == 0) {
 		$retenciones = $factura . $key['Retencion'].' = $ '.number_format($key['Valor'],2,",",".").'<br>';
 	}else{
 		$retenciones .= $factura . $key['Retencion'].' = $ '.number_format($key['Valor'],2,",",".").'<br>';	
 	}
 	
 	$ind++;
 }

 $total = $subtotal - $total_retenciones - $total_descuentos + $total_ajuste;

 $totales_ajustes = '';
 $totales_descuentos = '';
 $totales_retenciones = '';

 if ($total_ajuste > 0) {
     
    $totales_ajustes = '
    <tr>
        <td style="text-align:center;width:375px;max-width:370px;font-weight:bold;">  
        </td>
        <td style="text-align:center;width:200px;max-width:400px;font-weight:bold;border:1px solid #cccccc;"> Total Ajustes
        </td>
        <td style="text-align:right;width:150px;max-width:100px;border:1px solid #cccccc;">$'.number_format($total_ajuste, 2, ".", ",").'</td>
    </tr>
    ';
 }

 if ($total_descuentos > 0) {
     $totales_descuentos = '
     <tr>
        <td style="text-align:center;width:375px;max-width:370px;font-weight:bold;">  
        </td>
        <td style="text-align:center;width:200px;max-width:400px;font-weight:bold;border:1px solid #cccccc;">Total Descuentos</td>
        <td style="text-align:right;width:150px;max-width:100px;border:1px solid #cccccc;">$'.number_format($total_descuentos, 2, ".", ",").'</td>
    </tr>
     ';
 }

 if ($total_retenciones > 0) {
     $totales_retenciones = '
     <tr>
        <td style="text-align:center;width:375px;max-width:370px;font-weight:bold;">  
        </td>
        <td style="text-align:center;width:200px;max-width:400px;font-weight:bold;border:1px solid #cccccc;">Total Retenciones</td>
        <td style="text-align:right;width:150px;max-width:100px;border:1px solid #cccccc;">$'.number_format($total_retenciones, 2, ".", ",").'</td>
    </tr>
     
     ';
 }


 $nro_cheque = $comprobante['Cheque'] != '' ? " - " . $comprobante['Cheque'] : '';
     
        
        $codigos ='
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$comprobante["Codigo"].'</h3>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.$comprobante["Fecha_Comprobante"].'</h5>
            <h4 style="margin:5px 0 0 0;font-size:14px;line-height:14px;">Comprobante de '.$comprobante["Tipo"].'</h4>
        ';
        $contenido = '<table style="border:1px solid #cccccc;"  cellpadding="0" cellspacing="0">
            <tr style="width:590px;" >
                            <td  style="width:100px;font-size:10px;font-weight:bold;text-align:left; background:#ededed;border:1px solid #cccccc;">Tercero </td>
                            <td colspan="3" style="width:213px;font-size:10px;font-weight:bold;text-align:left;border:1px solid #cccccc;">'.$comprobante['NombreTercero'].'</td>
                            <td   style="width:150px;font-size:10px;font-weight:bold;text-align:center;background:#ededed;border:1px solid #cccccc;">Fecha</td>
            
            </tr>
            <tr style="width:710px; " >
                            <td  style="width:100px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;">Direccion</td>
                            <td  colspan="3" style="width:213px;font-size:10px;text-align:left;border:1px solid #cccccc;">'.$comprobante['DireccionTercero'].'</td>
                            <td  rowspan="4" style="vertical-align:middle; width:150px;font-size:10px;font-weight:bold;text-align:center;border:1px solid #cccccc;">'.fecha($comprobante['Fecha_Comprobante']).'</td>
            
            </tr>
            <tr style="width:590px; " >
                            <td  style="width:100px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;">Ciudad</td>
                            <td colspan="3" style="width:200px;font-size:10px;text-align:left;border:1px solid #cccccc;">'.$comprobante['CiudadTercero'].'</td>
            
            </tr>
            <tr style="width:590px;  " >
                            <td  style="width:100px;font-size:10px;font-weight:bold;text-align:left; background:#ededed;border:1px solid #cccccc;">Telefono</td>
                            <td  style="width:213px;font-size:10px;text-align:left;border:1px solid #cccccc;">'.$comprobante['TelefonoTercero'].'</td>
                            <td  style="vertical-align:middle; width:120px;font-size:10px;font-weight:bold;text-align:left;border:1px solid #cccccc;background:#ededed;">Metodo de Pago</td>
                            <td  style="vertical-align:middle; width:120px;font-size:10px;text-align:left;border:1px solid #cccccc;">'.$comprobante['FormaPago'].$nro_cheque.'</td>
            
            </tr>
            <tr style="width:590px;  " >
                            <td  style="width:100px;font-size:10px;font-weight:bold;text-align:left; background:#ededed;border:1px solid #cccccc;">NIT</td>
                            <td  style="width:100px;font-size:10px;text-align:left;border:1px solid #cccccc;">'.$comprobante['NitTercero'].'</td>
                            <td  style="vertical-align:middle; width:120px;font-size:10px;font-weight:bold;text-align:left;border:1px solid #cccccc;background:#ededed;">Cuenta</td>
                            <td  style="vertical-align:middle; width:120px;font-size:10px;text-align:left;border:1px solid #cccccc;">'.$comprobante['PlanCuenta'].'</td>
            
            </tr>
            <tr>
            <td  style="vertical-align:middle;width:100px;font-size:10px;font-weight:bold;text-align:left; background:#ededed;border:1px solid #cccccc;">Observaciones</td>
          
            <td  colspan="4" style="vertical-align:middle;width:213px;font-size:10px;text-align:left;border:1px solid #cccccc;">
            '.$comprobante["Observaciones"].'
            </td>

            
            </tr>
        </table>
   
        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr>
		
                <td style="text-align:center;width:580px;max-width:400px;font-weight:bold;border:1px solid #cccccc;background:#ededed;">
                    Concepto
                </td>
                <td style="text-align:center;width:150px;max-width:100px;font-weight:bold;border:1px solid #cccccc;background:#ededed;">
                    Valor
                </td>
                
            </tr>
            '.$contenido_conceptos.'</table>
            
            <table style="margin-top:10px;font-size:10px;" cellpadding="0" cellspacing="0">
            <tr>
                <td style="text-align:center;width:737px;max-width:400px;font-weight:bold;border:1px solid #cccccc;background:#ededed;"> Retenciones Aplicadas
                </td>
               
            </tr>
            <tr>
            <td style="text-align:left;width:737px;max-width:200px;border:1px solid #cccccc;"> '.$retenciones.' 
            </td>
            </tr>
            </table>
            
            <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr>
                <td style="text-align:center;width:375px;max-width:370px;font-weight:bold;">  
                </td>
                <td style="text-align:center;width:200px;max-width:400px;font-weight:bold;border:1px solid #cccccc;"> Subtotal
                </td>
                <td style="text-align:right;width:150px;max-width:100px;border:1px solid #cccccc;">$'.number_format($subtotal, 2, ".", ",").'</td>
            </tr>
            '.$totales_retenciones.'
            '.$totales_descuentos.'
            '.$totales_ajustes.'
            <tr>
                <td style="text-align:center;width:375px;max-width:370px;font-weight:bold;">  
                </td>
                <td style="background:#ededed;text-align:center;width:200px;max-width:400px;font-weight:bold;border:1px solid #cccccc;background:#ededed;"> Total
                </td>
                <td style="background:#ededed;text-align:right;width:150px;max-width:100px;border:1px solid #cccccc;">$'.number_format($total, 2, ".", ",").'</td>
            </tr>
            </table>';

    $query = "SELECT CC.*, GROUP_CONCAT(DISTINCT FC.Factura SEPARATOR ' | ') AS Documento FROM Contabilidad_Comprobante CC INNER JOIN Factura_Comprobante FC ON CC.Id_Factura_Comprobante = FC.Id_Factura_Comprobante WHERE CC.Id_Comprobante = $id AND (CC.Debito != 0 OR CC.Credito != 0) GROUP BY CC.Id_Comprobante, CC.Id_Plan_Cuentas";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $contabilidad = $oCon->getData();
    unset($oCon);

    /* $contenido2 = '
    <h3 style="text-align:center">Contabilidad</h3>
    <table style="font-size:10px;" cellpadding="0" cellspacing="0">
    <tr>

        <td style="text-align:center;width:80px;max-width:400px;font-weight:bold;border:1px solid #cccccc;background:#ededed;">
            Codigo
        </td>
        <td style="text-align:center;width:245px;max-width:400px;font-weight:bold;border:1px solid #cccccc;background:#ededed;">
            Cuenta
        </td>
        <td style="text-align:center;width:150px;max-width:400px;font-weight:bold;border:1px solid #cccccc;background:#ededed;">
            Documento
        </td>
        <td style="text-align:center;width:120px;max-width:400px;font-weight:bold;border:1px solid #cccccc;background:#ededed;">
            Débito
        </td>
        <td style="text-align:center;width:120px;max-width:100px;font-weight:bold;border:1px solid #cccccc;background:#ededed;">
            Crédito
        </td>
        
    </tr>';

    $totalDeb = 0;
    $totalCred = 0;

    foreach ($contabilidad as $value) {

        $debito = $value['Debito'] == 0 ? '' : '$.'.number_format($value['Debito'],2,",",".");
        $credito = $value['Credito'] == 0 ? '' :'$.'. number_format($value['Credito'],2,",",".");
        
        $contenido2 .= '<tr>

        <td style="text-align:center;border:1px solid #cccccc">
            '.$value['Codigo_Cuenta'].'
        </td>
        <td style="text-align:center;border:1px solid #cccccc">
            '.$value['Nombre_Cuenta'].'
        </td>
        <td style="text-align:center;border:1px solid #cccccc">
            '.$value['Documento'].'
        </td>
        <td style="text-align:center;border:1px solid #cccccc">
            '.$debito.'
        </td>
        <td style="text-align:center;border:1px solid #cccccc">
            '.$credito.'
        </td>
        
    </tr>';

    $totalDeb += $value['Debito'];
    $totalCred += $value['Credito'];

    }
    
    $contenido2 .= '
    <tr>
        <td colspan="3" style="text-align:center;border:1px solid #cccccc;font-weight:bold">SUMAS IGUALES</td>
        <td style="text-align:center;border:1px solid #cccccc;font-weight:bold">$.'.number_format($totalDeb,2,",",".").'</td>
        <td style="text-align:center;border:1px solid #cccccc;font-weight:bold">$.'.number_format($totalCred,2,",",".").'</td>
    </tr>
    </table>';
 */
            

	
	$contenido .='<br><table style="margin-top:10px;font-size:10px;" cellpadding="0" cellspacing="0">
	<tr>
  <td style="background:#ededed;text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
  Elaboró
</td>
	<td style="background:#ededed;text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
	Revisó
	</td> 
	<td style="background:#ededed;text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
	Aprobó
	</td> 
	<td style="background:#ededed;text-align:center;width:173px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
	Beneficiario
	</td> 
  </tr>
  <tr>
  <td style="width:180px;max-width:180px;border:1px solid #cccccc;">
  '.$comprobante['NombreFuncionario'].'
</td>
  <td style="width:180px;max-width:180px;border:1px solid #cccccc;">
  
</td>
  <td style="width:180px;max-width:180px;border:1px solid #cccccc;">
  
</td>
  <td style="width:173px;max-width:170px;border:1px solid #cccccc;">
  
</td>
  </tr>

	</table>';
	
 
/* FIN SWITCH*/

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'/IMAGENES/LOGOS/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:370px;font-weight:thin;font-size:14px;line-height:20px;">
                    '.$config["Nombre_Empresa"].'<br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    TEL: '.$config["Telefono"].'
                  </td>
                  <td style="width:200px;text-align:right">
                        '.$codigos.'
                  </td>
                  <td style="width:200px;">
                  <img src="'.($comprobante["Codigo_Qr"] =='' || !file_exists($_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$comprobante["Codigo_Qr"]) ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$comprobante["Codigo_Qr"] ).'" style="width:100px;margin-top:-10px;" />
                  </td>
                </tr>
              </tbody>
            </table><hr style="border:1px dotted #ccc;width:730px;">';
/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/

$marca_agua = '';

if ($comprobante['Estado'] == 'Anulada') {
    $marca_agua = 'backimg="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/anulada.png"';
}

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm" '.$marca_agua.'>
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
    $html2pdf->Output($direc,''); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

function getDescuentosFactura($id_factura, $id_comprobante){
    $query = "SELECT * FROM Descuento_Comprobante WHERE Id_Factura = $id_factura AND Id_Comprobante = $id_comprobante";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $descuentos = $oCon->getData();

    return $descuentos;
}

?>