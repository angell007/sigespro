<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/class.nomina_colilla_pago.php');



$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$quincena=( isset( $_REQUEST['quincena'] ) ? $_REQUEST['quincena'] : '' );
$nom=( isset( $_REQUEST['nom'] ) ? $_REQUEST['nom'] : '' );

$nomi = ( isset( $_REQUEST['nomi'] ) ? $_REQUEST['nomi'] : '' );
$fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : '' );
$ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] :  '' );



//detalles desglosados

$d= explode("-",$fini);

$mes_actual = date('m',strtotime($fini));
$anio_actual = date('Y',strtotime($fini));
$dia_actual = date('d',strtotime($fini));

$mes_fin = date('m',strtotime($ffin));
$anio_fin = date('Y',strtotime($ffin));
$dia_fin = date('d',strtotime($ffin));

if ($nomi == 'Mensual') {
    $quincena .= "AND ME.Quincena LIKE '".$anio_actual."-$mes_actual%' ";
    $mensualidad = "'$anio_actual-$mes_actual%'";
}else{
    if($d[2]<=15){
        $quincena="".$anio_actual."-$mes_actual;1";
     }else{ 
        $quincena="".$anio_actual."-$mes_actual;2";
     }
}


$funcionario=new CalculoNomina($id,$quincena,$fini,$ffin,'Nomina', $nomi);
$datos_nomina=$funcionario->CalculosNomina();


/* FUNCIONES BASICAS */
function fecha($str)
{
    $parts = explode(" ",$str);
    $date = explode("-",$parts[0]);
    return $date[2] . "/". $date[1] ."/". $date[0];
}

$meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
/* FIN FUNCIONES BASICAS*/


/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

/* DATOS DEL ARCHIVO A MOSTRAR */
/*$oItem = new complex($tipo,"Id_".$tipo,$id);
$data = $oItem->getData();
unset($oItem);
/* FIN DATOS DEL ARCHIVO A MOSTRAR */

ob_start(); // Se Inicializa el gestor de PDF
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
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
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/
//clientes
//proveedores
//comprobantes
//factura_comprobante
//cuenta contable comprobante
//retenciones_comprobante
/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */

function MesString($mes_index){
    global $meses;

    return  $meses[($mes_index-1)];
}

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$quincena=( isset( $_REQUEST['quin'] ) ? $_REQUEST['quin'] : '' );

$fecha1=explode(";",$quincena);
$mes=explode("-",$fecha1[0]);
$mes1=MesString($mes[1]);
$nomina="Período de pago desde el " . substr(fecha($fini), 0, 2) . " hasta el " . substr(fecha($ffin), 0, 2) . " de " . $mes1 . " del " . $mes[0];


$tem=explode(";",$quincena);
if($tem[1]=="1"){
    $fecha=$tem[0]."-14";
}else{
    $fecha=$tem[0]."-17";
}

$query = 'SELECT NF.*,  CONCAT(F.Nombres," ",F.Apellidos) as Funcionario,  
          (SELECT C.Nombre FROM Cargo C WHERE C.Id_Cargo=F.Id_Cargo) as Cargo, CF.Fecha_Inicio_Contrato as Fecha_Ingreso, CF.Valor as Salario
 FROM Nomina_Funcionario NF  
 INNER JOIN Funcionario F ON NF.Identificacion_Funcionario=F.Identificacion_Funcionario 
 INNER JOIN Contrato_Funcionario CF ON F.Identificacion_Funcionario = CF.Identificacion_Funcionario
 WHERE NF.Identificacion_Funcionario='.$id.' AND NF.Id_Nomina_Funcionario='.$nom;

$oCon= new consulta();
$oCon->setQuery($query);
$funcionario = $oCon->getData();
unset($oCon);


