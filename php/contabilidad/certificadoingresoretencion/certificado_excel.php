<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('content-type: application/json');
require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../class/html2pdf.class.php');
include_once('../../../class/NumeroALetra.php');

ini_set("memory_limit", "320000M");
ini_set('max_execution_time', 0);

$fecha_inicial = $_REQUEST['Fecha_Inicial'];
$fecha_final = $_REQUEST['Fecha_Final'];
$nit = $_REQUEST['Nit'];
$fecha_expedicion = $_REQUEST['Fecha_Expedicion'];

/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

$tercero = [];
$nits = getNitsFuncionarios();

echo '<table border="1" style="border-collapse: collapse;">
<thead>
<th>Identificacion</th>
<th>Nombre</th>
<th>Primer Nombre</th>
<th>Segundo Nombre</th>
<th>Primer Apellido</th>
<th>Segundo Apellido</th>
<th>Direccion</th>
<th>Departamento</th>
<th>Municipio</th>

';
for ($i=36; $i< 55 ; $i++) { 
    echo "<th>Renglon_$i</th>";
}

echo "</thead>
<tbody>";
// echo json_encode($nits)
foreach ($nits as $nit ) {   
    $nit = $nit['Nit'];
    $tercero[] = getDatosTercero($nit);
}



// echo json_encode($tercero);
exit;
$item49 = 0;
$item50 = 0;

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
  table {border-collapse: collapse;}
  table td {padding: 0px}
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/

/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */

$contenido = '
<table style="border: 1px solid #018A38;border-collapse: collapse;">
    <tbody>
    <tr>
        <td style="border-bottom: 1px solid #018a38"></td>
        <td style="border-bottom: 1px solid #018a38"></td>
        <td style="border-bottom: 1px solid #018a38"></td>
        <td style="border-bottom: 1px solid #018a38"></td>
        <td style="border-bottom: 1px solid #018a38"></td>
        <td style="border-bottom: 1px solid #018a38"></td>
        <td style="border-bottom: 1px solid #018a38"></td>
    </tr>
    <tr>
        <td style="padding: 0px;text-align:center;width:100px;border: 1px solid #018A38">
        <img src="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/logo_dian.png" style="width:150px;height:45px" alt="Pro-H Software" />
        </td>
        <td colspan="4" style="padding: 0px;font-weight:bold;width:420px;text-align:center;border: 1px solid #018A38;font-size:13px">
            Certificado de Ingresos y Retenciones
            por Rentas de Trabajo y de Pensiones
            Año gravable '.dateFormatter('anio',$fecha_inicial).'
        </td>
        <td style="padding-top:5px;vertical-align:center;padding: 0px;font-weight:bold;width:160px;text-align:center;border: 1px solid #018A38;background:#018A38;color:white">
            <span style="font-size:50px">220</span>
        </td>
    </tr>
    <tr>
        <td colspan="4" style="vertical-align:middle;padding: 1px;text-align:center;width:130px;border: 1px solid #018A38;border-bottom:none;font-size:9px">
        Antes de diligenciar este formulario lea cuidadosamente las instrucciones
        </td>
        <td colspan="3" style="padding: 0px;width:130px;text-align:left;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
            <span style="margin-left:10px;margin-top:5px">4. Número Formulario</span>
        </td>
    </tr>
    
    </tbody>
</table>
';

$contenido .= '
<table style="margin-top: 0;border: 1px solid #018A38;border-collapse: collapse;">
    <tr>
        <td rowspan="2" style="padding: 0px;text-align:center;height:10px;width:10px;max-width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
            <div style="width:70px;padding:0px;margin:0px;font-weight:bold;rotate:90;">Retenedor</div>
        </td>
        <td style="padding: 0px;height:10px;width:200px;text-align:left;border: 1px solid #018A38;font-size:9px;">
            <span style="margin-left:10px;margin-top:5px">5. Número de Identificación Tributaria (NIT)</span>

            <p style="margin-left:10px;margin-top:10px">804.016.084</p>
        </td>
        <td style="padding: 0px;height:10px;width:35px;text-align:left;border: 1px solid #018A38;font-size:9px;">
            <span style="margin-left:10px;margin-top:5px">6. DV.</span>
            <p style="margin-left:10px;margin-top:10px">5</p>
        </td>
        <td style="padding: 0px;height:10px;width:110px;text-align:left;border: 1px solid #018A38;font-size:9px;">
            <span style="margin-left:10px;margin-top:5px">7. Primer apellido</span>
        </td>
        <td style="padding: 0px;height:10px;width:110px;text-align:left;border: 1px solid #018A38;font-size:9px;">
            <span style="margin-left:10px;margin-top:5px">8. Segundo apellido</span>
        </td>
        <td style="padding: 0px;height:10px;width:110px;text-align:left;border: 1px solid #018A38;font-size:9px;">
            <span style="margin-left:10px;margin-top:5px">9. Primer nombre</span>
        </td>
        <td style="padding: 0px;height:10px;width:112px;text-align:left;border: 1px solid #018A38;font-size:9px;">
            <span style="margin-left:10px;margin-top:5px">10. Otros nombres</span>
        </td>
    </tr>
    <tr>
        <td colspan="6" style="padding: 0px;height:10px;width:200px;text-align:left;border: 1px solid #018A38;border-left:none;border-bottom:none;font-size:9px;">
            <span style="margin-left:10px;margin-top:5px">11. Razón Social</span>
            
            <p style="margin-left:10px;margin-top:10px">PRODUCTOS HOSPITALARIOS S.A. PRO-H S.A.</p>
        </td>
    </tr>
