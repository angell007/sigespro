<?php
 header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json'); 

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['rem'] ) ? $_REQUEST['rem'] : '' );
$id_producto_dis=$_REQUEST['prod'];
$condicion = SetCondiciones($id_producto_dis);

$query='SELECT PR.Cantidad as Cantidad, P.Nombre_Comercial, PR.Id_Producto,PR.Lote,R.Id_Destino,  PR.Id_Producto_Remision, PR.Id_Remision FROM Producto_Remision PR INNER JOIN Producto P ON PR.Id_Producto=P.Id_Producto INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision  WHERE PR.Id_Remision='.$id ;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos_remision = $oCon->getData();
unset($oCon);

$cantidad=0;
$j=0;
$texto=[];


foreach ($productos_remision as $key => $value) {
    $condicion = SetCondiciones($id_producto_dis);
    $query = '(SELECT (PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad,'.$value['Id_Producto_Remision'].' as Id_Producto_Remision, '.$value['Id_Remision'].' as Id_Remision,(SELECT CONCAT_WS(" ",P.Primer_Nombre,P.Segundo_Nombre,P.Primer_Apellido,P.Segundo_Apellido,"-",P.EPS) FROM Paciente P WHERE P.Id_Paciente =D.Numero_Documento) as Paciente, D.Numero_Documento as Id_Paciente,D.Fecha_Actual as Fecha, PD.Id_Producto_Dispensacion, PD.Id_Dispensacion, D.Codigo, PD.Id_Producto,"'.$value['Lote'].'" as Lote,D.Id_Punto_Dispensacion,PD.Cantidad_Entregada,PD.Entregar_Faltante, PD.Cantidad_Formulada,(PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Pendiente,PD.Fecha_Autorizacion,PD.Numero_Autorizacion,P.Nombre_Comercial, DATEDIFF( DM.Fecha_Maxima_Entrega,CURDATE()) AS Dias
    FROM Producto_Dispensacion PD 
    INNER JOIN (SELECT A.Id_Dispensacion FROM Auditoria A  WHERE (A.Estado="Aceptar" OR A.Estado="Auditado") AND A.Punto_Pre_Auditoria='.$value['Id_Destino'].' ) A ON PD.Id_Dispensacion=A.Id_Dispensacion
    INNER JOIN Dispensacion D ON A.Id_Dispensacion=D.Id_Dispensacion
    INNER JOIN Dispensacion_Mipres DM ON D.Id_Dispensacion_Mipres=DM.Id_Dispensacion_Mipres
    INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto      
    WHERE D.Id_Servicio!=7 AND (PD.Cantidad_Formulada>PD.Cantidad_Entregada) AND PD.Id_Producto='.$value['Id_Producto'].$condicion.' AND D.Estado_Dispensacion!="Anulada" AND D.Id_Dispensacion_Mipres!=0  ORDER BY Dias ASC  ) UNION (SELECT (PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad,'.$value['Id_Producto_Remision'].' as Id_Producto_Remision, '.$value['Id_Remision'].' as Id_Remision,(SELECT CONCAT_WS(" ",P.Primer_Nombre,P.Segundo_Nombre,P.Primer_Apellido,P.Segundo_Apellido,"-",P.EPS) FROM Paciente P WHERE P.Id_Paciente =D.Numero_Documento) as Paciente, D.Numero_Documento as Id_Paciente,D.Fecha_Actual as Fecha, PD.Id_Producto_Dispensacion, PD.Id_Dispensacion, D.Codigo, PD.Id_Producto,"'.$value['Lote'].'" as Lote,D.Id_Punto_Dispensacion,PD.Cantidad_Entregada,PD.Entregar_Faltante, PD.Cantidad_Formulada,(PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Pendiente,PD.Fecha_Autorizacion,PD.Numero_Autorizacion,P.Nombre_Comercial, "" as Dias
    FROM Producto_Dispensacion PD 
    INNER JOIN (SELECT A.Id_Dispensacion FROM Auditoria A  WHERE (A.Estado="Aceptar" OR A.Estado="Auditado") AND A.Punto_Pre_Auditoria='.$value['Id_Destino'].' ) A ON PD.Id_Dispensacion=A.Id_Dispensacion
    INNER JOIN Dispensacion D ON A.Id_Dispensacion=D.Id_Dispensacion     
    INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto      
    WHERE D.Id_Servicio!=7 AND (PD.Cantidad_Formulada>PD.Cantidad_Entregada) AND PD.Id_Producto='.$value['Id_Producto'].$condicion.' AND D.Estado_Dispensacion!="Anulada"   AND D.Id_Dispensacion_Mipres=0 ORDER BY PD.Id_Producto_Dispensacion ASC )';




    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $productos_dispensacion = $oCon->getData();
    unset($oCon);


    $cantidad=(INT)$value['Cantidad'];
    $k=-1;
    foreach ($productos_dispensacion as $i => $item) {$k++;
        if($cantidad>0){
            if((INT)$item['Cantidad']<=$cantidad){
                $temporal=[];
                $cantidad=$cantidad-(INT)$item['Cantidad'];
                $resultado[$j]=$item;
                $temporal['Id_Producto_Dispensacion']=$item['Id_Producto_Dispensacion'];
                $temporal['Id_Remision']=$item['Id_Remision'];
                $texto[$j]=(object)$temporal;
                $j++;
                $id_producto_dis.=$item['Id_Producto_Dispensacion'].',';
            }

        }else{         
            break;
        }

       

            
    }

    
    


}
$productos['Productos']=$resultado;
$productos['Id']=$texto;


echo json_encode($productos);

function SetCondiciones($req){
    $condicion = '';
    if ($req!='') {
       
            $condicion .= " AND  PD.Id_Producto_Dispensacion NOT IN (".trim($req,",").")";
        
    
    }
   
    return $condicion;
}



?>