$query="SELECT MN.* FROM Movimiento_Nomina_Funcionario MN WHERE MN.Id_Nomina_Funcionario= ".$nom;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$conceptos = $oCon->getData();
unset($oCon);


        
        $codigos ='
                      <h4 style="margin:5px 0 0 0;font-size:14px;line-height:14px;">Colilla de Pago</h4><br>
                      <h6 style="margin:5px 0 0 0;font-size:11px;line-height:11px;">'.$nomina.'</h6>
        ';
        $contenido = '<table style="border:1px solid #cccccc;"  cellpadding="0" cellspacing="0">
            <tr style="width:590px;" >
                            <td  style="width:100px;font-size:10px;font-weight:bold;text-align:left; background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Funcionario </td>
                            <td style="width:213px;font-size:10px;text-align:left;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$funcionario['Funcionario'].'</td>
                            <td  style="width:120px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Documento</td>
                            <td   style="width:110px;font-size:10px;text-align:left;border:1px solid #cccccc;padding:4px;padding-right:0;">C.C. '.number_format($funcionario['Identificacion_Funcionario'],0,",",".").'</td>
                            <td   style="width:150px;font-size:10px;font-weight:bold;text-align:center;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:2;">Colilla Número</td>
            
            </tr>
            <tr style="width:590px; " >
                            <td  style="width:100px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Cargo</td>
                            <td   style="width:213px;font-size:10px;text-align:left;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$funcionario['Cargo'].'</td>
                            <td  style="vertical-align:middle; width:120px;font-size:10px;font-weight:bold;text-align:left;border:1px solid #cccccc;background:#ededed;padding:4px;padding-right:0;">Dias Laborados</td>
                            <td  style="width:120px;font-size:10px;text-left:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$funcionario['Dias_Laborados'].' dias </td>
                            <td  style="vertical-align:middle; width:150px;font-size:10px;font-weight:bold;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:2;">
                         '.$nom.'</td>
            </tr>
            <tr style="width:590px; " >
                            <td  style="width:100px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Salario</td>
                            <td   style="width:213px;font-size:10px;text-align:left;border:1px solid #cccccc;padding:4px;padding-right:0;">$ '.number_format($funcionario['Salario'],0,",",".").'</td>
                            <td  style="vertical-align:middle; width:120px;font-size:10px;font-weight:bold;text-align:left;border:1px solid #cccccc;background:#ededed;padding:4px;padding-right:0;">Fecha de Ingreso</td>
                            <td colspan="2"  style="width:120px;font-size:10px;text-left:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.fecha($funcionario['Fecha_Ingreso']).'</td>
            
            </tr>    
           
         
         
        </table>
        
   
       <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
<tr>
    <td colspan="2" style="text-align:center;width:345px;font-weight:bold;border:1px solid #cccccc;background:#ededed;padding:4px;">Devengado</td>
    <td colspan="2" style="text-align:center;width:345px;font-weight:bold;border:1px solid #cccccc;background:#ededed;padding:4px;">Deducido</td>
</tr>
<tr>
    <td style="text-align:center;width:207px;font-weight:bold;border:1px solid #cccccc;background:#ededed;padding:4px;">Concepto</td>
    <td style="text-align:center;width:138px;font-weight:bold;border:1px solid #cccccc;background:#ededed;padding:4px;">Valor</td>
    <td style="text-align:center;width:207px;font-weight:bold;border:1px solid #cccccc;background:#ededed;padding:4px;">Concepto</td>
    <td style="text-align:center;width:138px;font-weight:bold;border:1px solid #cccccc;background:#ededed;padding:4px;">Valor</td>
</tr>';

// Devengado
$devengado = [
    ['Concepto' => 'Salario', 'Valor' => $datos_nomina['Salario_Quincena']],
    ['Concepto' => 'Auxilio de Transporte', 'Valor' => $datos_nomina['Auxilio']],
    ['Concepto' => 'Bonificaciones', 'Valor' => array_sum(array_column($datos_nomina['Bono_Funcionario'], 'Valor'))],
];



// Deducciones (Retenciones)
$deducido = [
    ['Concepto' => 'Salud', 'Valor' => $datos_nomina['Total_Salud']],
    ['Concepto' => 'Pensión', 'Valor' => $datos_nomina['Total_Pension']],
    ['Concepto' => 'Fondo Solidario', 'Valor' => $datos_nomina['Total_Solidaridad']],
    ['Concepto' => 'Retención en la Fuente', 'Valor' => $datos_nomina['Total_Renta']],
    ['Concepto' => 'Póliza Funeraria', 'Valor' => $datos_nomina['Lista_Egresos'][5]['Valor']], 
];

// Calcular el número máximo de filas
$max_filas = max(count($devengado), count($deducido));