</table>
';

$contenido .= '
<table style="margin-top: 0;border: 1px solid #018A38;border-collapse: collapse;">
<tr>
        <td style="padding: 0px;text-align:center;height:10px;width:40px;max-width:40px;border: 1px solid #018A38;border-bottom:none;font-size:8px;">
            <div style="width:40px;padding:0px;margin:0px;font-weight:bold;rotate:90;">Trabajador</div>
        </td>
        <td style="padding: 0px;height:10px;width:60px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
            <span style="margin-left:10px;margin-top:5px;text-align:center;">24. Tipo de documento</span>

            <p style="margin-left:10px;margin-top:10px">13</p>
        </td>
        <td style="padding: 0px;height:10px;width:200px;text-align:left;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
            <span style="margin-left:10px;margin-top:5px">25. Número de Identificación</span>

            <p style="margin-left:10px;margin-top:15px">'.$nit.'</p>
        </td>
        <td style="padding: 0px;height:10px;width:429px;text-align:left;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
            <span style="margin-left:10px;margin-top:5px">Apellidos y nombres</span>

            <table style="margin-left:5px;margin-top:20px">
                <tr>
                    <td style="padding: 0px;text-align:left;width:100px;max-width:100px;border-right: 1px solid #018A38;color:#31a05e;font-size:8px;">
                        <p style="margin:0px;color:black">'.strtoupper($tercero['Primer_Apellido']).'</p>
                     26. Primer apellido
                    </td>
                    <td style="padding: 0px;text-align:left;width:100px;max-width:100px;border-right: 1px solid #018A38;color:#31a05e;font-size:8px;">
                        <p style="margin:0px;color:black">'.strtoupper($tercero['Segundo_Apellido']).'</p>
                     27. Segundo apellido
                    </td>
                    <td style="padding: 0px;text-align:left;width:100px;max-width:100px;border-right: 1px solid #018A38;color:#31a05e;font-size:8px;">
                        <p style="margin:0px;color:black">'.strtoupper($tercero['Primer_Nombre']).'</p>
                     28. Primer nombre
                    </td>
                    <td style="padding: 0px;text-align:left;width:100px;max-width:100px;font-size:8px;color:#31a05e;">
                        <p style="margin:0px;color:black">'.strtoupper($tercero['Segundo_Nombre']).'</p>
                     29. Otros nombres
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
';

