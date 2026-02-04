<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="reporte_ajuste_individual.php.xls"');
    header('Cache-Control: max-age=0'); 

    require_once('../../config/start.inc.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.querybasedatos.php');

    $queryObj = new QueryBaseDatos();
    $http_response = new HttpResponse();
    $response = array();

    $condiciones = SetConditions();
    $ajustes = GetAjsutes($condiciones);
    ArmarTablaResultados($ajustes);

    function SetConditions(){
        $req = $_REQUEST;
        $condicion = '';

        if (isset($req['fechas']) && $req['fechas']) {
            $fechas = SepararFechas($req['fechas']);

            if ($condicion != "") {
                $condicion .= " AND DATE(A.Fecha) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."'";
            } else {
                $condicion .= " WHERE DATE(A.Fecha) BETWEEN '".$fechas[0]."' AND '".$fechas[1]."'";
            }
        }

        if (isset($req['tipo']) && $req['tipo']) {
            $tipo=$_REQUEST['tipo'];
            if($tipo!=''){
                if ($condicion != "") {
                    $condicion .= " AND A.Tipo  ='$tipo'";
                } else {
                    $condicion .= " WHERE A.Tipo ='".$tipo."' ";
                }
            }
           
        }
        if (isset($req['origen']) && $req['origen']) {
            $origen=$_REQUEST['origen'];
            if($origen!=''){
                if ($condicion != "") {
                    $condicion .= " AND A.Origen_Destino ='$origen' ";
                } else {
                    $condicion .= " WHERE A.Origen_Destino ='".$origen."' ";
                }
            }
           
        }
        if (isset($req['id']) && $req['id']) {
            $id=$_REQUEST['id'];
            if($id!=''){
                if ($condicion != "") {
                    $condicion .= " AND A.Id_Origen_Destino=  '$id' ";
                } else {
                    $condicion .= " WHERE A.Id_Origen_Destino='".$id."' ";
                }
            }
         
        }

        return $condicion;
    }

    function GetAjsutes($condiciones){
        global $queryObj;

        $query_ajuste = 'SELECT P.Nombre_Comercial, CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida, " ") as Producto,
        IF(P.Laboratorio_Generico IS NULL,P.Laboratorio_Comercial,P.Laboratorio_Generico) as Laboratorio,
        P.Embalaje,P.Codigo_Cum, 
         PAI.Fecha_Vencimiento,
         PAI.Lote,
         PAI.Costo,
         PAI.Cantidad,(PAI.Cantidad*PAI.Costo) as Subtotal,
         PAI.Observaciones, 
         (
          CASE  
           WHEN A.Origen_Destino = "Bodega"  THEN CONCAT_WS(" ", "BODEGA", (SELECT Nombre FROM Bodega WHERE Id_Bodega=A.Id_Origen_Destino))
           ELSE  (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=A.Id_Origen_Destino)
         END
         ) as Destino,                  
        DATE(A.Fecha) As Fecha,A.Tipo,
        A.Codigo, A.Estado, PAI.Observaciones,
            A.Observacion_Anulacion,
        (SELECT CONCAT(Nombres, " ", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=A.Identificacion_Funcionario) as Funcionario, (SELECT CONCAT(Nombres, " ", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=A.Funcionario_Anula) as Funcionario_Anula, DATE(A.Fecha_Anulacion) as Fecha_Anulacion
        FROM Producto_Ajuste_Individual PAI 
        INNER JOIN Producto P ON PAI.Id_Producto=P.Id_Producto
        INNER JOIN Ajuste_Individual A ON PAI.Id_Ajuste_Individual=A.Id_Ajuste_Individual 
        '.$condiciones;

    

        $queryObj->SetQuery($query_ajuste);
        $ajuste = $queryObj->ExecuteQuery('multiple');
        return $ajuste;
    }
    
    function SepararFechas($fechas){
        $fechas_separadas = explode(" - ", $fechas);
        return $fechas_separadas;
    }

    function ArmarTablaResultados($resultados){

        $contenido_excel = '';

        $contenido_excel = '
        <table border=1>
        <tr>
            <td align="center"><strong>Codigo Ajuste</strong></td>
            <td align="center"><strong>Fecha</strong></td>
            <td align="center"><strong>Funcionario </strong></td>
            <td align="center"><strong>Nombre Comercial</strong></td>
            <td align="center"><strong>Producto</strong></td>
            <td align="center"><strong>Cum</strong></td>
            <td align="center"><strong>Tipo</strong></td>
            <td align="center"><strong>Observacion</strong></td>
            <td align="center"><strong>Origen</strong></td>
            <td align="center"><strong>Lote</strong></td>
            <td align="center"><strong>Fecha Vencimiento</strong></td>
            <td align="center"><strong>Cantidad</strong></td>
            <td align="center"><strong>Costo</strong></td>
        </tr>';
    
        if (count($resultados) > 0) {
            foreach ($resultados as $i => $r) {

                $contenido_excel .= '
                <tr>
                    <td>'.$r['Codigo'].'</td>
                    <td>'.$r['Fecha'].'</td>
                    <td>'.$r['Funcionario'].'</td>
                    <td>'.$r["Nombre_Comercial"].'</td>
                    <td>'.$r["Producto"].'</td>
                    <td>'.$r["Codigo_Cum"].'</td>
                    <td>'.$r["Tipo"].'</td>
                    <td>'.$r["Observaciones"].'</td>
                    <td>'.$r["Destino"].'</td>
                    <td>'.$r["Lote"].'</td>
                    <td>'.$r["Fecha_Vencimiento"].'</td>
                    <td>'.$r["Cantidad"].'</td>
                    <td>'.$r["Costo"].'</td>
                </tr>';
            } 
        }else{
    
            $contenido_excel .= '
            <tr>
                <td colspan="8" align="center">SIN RESULTADOS PARA MOSTRAR</td>
            </tr>';
        }        
           
    
        $contenido_excel .= '
        </table>';

        echo $contenido_excel;
    }

?>