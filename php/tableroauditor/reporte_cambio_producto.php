<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="reporte_Cambio_Productoa.php.xls"');
header('Cache-Control: max-age=0');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$condicion='';


if (isset($_REQUEST['fechas']) && $_REQUEST['fechas']) {
    $fechas = SepararFechas($_REQUEST['fechas']);    
    $condicion .= " AND DATE(CP.Fecha) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."'";   
}
$fecha_fin='';
$fecha_inicio='';

$fecha_inicio=$_REQUEST['fini'];
$fecha_fin=$_REQUEST['ffin'];

if($fecha_inicio!='' && $fecha_fin!=''){
    $condicion .= " AND DATE(CP.Fecha) BETWEEN '".$fecha_inicio."' AND '".$fecha_fin."'";   
}

  
$query=CrearQuery();



$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$datos= $oCon->getData();
unset($oCon);

$reporte=[];
$j=-1;
 


foreach ($datos as $key => $value) {
    $disp=$value['Id_Dispensacion'];
    $datos[$key]['Dis']=GetCodigos($disp);
}


ArmarTablaResultados($datos);







function CrearQuery(){
    global $condicion;
    $query=" SELECT R.*, PR.Nombre_Comercial as Nombre_Antiguo,PR.Codigo_Cum as Cum_Antiguo
    FROM ( SELECT CP.Fecha,CP.Id_Cambio_Producto, CP.Id_Dispensacion, P.Nombre_Comercial, P.Codigo_Cum,CP.Id_Producto_Final, 
    (SELECT CONCAT(Nombres,' ', Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=CP.Identificacion_Funcionario ) as Funcionario  
    FROM Cambio_Producto CP INNER JOIN Producto P ON CP.Id_Producto_Inicial=P.Id_Producto 
    WHERE CP.Id_Dispensacion !=''  $condicion ) R INNER JOIN Producto PR ON R.Id_Producto_Final = PR.Id_Producto  ";
    return $query;
}

function ArmarTablaResultados($resultados){

    $contenido_excel = '';

    $contenido_excel = '
    <table border=1>
    <tr>
        <td align="center"><strong>Dispensaciones</strong></td>
        <td align="center"><strong>Medicamento Antiguo</strong></td>
        <td align="center"><strong>Cum Antiguo </strong></td>
        <td align="center"><strong>Medicamento Nuevo</strong></td>
        <td align="center"><strong>Cum Nuevo</strong></td>
        <td align="center"><strong>Funcionario</strong></td>
        <td align="center"><strong>Fecha</strong></td>
        
    </tr>';

    if (count($resultados) > 0) {
        foreach ($resultados as $i => $r) {

            $contenido_excel .= '
            <tr>
                <td>'.$r['Dis'].'</td>
                <td>'.$r['Nombre_Antiguo'].'</td>
                <td>'.$r['Cum_Antiguo'].'</td>
                <td>'.$r["Nombre_Comercial"].'</td>
                <td>'.$r["Codigo_Cum"].'</td>
                <td>'.$r["Funcionario"].'</td>
                <td>'.$r["Fecha"].'</td>
               
            </tr>';
        } 
    }else{

        $contenido_excel .= '
        <tr>
            <td colspan="7" align="center">SIN RESULTADOS PARA MOSTRAR</td>
        </tr>';
    }        
       

    $contenido_excel .= '
    </table>';

    echo $contenido_excel;
}

function GetCodigos($id){
    $query="SELECT GROUP_CONCAT(Codigo) as Codigo FROM Dispensacion WHERE Id_Dispensacion IN ($id)";

    $oCon= new consulta();
    $oCon->setQuery($query);
    $dis= $oCon->getData();
    unset($oCon);

    return $dis['Codigo'];
}
function SepararFechas($fechas){
    $fechas_separadas = explode(" - ", $fechas);
    return $fechas_separadas;
}