$contenido .= '
    <table style="margin-top: 0;border: 1px solid #018A38;border-collapse: collapse;">
        <tr>
            <td style="padding: 0px;text-align:center;height:10px;width:320px;border: 1px solid #018A38;border-bottom:none;font-size:8px;">
                <span style="margin-top:2px">Período de la Certificación</span>

                <table style="margin-top:15px">
                <tr>
                    <td style="vertical-align:middle;padding: 0px;text-align:left;width:30px;max-width:30px;font-weight:bold;font-size:8px;">
                        30. DE:
                    </td>
                    <td style="padding: 3px;text-align:center;width:27px;max-width:27px;border: 1px solid #018A38;color:#31a05e;font-size:8px;">
                      '.dateFormatter('anio',$fecha_inicial).'
                    </td>
                    <td style="padding: 3px;text-align:center;width:20px;max-width:20px;border: 1px solid #018A38;color:#31a05e;font-size:8px;">
                        '.dateFormatter('mes',$fecha_inicial).'
                    </td>
                    <td style="padding: 3px;text-align:center;width:24px;max-width:24px;border: 1px solid #018A38;color:#31a05e;font-size:8px;">
                        '.dateFormatter('dia',$fecha_inicial).'
                    </td>
                    
                    <td style="vertical-align:middle;padding: 0px;text-align:center;width:30px;max-width:30px;font-weight:bold;font-size:8px;">
                        31. A:
                    </td>
                    <td style="padding: 3px;text-align:center;width:27px;max-width:27px;border: 1px solid #018A38;color:#31a05e;font-size:8px;">
                    '.dateFormatter('anio',$fecha_final).'
                    </td>
                    <td style="padding: 3px;text-align:center;width:20px;max-width:20px;border: 1px solid #018A38;color:#31a05e;font-size:8px;">
                    '.dateFormatter('mes',$fecha_final).'
                    </td>
                    <td style="padding: 3px;text-align:center;width:24px;max-width:24px;border: 1px solid #018A38;color:#31a05e;font-size:8px;">
                    '.dateFormatter('dia',$fecha_final).'
                    </td>
                </tr>
            </table>
            </td>
            <td style="padding: 0px;text-align:center;height:10px;width:110px;border: 1px solid #018A38;border-bottom:none;font-size:8px;">
                <span style="margin-top:2px">32. Fecha de expedición</span>

                <table style="margin-top:15px">
                    <tr>
                        <td style="padding: 3px;text-align:center;width:23px;max-width:23px;border: 1px solid #018A38;color:#31a05e;font-size:8px;">
                        '.dateFormatter('anio',$fecha_expedicion).'
                        </td>
                        <td style="padding: 3px;text-align:center;width:17px;max-width:17px;border: 1px solid #018A38;color:#31a05e;font-size:8px;">
                        '.dateFormatter('mes',$fecha_expedicion).'
                        </td>
                        <td style="padding: 3px;text-align:center;width:18px;max-width:18px;border: 1px solid #018A38;color:#31a05e;font-size:8px;">
                        '.dateFormatter('dia',$fecha_expedicion).'
                        </td>
                    </tr>
                </table>
            </td>
            <td style="padding: 0px;text-align:left;height:10px;width:191px;border: 1px solid #018A38;border-bottom:none;font-size:8px;">
                <span style="margin-left:10px;margin-top:2px">33. Lugar donde se practicó la retención</span>
                <p style="margin-left:10px;margin-top:10px">BUCARAMANGA</p>
            </td>
            <td style="padding: 0px;text-align:center;height:10px;width:40px;border: 1px solid #018A38;border-bottom:none;font-size:8px;">
                <span style="margin-top:2px">34. Cód. Dpto.</span>

                <p style="margin-left:10px;margin-top:10px">'.$tercero['Dep_Cod_Dian'].'</p>
            </td>
            <td style="padding: 0px;text-align:left;height:10px;width:63px;border: 1px solid #018A38;border-bottom:none;font-size:8px;">
                <span style="margin-top:2px">35. Cód. Ciudad/ Municipio</span>

                <p style="margin-left:10px;margin-top:10px">'.codigoMunicipioFormatter($tercero['Cod_Municipio']).'</p>
            </td>
        </tr>
    </table>

  
    <table style="margin-top: 0;border: 1px solid #018A38;border-collapse: collapse;">
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#CCE4D2;font-weight:bold">
            Concepto de los Ingresos
            </td>
            <td style="padding: 0px;text-align:left;width:10px;border: 1px solid #018A38;border-right:none;border-bottom:none;font-size:8px;background:#CCE4D2;">
                &nbsp;
            </td>
            <td style="padding: 3px;text-align:center;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:8px;font-weight:bold;background:#CCE4D2;">
                Valor
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                <span style="margin-left:5px">Pagos por salarios o emolumentos eclesiásticos</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                36
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                '.number_format(getValorRenglon(36),2,",",".").'
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                <span style="margin-left:5px">Pagos realizados con bonos electrónicos o de papel de servicio, cheques, tarjetas, vales, etc.</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                37
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
            '.number_format(getValorRenglon(37),2,",",".").'
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                <span style="margin-left:5px">Pagos por honorarios</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                38
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
            '.number_format(getValorRenglon(38),2,",",".").'
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                <span style="margin-left:5px">Pagos por servicios</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                39
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
            '.number_format(getValorRenglon(39),2,",",".").'
            </td>
        </tr>
        
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                <span style="margin-left:5px">Pagos por comisiones</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                40
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
            '.number_format(getValorRenglon(40),2,",",".").'
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                <span style="margin-left:5px">Pagos por prestaciones sociales</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                41
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
            '.number_format(getValorRenglon(41),2,",",".").'
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                <span style="margin-left:5px">Pagos por viáticos</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                42
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
            '.number_format(getValorRenglon(42),2,",",".").'
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                <span style="margin-left:5px">Pagos por gastos de representación</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                43
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
            '.number_format(getValorRenglon(43),2,",",".").'
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                <span style="margin-left:5px">Pagos por compensaciones por el trabajo asociado cooperativo</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                44
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
            '.number_format(getValorRenglon(44),2,",",".").'
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                <span style="margin-left:5px">Otros pagos</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                45
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
            '.number_format(getValorRenglon(45),2,",",".").'
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                <span style="margin-left:5px">Cesantías e intereses de cesantías efectivamente pagadas al empleado</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                46
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
            '.number_format(getValorRenglon(46),2,",",".").'
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                <span style="margin-left:5px">Cesantías consignadas al fondo de cesantías</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                47
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
            '.number_format(getValorRenglon(47),2,",",".").'
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                <span style="margin-left:5px">Pensiones de jubilación, vejez o invalidez</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                48
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
            '.number_format(getValorRenglon(48),2,",",".").'
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                <span style="margin-left:5px"><strong>Total de ingresos brutos</strong> (Sume 36 a 48)</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                <strong>49</strong>
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                <strong>'.number_format(getTotalIngresosBrutos(),2,",",".").'</strong>
            </td>
        </tr>

        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#CCE4D2;font-weight:bold">
            Concepto de los Aportes
            </td>
            <td style="padding: 0px;text-align:left;width:10px;border: 1px solid #018A38;border-right:none;border-bottom:none;font-size:8px;background:#CCE4D2;">
                &nbsp;
            </td>
            <td style="padding: 3px;text-align:center;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:8px;font-weight:bold;background:#CCE4D2;">
                Valor
            </td>
        </tr>

        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                <span style="margin-left:5px">Aportes obligatorios por salud a cargo del trabajador</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                50
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
            '.number_format($item49,2,",",".").'
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                <span style="margin-left:5px">Aportes obligatorios a fondos de pensiones y solidaridad pensional a cargo del trabajador</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                51
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
            '.number_format($item50,2,",",".").'
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                <span style="margin-left:5px">Cotizaciones voluntarias al régimen de ahorro individual con solidaridad - RAIS</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                52
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
            '.number_format(getValorRenglon(52),2,",",".").'
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                <span style="margin-left:5px">Aportes voluntarios a fondos de pensiones</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                53
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
            '.number_format(getValorRenglon(53),2,",",".").'
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                <span style="margin-left:5px">Aportes a cuentas AFC.</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
                54
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7">
            '.number_format(getValorRenglon(54),2,",",".").'
            </td>
        </tr>

        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:566px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#018A38;color:white;font-weight:bold">
                <span style="margin-left:5px">Valor de la retención en la fuente por ingresos laborales y de pensiones</span>
            </td>
            <td style="vertical-align:middle;padding: 1px;text-align:center;width:10px;border: 1px solid #018A38;font-size:9px;">
                <strong>55</strong>
            </td>
            <td style="padding: 3px;text-align:right;width:132px;border: 1px solid #018A38;font-size:9px;">
                <strong>'.number_format(getValorRenglon(55),2,",",".").'</strong>
            </td>
        </tr>
        
        <tr>
            <td colspan="3" style="padding: 3px;padding-left:1px;height:25px;text-align:left;border: 1px solid #018A38;font-size:9px;">
                <span style="margin-left:5px">Nombre del pagador o agente retenedor</span>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="padding: 3px;padding-left:1px;text-align:center;border: 1px solid #018A38;font-size:9px;background:#CCE4D2;font-weight:bold">
                Datos a cargo del trabajador o pensionado
            </td>
        </tr>
    </table>

    <table style="margin-top: 0;border: 1px solid #018A38;border-collapse: collapse;">
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:366px;border: 1px solid #018A38;font-size:9px;background:#f7f7f7;font-weight:bold">
                Concepto de otros ingresos
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:10px;border: 1px solid #018A38;border-right:none;font-size:9px;background:#f7f7f7;font-weight:bold">
                &nbsp;
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:150px;border: 1px solid #018A38;font-size:9px;background:#f7f7f7;font-weight:bold">
                Valor recibido
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:10px;border: 1px solid #018A38;border-right:none;font-size:9px;background:#f7f7f7;font-weight:bold">
                &nbsp;
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:150px;border: 1px solid #018A38;font-size:9px;background:#f7f7f7;font-weight:bold">
                Valor retenido
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:366px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                <span style="margin-left:5px">Arrendamientos</span>
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                56
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:150px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                63
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:150px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:366px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;">
                <span style="margin-left:5px">Honorarios, comisiones y servicios</span>
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;">
                57
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:150px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;">
                
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;">
                64
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:150px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;">
                
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:366px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                <span style="margin-left:5px">Intereses y rendimientos financieros</span>
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                58
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:150px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                65
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:150px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:366px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;">
                <span style="margin-left:5px">Enajenación de activos fijos</span>
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;">
                59
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:150px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;">
                
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;">
                66
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:150px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;">
                
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:366px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                <span style="margin-left:5px">Loterías, rifas, apuestas y similares</span>
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                60
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:150px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                67
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:150px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:366px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;">
                <span style="margin-left:5px">Otros</span>
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;">
                61
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:150px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;">
                
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;">
                68
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:150px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;">
                
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:366px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                <span style="margin-left:5px"><strong>Totales:</strong> (<strong>Valor recibido:</strong> Sume 56 a 61), (Valor retenido: Sume 63 a 68) </span>
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                <strong>62</strong>
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:150px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:10px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                <strong>69</strong>
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:150px;border: 1px solid #018A38;border-bottom:none;font-size:9px;">
                
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;text-align:left;width:366px;border: 1px solid #018A38;border-bottom:none;border-right:none;font-size:9px;background:#f7f7f7;">
                <span style="margin-left:5px"><strong>Total retenciones año gravable '.dateFormatter('anio',$fecha_inicial).'</strong> (Sume 53 + 67)</span>
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:10px;border-bottom: none;font-size:9px;background:#f7f7f7;">
                &nbsp;
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:150px;border-bottom: none;border-right: 1px solid #018A38;font-size:9px;background:#f7f7f7;">
                &nbsp;
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:10px;border: 1px solid #018A38;font-size:9px;background:#f7f7f7;">
                <strong>70</strong>
            </td>
            <td style="padding: 3px;padding-left:1px;text-align:center;width:150px;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;">
                
            </td>
        </tr>
    </table>

    <table style="margin-top: 0;border: 1px solid #018A38;border-collapse: collapse;">
        <tr>
            <td style="padding: 3px;padding-left:1px;width:15px;text-align:center;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#CCE4D2;font-weight:bold">
                Item
            </td>
            <td style="padding: 3px;padding-left:1px;width:524px;text-align:center;border: 1px solid #018A38;border-right:none;border-bottom:none;font-size:9px;background:#CCE4D2;font-weight:bold">
                71. Identificación de los bienes y derechos poseídos
            </td>
            <td style="padding: 3px;padding-left:1px;width:10px;text-align:center;border-top: 1px solid #018A38;border-right: 1px solid #018A38;font-size:9px;background:#CCE4D2;font-weight:bold">
                &nbsp;
            </td>
            <td style="padding: 3px;padding-left:1px;width:151px;text-align:center;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#CCE4D2;font-weight:bold">
                72. Valor Patrimonial
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;width:15px;text-align:center;border: 1px solid #018A38;border-bottom:none;font-size:9px;font-weight:bold">
                1
            </td>
            <td style="padding: 3px;padding-left:1px;width:524px;text-align:center;border: 1px solid #018A38;border-right:none;border-bottom:none;font-size:9px;font-weight:bold">
                
            </td>
            <td style="padding: 3px;padding-left:1px;width:10px;text-align:center;border-top: 1px solid #018A38;border-right: 1px solid #018A38;font-size:9px;font-weight:bold">
                &nbsp;
            </td>
            <td style="padding: 3px;padding-left:1px;width:151px;text-align:center;border: 1px solid #018A38;border-bottom:none;font-size:9px;font-weight:bold">
                
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;width:15px;text-align:center;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;font-weight:bold">
                2
            </td>
            <td style="padding: 3px;padding-left:1px;width:524px;text-align:center;border: 1px solid #018A38;border-right:none;border-bottom:none;background:#f7f7f7;font-size:9px;font-weight:bold">
                
            </td>
            <td style="padding: 3px;padding-left:1px;width:10px;text-align:center;border-top: 1px solid #018A38;border-right: 1px solid #018A38;background:#f7f7f7;font-size:9px;font-weight:bold">
                &nbsp;
            </td>
            <td style="padding: 3px;padding-left:1px;width:151px;text-align:center;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;font-weight:bold">
                
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;width:15px;text-align:center;border: 1px solid #018A38;border-bottom:none;font-size:9px;font-weight:bold">
                3
            </td>
            <td style="padding: 3px;padding-left:1px;width:524px;text-align:center;border: 1px solid #018A38;border-right:none;border-bottom:none;font-size:9px;font-weight:bold">
                
            </td>
            <td style="padding: 3px;padding-left:1px;width:10px;text-align:center;border-top: 1px solid #018A38;border-right: 1px solid #018A38;font-size:9px;font-weight:bold">
                &nbsp;
            </td>
            <td style="padding: 3px;padding-left:1px;width:151px;text-align:center;border: 1px solid #018A38;border-bottom:none;font-size:9px;font-weight:bold">
                
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;width:15px;text-align:center;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;font-weight:bold">
                4
            </td>
            <td style="padding: 3px;padding-left:1px;width:524px;text-align:center;border: 1px solid #018A38;border-right:none;border-bottom:none;background:#f7f7f7;font-size:9px;font-weight:bold">
                
            </td>
            <td style="padding: 3px;padding-left:1px;width:10px;text-align:center;border-top: 1px solid #018A38;border-right: 1px solid #018A38;background:#f7f7f7;font-size:9px;font-weight:bold">
                &nbsp;
            </td>
            <td style="padding: 3px;padding-left:1px;width:151px;text-align:center;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;font-weight:bold">
                
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;width:15px;text-align:center;border: 1px solid #018A38;border-bottom:none;font-size:9px;font-weight:bold">
                5
            </td>
            <td style="padding: 3px;padding-left:1px;width:524px;text-align:center;border: 1px solid #018A38;border-right:none;border-bottom:none;font-size:9px;font-weight:bold">
                
            </td>
            <td style="padding: 3px;padding-left:1px;width:10px;text-align:center;border-top: 1px solid #018A38;border-right: 1px solid #018A38;font-size:9px;font-weight:bold">
                &nbsp;
            </td>
            <td style="padding: 3px;padding-left:1px;width:151px;text-align:center;border: 1px solid #018A38;border-bottom:none;font-size:9px;font-weight:bold">
                
            </td>
        </tr>
        <tr>
            <td style="padding: 3px;padding-left:1px;width:15px;text-align:center;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;font-weight:bold">
                6
            </td>
            <td style="padding: 3px;padding-left:1px;width:524px;text-align:center;border: 1px solid #018A38;border-right:none;border-bottom:none;background:#f7f7f7;font-size:9px;font-weight:bold">
                
            </td>
            <td style="padding: 3px;padding-left:1px;width:10px;text-align:center;border-top: 1px solid #018A38;border-right: 1px solid #018A38;background:#f7f7f7;font-size:9px;font-weight:bold">
                &nbsp;
            </td>
            <td style="padding: 3px;padding-left:1px;width:151px;text-align:center;border: 1px solid #018A38;border-bottom:none;font-size:9px;background:#f7f7f7;font-weight:bold">
                
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 3px;padding-left:1px;text-align:;border: 1px solid #018A38;font-size:9px;background:#018A38;color:white;font-weight:bold">
                <span style="margin-left:5px">Deudas vigentes a 31 de Diciembre de '.dateFormatter('anio',$fecha_inicial).'</span>
            </td>
            <td style="padding: 3px;padding-left:1px;width:10px;text-align:center;border: 1px solid #018A38;font-size:9px;font-weight:bold">
                73
            </td>
            <td style="padding: 3px;padding-left:1px;width:151px;text-align:center;border: 1px solid #018A38;font-size:9px;font-weight:bold">
                
            </td>
        </tr>
        
        <tr>
            <td colspan="4" style="padding: 3px;padding-left:1px;text-align:center;border: 1px solid #018A38;font-size:9px;background:#CCE4D2;font-weight:bold">
                Identificación de la persona dependiente de acuerdo al parágrafo 2 del artículo 387 del Estatuto Tributario
            </td>
        </tr>

    </table>

    <table style="margin-top: 0;border: 1px solid #018A38;border-collapse: collapse;">
        <tr> 
            <td style="padding: 0px;height:20px;width:100px;text-align:left;border: 1px solid #018A38;font-size:9px;">
                <span style="margin-left:10px;">74. Tipo documento</span>
            </td>
            <td style="padding: 0px;height:20px;width:100px;text-align:left;border: 1px solid #018A38;font-size:9px;">
                <span style="margin-left:10px;">75. No. Documento</span>
            </td>
            <td style="padding: 0px;height:20px;width:420px;text-align:left;border: 1px solid #018A38;font-size:9px;">
                <span style="margin-left:10px;">73. Apellidos y Nombres</span>
            </td>
            <td style="padding: 0px;height:20px;width:110px;text-align:left;border: 1px solid #018A38;font-size:9px;">
                <span style="margin-left:10px;">74. Parentesco</span>
            </td>
        </tr>
    </table>

    <table style="margin-top: 0;border: 1px solid #018A38;border-collapse: collapse;">
        <tr>
            <td style="padding: 0px;width:550px;text-align:left;border: 1px solid #018A38;font-size:7px;">
                <span style="margin-left:10px;margin-top:3px">Certifico que durante el año gravable de '.dateFormatter('anio',$fecha_inicial).':</span>

                <ol style="margin-left:-12px;margin-bottom:0">
                    <li>Mi patrimonio bruto era igual o inferior a 4.500 UVT ($'.number_format(4500*$config['Valor_Uvt'],0,"",".").')</li>
                    <li>No fui responsable del impuesto sobre las ventas ni del impuesto nacional al consumo.</li>
                    <li>Mis ingresos brutos fueron inferiores a 1.400 UVT ($'.number_format(4500*$config['Valor_Uvt'],0,"",".").').</li>
                    <li>Mis consumos mediante tarjeta de crédito no excedieron la suma de 1.400 UVT ($'.number_format(4500*$config['Valor_Uvt'],0,"",".").').</li>
                    <li>Que el total de mis compras y consumos no superaron la suma de 1.400 UVT ($'.number_format(4500*$config['Valor_Uvt'],0,"",".").')</li>
                    <li>Que el valor total de mis consignaciones bancarias, depósitos o inversiones financieras no excedieron los 1.400 UVT ($'.number_format(4500*$config['Valor_Uvt'],0,"",".").').</li>
                </ol>
                <span style="margin-left:10px;margin-top:-5px">Por lo tanto, manifiesto que no estoy obligado a presentar declaración de renta y complementario por el año gravable '.dateFormatter('anio',$fecha_inicial).'.</span>
            </td>
            <td style="padding: 0px;width:186px;text-align:left;border: 1px solid #018A38;font-size:7px;">
                <span style="margin-left:10px;">Firma del Trabajador o Pensionado</span>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 0px;text-align:center;font-size:7px;">
                NOTA: Este certificado sustituye para todos los efectos legales la declaración de Renta y Complementario para el trabajador o pensionado que lo firme.
            </td>
        </tr>
    </table>
