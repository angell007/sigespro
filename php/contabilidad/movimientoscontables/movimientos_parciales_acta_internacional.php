<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include_once('../../../class/class.querybasedatos.php');
require_once('../../../class/html2pdf.class.php');

$id_registro = ( isset( $_REQUEST['id_registro'] ) ? $_REQUEST['id_registro'] : '' );
$id_funcionario_imprime = ( isset( $_REQUEST['id_funcionario_elabora'] ) ? $_REQUEST['id_funcionario_elabora'] : '' );
$tipo_valor = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$titulo = $tipo_valor != '' ? "CONTABILIZACIÓN NIIF" : "CONTABILIZACIÓN PCGA";


$queryObj = new QueryBaseDatos();

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

$oItem = new complex('Nacionalizacion_Parcial','Id_Nacionalizacion_Parcial', $id_registro);
$datos = $oItem->getData();
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

/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */

    $query = '
        SELECT
        PC.Codigo,
        PC.Nombre,
        PC.Codigo_Niif,
        PC.Nombre_Niif,
        MC.Nit,
        MC.Fecha_Movimiento AS Fecha,
        MC.Tipo_Nit,
        MC.Id_Registro_Modulo,
        MC.Documento,
        MC.Numero_Comprobante,
        MC.Debe,
        MC.Haber,
        MC.Debe_Niif,
        MC.Haber_Niif,
        (CASE
            WHEN MC.Tipo_Nit = "Cliente" THEN (SELECT Nombre FROM Cliente WHERE Id_Cliente = MC.Nit)
            WHEN MC.Tipo_Nit = "Proveedor" THEN (SELECT Nombre FROM Proveedor WHERE Id_Proveedor = MC.Nit)
            WHEN MC.Tipo_Nit = "Funcionario" THEN (SELECT CONCAT_WS(" ", Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = MC.Nit)
        END) AS Nombre_Cliente,
        "Factura Venta" AS Registro
        FROM Movimiento_Contable MC
        INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
        WHERE
            MC.Estado = "Activo" AND Id_Modulo = 23 AND Id_Registro_Modulo ='.$id_registro.' ORDER BY Debe DESC';

    $queryObj->SetQuery($query);
    $movimientos = $queryObj->ExecuteQuery('multiple');


    $query = '
        SELECT
        SUM(MC.Debe) AS Debe,
        SUM(MC.Haber) AS Haber,
        SUM(MC.Debe_Niif) AS Debe_Niif,
        SUM(MC.Haber_Niif) AS Haber_Niif
        FROM Movimiento_Contable MC
        WHERE
            MC.Estado = "Activo" AND Id_Modulo = 23 AND Id_Registro_Modulo ='.$id_registro;

    $queryObj->SetQuery($query);
    $movimientos_suma = $queryObj->ExecuteQuery('simple');

    $query = '
        SELECT
            CONCAT_WS(" ", Nombres, Apellidos) AS Nombre_Funcionario
        FROM Funcionario
        WHERE
            Identificacion_Funcionario ='.$id_funcionario_imprime;

    $queryObj->SetQuery($query);
    $imprime = $queryObj->ExecuteQuery('simple');

    $query = '
        SELECT
            CONCAT_WS(" ", Nombres, Apellidos) AS Nombre_Funcionario
        FROM Funcionario
        WHERE
            Identificacion_Funcionario ='.$datos['Identificacion_Funcionario'];

    $queryObj->SetQuery($query);
    $elabora = $queryObj->ExecuteQuery('simple');

    unset($queryObj);
        
        $codigos ='
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">'.$titulo.'</h4>
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">Nacionalización Parcial</h4>
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">'.$movimientos[0]['Numero_Comprobante'].'</h4>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">Fecha '.fecha($movimientos[0]['Fecha']).'</h5>
        ';
        

        $contenido = '<table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:78px;font-weight:bold;text-align:center;background:#cecece;;border:1px solid #cccccc;">
                Cuenta '.$tipo_valor.'
            </td>   
            <td style="width:170px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               Nombre Cuenta '.$tipo_valor.'
            </td>
            <td style="width:60px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               Documento
            </td>
            <td style="width:155px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                Nit
            </td>
            <td style="width:115px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                Debitos '.$tipo_valor.'
            </td>
            <td style="width:115px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                Crédito '.$tipo_valor.'
            </td>
        </tr>';

    if (count($movimientos) > 0) {
        
        foreach ($movimientos as $value) {
        
            if ($tipo_valor != '') {
                $codigo = $value['Codigo_Niif'];
                $nombre_cuenta = $value['Nombre_Niif'];
                $debe = $value['Debe_Niif'];
                $haber = $value['Haber_Niif'];
                $total_debe = $movimientos_suma["Debe_Niif"];
                $total_haber = $movimientos_suma["Haber_Niif"];
            } else {
                $codigo = $value['Codigo'];
                $nombre_cuenta = $value['Nombre'];
                $debe = $value['Debe'];
                $haber = $value['Haber'];
                $total_debe = $movimientos_suma["Debe"];
                $total_haber = $movimientos_suma["Haber"];
            }
        
            $contenido .= '
                <tr>
                    <td style="width:78px;padding:4px;text-align:left;border:1px solid #cccccc;">
                        '.$codigo.'
                    </td>
                    <td style="width:150px;padding:4px;text-align:left;border:1px solid #cccccc;">
                        '.$nombre_cuenta.'
                    </td>
                    <td style="width:50px;padding:4px;text-align:right;border:1px solid #cccccc;">
                        '.$value["Documento"].'
                    </td>
                    <td style="width:140px;padding:4px;text-align:right;border:1px solid #cccccc;">
                       '.$value['Nombre_Cliente'].' - '.$value["Nit"].'
                    </td>
                    <td style="width:100px;padding:4px;text-align:right;border:1px solid #cccccc;">
                        $ '.number_format($debe, 2, ".", ",").'
                    </td>
                    <td style="width:100px;padding:4px;text-align:right;border:1px solid #cccccc;">
                        $ '.number_format($haber, 2, ".", ",").'
                    </td>
                </tr>
            ';
        }

        $contenido .= '
            <tr>
                <td colspan="4" style="padding:4px;text-align:center;border:1px solid #cccccc;">
                    TOTAL
                </td>
                <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
                    $ '.number_format($total_debe, 2, ".", ",").'
                </td>
                <td style="padding:4px;text-align:right;border:1px solid #cccccc;">
                    $ '.number_format($total_haber, 2, ".", ",").'
                </td>
            </tr>';
    }

    $contenido .= '</table>
    
    <table style="margin-top:10px;" cellpadding="0" cellspacing="0">

        <tr>
            <td style="font-weight:bold;width:170px;border:1px solid #cccccc;padding:4px">
                Elaboró:
            </td>
            <td style="font-weight:bold;width:168px;border:1px solid #cccccc;padding:4px">
                Imprimió:
            </td>
            <td style="font-weight:bold;width:168px;border:1px solid #cccccc;padding:4px">
                Revisó:
            </td>
            <td style="font-weight:bold;width:168px;border:1px solid #cccccc;padding:4px">
                Aprobó:
            </td>
        </tr>

        <tr>
            <td style="font-size:10px;width:170px;border:1px solid #cccccc;padding:4px">
            '.$elabora['Nombre_Funcionario'].'
            </td>
            <td style="font-size:10px;width:168px;border:1px solid #cccccc;padding:4px">
            '.$imprime['Nombre_Funcionario'].'
            
            </td>
            <td style="font-size:10px;width:168px;border:1px solid #cccccc;padding:4px">
            
            </td>
            <td style="font-size:10px;width:168px;border:1px solid #cccccc;padding:4px">
            
            </td>
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

$marca_agua = '';

if ($movimientos[0]['Estado'] == 'Anulado') {
    $marca_agua = 'backimg="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/anulada.png"';
}

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm" '.$marca_agua.'>
                <div class="page-content" >'.
                    $cabecera.
                    $contenido.
                    '
                </div>
            </page>';
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

// var_dump($content);
// exit;

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

?>