// Imprimir la tabla con los conceptos
for ($i = 0; $i < $max_filas; $i++) {
    $devengado_concepto = isset($devengado[$i]) ? $devengado[$i] : null;
    $deducido_concepto = isset($deducido[$i]) ? $deducido[$i] : null;

    $contenido .= '<tr>';

    // Columna de Devengado
    if ($devengado_concepto) {
        $contenido .= '
            <td style="width:207px;max-width:207px;border:1px solid #cccccc; max-height:50px;padding:4px;">
                ' . $devengado_concepto['Concepto'] . '
            </td>
            <td style="text-align:right;width:138px;max-width:138px;border:1px solid #cccccc;padding:4px;">
                $ ' . number_format($devengado_concepto['Valor'], 2, ".", ",") . '
            </td>';
    } else {
        $contenido .= '
            <td style="width:207px;max-width:207px;border:1px solid #cccccc; max-height:50px;padding:4px;"></td>
            <td style="text-align:right;width:138px;max-width:138px;border:1px solid #cccccc;padding:4px;"></td>';
    }

    // Columna de Deducido
    if ($deducido_concepto) {
        $contenido .= '
            <td style="width:207px;max-width:207px;border:1px solid #cccccc; max-height:50px;padding:4px;">
                ' . $deducido_concepto['Concepto'] . '
            </td>
            <td style="text-align:right;width:138px;max-width:138px;border:1px solid #cccccc;padding:4px;">
                $ ' . number_format($deducido_concepto['Valor'], 2, ".", ",") . '
            </td>';
    } else {
        $contenido .= '
            <td style="width:207px;max-width:207px;border:1px solid #cccccc; max-height:50px;padding:4px;"></td>
            <td style="text-align:right;width:138px;max-width:138px;border:1px solid #cccccc;padding:4px;"></td>';
    }

    $contenido .= '</tr>';
}

// Añadir fila con los totales
$contenido .= '
<tr>
    <td style="font-weight:bold;border:1px solid #cccccc;padding:4px;text-align:right;">Total Devengado</td>
    <td style="text-align:right;border:1px solid #cccccc;padding:4px;">$ '.number_format(array_sum(array_column($devengado, 'Valor')), 2, ".", ",").'</td>
    <td style="font-weight:bold;border:1px solid #cccccc;padding:4px;text-align:right;">Total Deducido</td>
    <td style="text-align:right;border:1px solid #cccccc;padding:4px;">$ '.number_format(array_sum(array_column($deducido, 'Valor')), 2, ".", ",").'</td>
</tr>';

// Calcular y añadir el valor neto a pagar al empleado
$valor_neto = array_sum(array_column($devengado, 'Valor')) - array_sum(array_column($deducido, 'Valor'));
$contenido .= '
<tr>
    <td colspan="3" style="font-weight:bold;text-align:right;padding:4px;">Valor Neto a Pagar al Empleado</td>
    <td style="text-align:right;padding:4px;border:1px solid #cccccc;">$ '.number_format($valor_neto, 2, ".", ",").'</td>
</tr>';

$contenido .= '</table><br>

<b style="font-size:10px;">Nota: Lo expuesto en este comprobante representa el pago mensual del empleado, y en este se listan el salario neto, deducciones e ingresos adicionales y su firma representa su entera satisfacción.</b>

<table style="margin-top:20px">    
    <tr>
        <td style="width:400px;padding-left:30px">
        <table>
        <tr>
            <td style="width:330px;font-weight:bold; border-top:1px solid black; text-align:center;">'.$funcionario['Funcionario'].'</td>
            <td style="width:30px;"></td>
            <td style="width:330px;font-weight:bold; border-top:1px solid black; text-align:center;text-transform:uppercase;">'.$config["Nombre_Empresa"].'</td>
        </tr>
        <tr>
            <td style="width:330px;font-weight:bold; text-align:center;">'.$funcionario['Cargo'].' </td>    
            <td style="width:30px;"></td>    
            <td style="width:330px;font-weight:bold; text-align:center;"></td>    
        </tr>
        </table>
        </td>    
    </tr>
</table>';

/* FIN SWITCH*/

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'/assets/images/logo-color.png" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:510px;font-weight:thin;font-size:14px;line-height:20px;">
                    '.$config["Nombre_Empresa"].'<br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    TEL: '.$config["Telefono"].'
                  </td>
                  <td style="width:170px;text-align:right">
                        '.$codigos.'
                  </td>
                  
                </tr>
              </tbody>
            </table><hr style="border:1px dotted #ccc;width:730px;">';
/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

$footer = '<div style="position:absolute; bottom: -180px; left: 0px; width: 100%; text-align: left; font-size: 10px; border-top: 1px solid #cccccc; margin: 0; padding-left: 5px;">  Proveedor tecnológico: PRODUCTOS HOSPITALARIOS S.A. PRO-H S.A. - Nit 804.016.084 - 5. Nombre Software: SIGESPRO.</div>';


$content = '<page backtop="0mm" backbottom="0mm">
                <div class="page-content">' . $cabecera . $contenido . $footer . '</div>
            </page>';

/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('L', array(215.9,180), 'Es', true, 'UTF-8', array(5, 5, 2, 0));
    $html2pdf->writeHTML($content);
    $direc = $id.'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc,''); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

?>