';


/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm">
                <div class="page-content" >'.
                    $contenido.'
                </div>
            </page>';
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('P', 'Legal', 'Es', true, 'UTF-8', array(5, 5, 5, 0));
    $html2pdf->writeHTML($content);
    $direc = "Certificado_$nit.pdf"; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

/* FUNCIONES BASICAS */
function fecha($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	return $date[2] . "/". $date[1] ."/". $date[0];
}

function strFechaExpedicion($fecha) {
    $meses = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
    
    $dia = date('d',strtotime($fecha));
    $mes = date('n',strtotime($fecha));
    $anio = date('Y',strtotime($fecha));

    $mes = $meses[$mes-1];

    $str = "Este certificado se expide a los $dia del mes de $mes del $anio";

    return $str;
}

function getDatosTercero($nit) {
    global $item49, $item50;
    $query = "SELECT
        F.Identificacion_Funcionario as Nit, 
        CONCAT_WS(' ',Nombres,Apellidos) AS Nombre, 
        Primer_Nombre, 
        Segundo_Nombre, 
        Primer_Apellido,
        Segundo_Apellido, 
        Direccion_Residencia AS Direccion,
        M.Dep_Cod_Dian,
        M.Cod_Municipio 
        FROM Funcionario F
        LEFT JOIN Contrato_Funcionario FC ON F.Identificacion_Funcionario = FC.Identificacion_Funcionario
        LEFT JOIN (
                SELECT T.Id_Municipio, T.Codigo_Dane AS Cod_Municipio, 
                (SELECT Codigo FROM Departamento WHERE Id_Departamento = T.Id_Departamento) AS Dep_Cod_Dian
                FROM Municipio T
            ) M ON FC.Id_Municipio = M.Id_Municipio 
        WHERE F.Identificacion_Funcionario = $nit

    UNION
    (SELECT C.Id_Cliente as Nit, IF(Primer_Nombre != '',CONCAT_WS(' ',Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido),Razon_Social) AS Nombre, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Direccion, (SELECT Codigo FROM Departamento WHERE Id_Departamento = C.Id_Departamento) AS Dep_Cod_Dian, (SELECT Codigo_Dane FROM Municipio WHERE Id_Municipio = C.Id_Municipio) AS Cod_Municipio FROM Cliente C WHERE Id_Cliente = $nit)
    UNION
    (SELECT P.Id_Proveedor as Nit, IF(Primer_Nombre != '',CONCAT_WS(' ',Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido),Razon_Social) AS Nombre, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Direccion, (SELECT Codigo FROM Departamento WHERE Id_Departamento = P.Id_Departamento) AS Dep_Cod_Dian, (SELECT Codigo_Dane FROM Municipio WHERE Id_Municipio = P.Id_Municipio) AS Cod_Municipio FROM Proveedor P WHERE Id_Proveedor = $nit)
    ";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $datos = $oCon->getData();

    
    // $query = "SELECT * FROM Parametro_Certificado_Ingreso_Retencion_Renglon";

    // $oCon = new consulta();
    // $oCon->setQuery($query);
    // $oCon->setTipo('Multiple');
    // $resultado = $oCon->getData();
    // unset($oCon);

    for ($i=36; $i< 55 ; $i++) { 
        switch($i){
            case 49:
                $datos["Renglon_$i"] = getTotalIngresosBrutos();
                break;
            case 50;
                $datos["Renglon_$i"] = $item49; break;
            case 51;
                $datos["Renglon_$i"] = $item50; break;

            default:
                // $valorRenglon=getValorRenglon($i);
                $datos["Renglon_$i"] = getValorRenglon($i);
                break;
        }
    }

    echo "<tr>";
    foreach($datos as $valor ) {
        echo "<td>$valor</td>";
    }
    echo "</tr>";
    return '';


    unset($oCon);

    return $datos;
}

