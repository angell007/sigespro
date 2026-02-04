<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Remisiones.xls"');
header('Cache-Control: max-age=0'); 

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion='';
$query=CrearQuery();
ArmarReporte($query);

function ArmarReporte($query){

    $encabezado=GetEncabezado($query);
    $datos=GetDatos($query);
    $contenido = '';
    
    if ($encabezado) {
        $contenido .= '<table ><tr>';
        foreach ($encabezado as $key => $value) {
          $contenido.='<td>'.$key.'</td>';
        }
        $contenido .= '</tr>';
    }

    if ($datos) {
        foreach ($datos as $i => $dato) {
            $contenido .= '<tr>';
    
            foreach ($dato as $key => $value) {
                    $contenido.= '<td>' . $dato[$key] . '</td>';
            }
    
            $contenido .= '</tr>';
        }
    
     $contenido .= '</table>';
    }

    if ($contenido == '') {
        $contenido .= '
            <table>
                <tr>
                    <td>NO EXISTE INFORMACION PARA MOSTRAR</td>
                </tr>
            </table>
        ';
    }

 echo $contenido;

}
function GetEncabezado($query){
    $oCon= new consulta();
    $oCon->setQuery($query);
    $encabezado= $oCon->getData();
    unset($oCon);

    $encabezado['Diferencia_En_fecha'] = 'Sin Diferencia';

    return $encabezado;
}

function GetDatos($query){
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $datos= $oCon->getData();
    unset($oCon);

    $datos = GetDiferenciaFechas($datos);
    return $datos;
}
function CrearQuery(){
    global $condicion;
    if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['ffin'] != "" && isset($_REQUEST['ffin']) ) {
        $condicion.=" WHERE R.Tipo_Origen='Bodega' AND R.Tipo_Destino='Punto_Dispensacion' AND  DATE(R.Fecha)>='".$_REQUEST['fini']."' AND DATE(R.Fecha)<='".$_REQUEST['ffin']."'";
    }

    $query='SELECT R.Codigo,R.Nombre_Origen as Origen, R.Nombre_Destino, IFNULL((SELECT CONCAT_WS(" ",Nombres,Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=R.Fase_1),"Sin Funcionario") as Fase_1, IFNULL((SELECT CONCAT_WS(" ",Nombres,Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=R.Fase_2),"Sin Funcionario") as Fase_2,(SELECT COUNT(Id_Producto_Remision) FROM Producto_Remision WHERE Id_Remision=R.Id_Remision) as Total_Items, R.Fecha, IF((R.Id_Origen = 2 OR R.Id_Origen = 3), IFNULL(R.Fin_Fase1, "Sin Fecha"), IFNULL(R.Fin_Fase2, "Sin Fecha")) Fecha_Despacho

    FROM Remision R '.$condicion;
           
        

    return $query;
}

function GetDiferenciaFechas($datos){
    foreach ($datos as $key => $record) {
        if ($record['Fecha_Despacho'] != 'Sin Fecha') {
            $date1 = new DateTime($record['Fecha']);
            $date2 = new DateTime($record['Fecha_Despacho']);
            $diff = $date1->diff($date2);
            $dias = intval($diff->days) > 1 ? $diff->days . ' dias ' : $diff->days . ' dia ';
            $horas = intval($diff->h) > 1 ? $diff->h . ' horas ' : $diff->h . ' hora ';
            $minutos = intval($diff->i) > 1 ? $diff->i . ' minutos ' : $diff->i . ' minuto ';
            $segudos = intval($diff->s) > 1 ? $diff->s . ' segundos ' : $diff->s . ' segundo ';
            $datos[$key]['Diferencia_En_fecha'] = $dias.$horas.$minutos.$segundos;
        }else{
            $datos[$key]['Diferencia_En_fecha'] = 'Sin Diferencia';
        }
    }

    return $datos;
}