function calcularBase($porcentaje,$retencion) {
    return $porcentaje > 0 ? $retencion * 100 / $porcentaje : '0';
}

function dateFormatter($tipo,$fecha) {
    switch ($tipo) {
        case 'dia':
            $fecha = date('d',strtotime($fecha));
            break;
        case 'mes':
            $fecha = date('m',strtotime($fecha));
            break;
        case 'anio':
            $fecha = date('Y',strtotime($fecha));
            break;
    }

    return $fecha;
}

function codigoMunicipioFormatter($cod) {
    return str_pad($cod,3,'0',STR_PAD_LEFT);
}

function getValorRenglon($renglon) {
    global $item49, $item50;

    $condiciones = getCondiciones();
    $cuentas = getCuentasByRenglon($renglon);
    $operacion = operacionValor($cuentas['Tipo_Valor']);
    
   /* echo '<pre>';
    if($renglon==39){
        var_dump($condiciones);
        var_dump($cuentas);
        var_dump($operacion);
            
    }*/

    
    $cond_nomina = '';

    if ($renglon > 47) {
        $cond_nomina .= ' AND Id_Modulo IN (18,30)';
    }
    


    if ($operacion != '') {
        $query = "SELECT IFNULL($operacion,0) AS Valor FROM Movimiento_Contable WHERE Id_Plan_Cuenta IN ($cuentas[Cuentas]) $condiciones $cond_nomina";
/*
if($renglon==39){
       echo $query;exit;
    }*/

        $oCon = new consulta();
        $oCon->setQuery($query);
        $resultado = $oCon->getData();
        unset($oCon);
    
    if ($renglon == 36) {
        $x4 =  $resultado ? ($resultado['Valor'] * .04 ) : '0';
        $item49 = $x4;
        $item50 = $x4;
        
            
        // echo "$item49, $item50"; exit;
        
    }

        
        return $resultado ? $resultado['Valor'] : '0';
    }

    return '0';
}

function getCuentasByRenglon($renglon) {
    $query = "SELECT * FROM Parametro_Certificado_Ingreso_Retencion_Renglon WHERE Renglon = $renglon";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado;
}

function operacionValor($operacion) {
    $str = '';
    switch ($operacion) {
        case 'D':
            $str = "(SUM(Debe))";
            break;
        case 'C':
            $str = "(SUM(Haber))";
            break;
        case 'D-C':
            $str = "(SUM(Debe) - SUM(Haber))";
            break;
        case 'C-D':
            $str = "(SUM(Haber) - SUM(Debe))";
            break;
    }

    return $str;
}

function getTotalIngresosBrutos() {
    $total = 0;
    for ($renglon=36; $renglon <= 48; $renglon++) { // Valores de los renglones 37 al 47
        $total += getValorRenglon($renglon);
    }

    return $total;
}

function getCondiciones() {
    global $fecha_inicial;
    global $fecha_final;
    global $nit;

    $condicion = '';
    if (isset($fecha_inicial) && $fecha_inicial != "" && isset($fecha_final) && $fecha_final != "") {
        $fecha_inicio = $fecha_inicial;
        $fecha_fin = $fecha_final;
        $condicion .= " AND (DATE(Fecha_Movimiento) BETWEEN '$fecha_inicio' AND '$fecha_fin')";
    }
    if (isset($nit) && $nit != '') {
        $condicion .= " AND Nit = $nit";
    }

    $condicion .= ' AND Estado = "Activo" AND Detalles NOT LIKE "CIERRE 20%" ';

    return $condicion;
}


function getNitsFuncionarios() {
    global $fecha_inicial;
    global $fecha_final;
    global $nit;
    $condicion = '';
    if (isset($fecha_inicial) && $fecha_inicial != "" && isset($fecha_final) && $fecha_final != "") {
        $fecha_inicio = $fecha_inicial;
        $fecha_fin = $fecha_final;
        $condicion .= " AND (DATE(Fecha_Movimiento) BETWEEN '$fecha_inicio' AND '$fecha_fin')";
    }
    $condicion .= ' AND Estado = "Activo" AND Detalles NOT LIKE "CIERRE 20%" ';

    $query = "SELECT DISTINCT Nit from Movimiento_Contable Where Tipo_Nit ='Funcionario' $condicion";
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado;
}


/* FIN FUNCIONES BASICAS*/

?>