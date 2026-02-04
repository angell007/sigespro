<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$fechaInicial    = ( isset( $_REQUEST['inicio'] ) ? $_REQUEST['inicio'] : '' );
$fechaFinal      = ( isset( $_REQUEST['final'] ) ? $_REQUEST['final'] : '' );
$idcontrato      = ( isset( $_REQUEST['idContrato'] ) ? $_REQUEST['idContrato'] : '' );
$clienteps      = ( isset( $_REQUEST['clienteps'] ) ? $_REQUEST['clienteps'] : '' );
$tipo            = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$tiempo          = ( isset( $_REQUEST['tiempo'] ) ? $_REQUEST['tiempo'] : '3' );
$tipoMedicamento = (isset($_REQUEST['tipo_medicamento']) ? $_REQUEST['tipo_medicamento'] : '');
$excluirVencimientos = (isset($_REQUEST['excluir_vencimientos']) ? $_REQUEST['excluir_vencimientos'] : '');
$grupo1 = ''; $grupo2 = ''; $grupo3 = ''; $grupo4 = '';

$date1 = new DateTime($fechaInicial);
$date2 = new DateTime($fechaFinal);
$diff = $date1->diff($date2);
$mes = round($diff->days/30);

if($tiempo<=$mes){
   $difer = $mes/$tiempo; 
   if($difer<1){
     $difer=1;
   }
   $diff ='/'.(INT)$difer;
}else{
  $difer = $mes*$tiempo; 
  if($difer<1){
    $difer=1;
  }
  $diff ='*'.(INT)$difer;
}

$codMedicamento  = '';
if( $tipoMedicamento && $tipoMedicamento != 'Todos' ){
    $codMedicamento = ' AND P.Tipo_Pos = "'.$_REQUEST['tipo_medicamento'].'" ';
}

if (existePrecompra()) {
    $resultado['type'] = 'error';
    $resultado['text'] = 'Existen precompras pendientes con esas características';
    $resultado['title'] = 'No se puede generar el rotativo';
    echo json_encode($resultado);
    return false;
}

$oItem = new complex('Configuracion','Id_Configuracion',1);
$nc = $oItem->getData();
unset($oItem);

switch($tipo){

case "Contrato":{

    llenarGruposContrato();

    $query = 'SELECT RES.ATC, 
                     RES.Nombre_Contrato,
                     RES.Cantidad, 
                     RES.Unidad_Medida, 
                     RES.Concentracion, 
                     RES.Presentacion, 
                     RES.Descripcion, 
                     RES.CUM, 
                     RES.Id_Producto,
                     RES.CantidadContrato, 
                     RES.Producto, 
                     SUM(RES.Consumida) AS Consumida, 
                     SUM(RES.Promedio_Tiempo) as Promedio_Tiempo, 
                     RES.Cantidad_Presentacion, "true" AS Desabilitado
             FROM ( '.$grupo4.') RES    
             GROUP BY RES.CUM';  
    
    break;

}
case "Nutriciones":{

      llenarGruposNutriciones();
    $query = 'SELECT RES.ATC, RES.Cantidad, RES.Unidad_Medida, RES.Concentracion, RES.Presentacion, RES.Descripcion, RES.CUM, RES.Id_Producto, RES.Producto, SUM(RES.Consumida) AS Consumida, SUM(RES.Promedio_Tiempo) as Promedio_Tiempo, RES.Cantidad_Presentacion, "true" AS Desabilitado
            FROM 
            (';
        if($tipoMedicamento == 'Todos'){
            $query.= $grupo1 .'
                    UNION ALL  
                    '.$grupo2.'
                    UNION ALL  
                    '.$grupo3;   
        }
       
        if($tipoMedicamento == 'Clientes'){
            $query.= $grupo1;
        }else if($tipoMedicamento == 'Pos'){
            $query.= $grupo2;
        }else if($tipoMedicamento == 'No Pos'){
            $query.= $grupo3;
        }
  
            
    $query.=' 
            ) RES
            GROUP BY  RES.Id_Producto
            ORDER BY RES.ATC ASC '; 
            
    break;
    
    
    
    
}
case "Materiales":{

      llenarGruposMateriales();
    $query = 'SELECT RES.ATC, RES.Id_Grupo_Materiales, RES.Cantidad, RES.Unidad_Medida, RES.Concentracion, RES.Presentacion, RES.Descripcion, RES.CUM, RES.Id_Producto, RES.Producto, SUM(RES.Consumida) AS Consumida, SUM(RES.Promedio_Tiempo) as Promedio_Tiempo, RES.Cantidad_Presentacion, "true" AS Desabilitado
            FROM 
            (';
        if($tipoMedicamento == 'Todos'){
            $query.= $grupo1 .'
                    UNION ALL  
                    '.$grupo2.'
                    UNION ALL  
                    '.$grupo3;   
        }
       
        if($tipoMedicamento == 'Clientes'){
            $query.= $grupo1;
        }else if($tipoMedicamento == 'Pos'){
            $query.= $grupo2;
        }else if($tipoMedicamento == 'No Pos'){
            $query.= $grupo3;
        }
  
            
    $query.=' 
            ) RES
            GROUP BY  RES.Id_Grupo_Materiales
            ORDER BY RES.Id_Grupo_Materiales ASC '; 

    break;

}
case "Medicamentos":{ 
  
    llenarGrupos();
    $query = 'SELECT 
                     RES.ATC, 
                     RES.Cantidad, 
                     RES.Unidad_Medida, 
                     RES.Concentracion, 
                     RES.Presentacion, 
                     RES.Descripcion, 
                     RES.CUM, 
                     RES.Id_Producto, 
                     RES.Producto, 
                     SUM(RES.Consumida) AS Consumida, 
                     SUM(RES.Promedio_Tiempo) as Promedio_Tiempo, 
                     RES.Cantidad_Presentacion, "true" AS Desabilitado
              FROM 
            (';
        if($tipoMedicamento == 'Todos'){
            $query.= $grupo1 .'
                    UNION ALL  
                    '.$grupo2.'
                    UNION ALL  
                    '.$grupo3;   
        }
       
        if($tipoMedicamento == 'Clientes'){
            $query.= $grupo1;
        }else if($tipoMedicamento == 'Pos'){
            $query.= $grupo2;
        }else if($tipoMedicamento == 'No Pos'){
            $query.= $grupo3;
        }
  
            
    $query.=' 
            ) RES
            #ORDER BY P.Descripcion_ATC ASC, P.Presentacion ASC
            GROUP BY  RES.ATC, RES.Cantidad, RES.Unidad_Medida, RES.Concentracion, RES.Presentacion
            ORDER BY RES.ATC ASC '; 
    break;
}

}

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);
$i=-1;

foreach ($resultado as $value) 
{
    $i++;
    $excluir = '';
    $condicionInv = ' WHERE I.Id_Producto IN ('.$value['Id_Producto'].') AND I.Id_Estiba!=0';
    $condicionContrato = ' WHERE IC.Id_Producto_Contrato IN ('.$value['Id_Producto'].') AND I.Id_Estiba!=0';

    if ($excluirVencimientos == 'Si') {
        $excluir = ' INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
                     INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = E.Id_Grupo_Estiba 
                    ';
        $condicionInv .= ' AND G.Nombre NOT LIKE "%VENCIMIENTO%" ';
    }
    
    if ($idcontrato != '') {
        $query ='SELECT SUM(IC.Cantidad-(IC.Cantidad_Apartada+IC.Cantidad_Seleccionada)) as CantidadActual
                   FROM Inventario_Contrato IC 
                   INNER JOIN Inventario_Nuevo I ON I.Id_Inventario_Nuevo = IC.Id_Inventario_Nuevo
                   '.$excluir.' '.$condicionInv;
    }else{
        $query ='SELECT SUM(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada) - IFNULL( (IC.Cantidad),"0") ) as CantidadActual
                    FROM Inventario_Nuevo I
                    LEFT JOIN Inventario_Contrato IC ON I.Id_Inventario_Nuevo = IC.Id_Inventario_Nuevo
                    '.$excluir.' '.$condicionInv;
    }
 
    $oCon= new consulta();
    $oCon->setQuery($query);
    $Cantidad = $oCon->getData();
    unset($oCon);
    $query = 'SELECT P.* 
                FROM Producto_Orden_Compra_Nacional P
                INNER JOIN Orden_Compra_Nacional OC ON OC.Id_Orden_Compra_Nacional = P.Id_Orden_Compra_Nacional
                WHERE P.Id_Producto IN('.$value['Id_Producto'].') AND 
                 OC.Estado != "Anulada" && OC.Estado != "Recibida"  AND 
                 DATE(OC.Fecha_Creacion_Compra) >= DATE_SUB(NOW(),INTERVAL 15 DAY)';
                 $oCon= new consulta();
                 $oCon->setQuery($query);
                 $existenciaCompra = $oCon->getData();
                 unset($oCon);
                 //lo que hay en inventario
                 $cantidades =  (INT)$Cantidad['CantidadActual'] ;
                 
                 //existencia orden de compra
                 $resultado[$i]['CantidadCompras'] =  (INT)$existenciaCompra['Cantidad'] ;
                 
                 if($existenciaCompra){
                     //todas las existencias
                     $cantidades += (INT)$existenciaCompra['Cantidad'] ;
                    }
                    //promedio tiempo es cantidades del contrato / meses contrato
                    if(($value['Promedio_Tiempo'])<=(INT)$cantidades){    
                        unset($resultado[$i]);
                    }else{
                        $value['Promedio_Tiempo'] - (INT)$existenciaCompra['Cantidad'];
                        $resultado[$i]['CantidadActual']=(INT)$Cantidad['CantidadActual'];
                    }
}
                
                
$resultado=array_values($resultado);

$mensaje[] = array('Mensaje' => 'No se encuentra alguna compra relacionada con estos productos' , 'DivMensaje' => true , "DivEncabezado" => false);

$i=-1;
foreach($resultado as $result)
{
    $i++;
    if($result['CantidadActual']<0){
        $resultado[$i]['CantidadActual']=0;
    }
    
    $modulo=($result['Promedio_Tiempo']-$resultado[$i]['CantidadActual'])%$result['Cantidad_Presentacion'];
    $temporal=$result['Cantidad_Presentacion']/2; //

    if($modulo>$temporal){
        $cantidad_final=$result['Cantidad_Presentacion']-$modulo;
        $resultado[$i]['CantidadTotal'] = ($result['Promedio_Tiempo']-$resultado[$i]['CantidadActual'])+$cantidad_final;
        
    }else{
        $resultado[$i]['CantidadTotal'] = ($result['Promedio_Tiempo']-$resultado[$i]['CantidadActual'])-$modulo;
       
    }
    
     $resultado[$i]['CantidadTotal'] -= $resultado[$i]['CantidadCompras'] ;
       
     $queryP = 'SELECT  A.Id_Proveedor
                    FROM Acta_Recepcion A                  
                    WHERE A.Id_Acta_Recepcion IN 
                    (
                    SELECT MAX(PA.Id_Acta_Recepcion)
                    FROM Producto_Acta_Recepcion PA
                    INNER JOIN Acta_Recepcion A ON A.Id_Acta_Recepcion = PA.Id_Acta_Recepcion
                    WHERE PA.Id_Producto IN ('.$result['Id_Producto'].')
                    GROUP BY  A.Id_Proveedor
                    )
                    ORDER BY A.Id_Acta_Recepcion DESC 
                    LIMIT 0,4';
    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($queryP);
    $proveedores = $oCon->getData();
    unset($oCon);
    
    $resultado1 = [];
    foreach( $proveedores as $proveedor){
        $query1 = 'SELECT P.Id_Proveedor as Id_Proveedor,
                          P.Nombre as NombreProveedor, 
                          SUM(PAR.Cantidad) as Cantidad , 
                          PAR.Precio as Total, 
                          AR.Codigo as Codigo , 
                          AR.Fecha_Creacion as Fecha, 
            IFNULL(CONCAT( Pr.Principio_Activo, " ", Pr.Presentacion, " ", Pr.Concentracion, " (", Pr.Nombre_Comercial,") ", Pr.Cantidad," ", Pr.Unidad_Medida, " " ), Pr.Nombre_Comercial) as nombre,
            Pr.Cantidad_Minima as Cantidad_Minima,
            Pr.Cantidad_Maxima as Cantidad_Maxima,
            false as "DivMensaje",
            true as "DivEncabezado", Pr.Id_Producto
            FROM Acta_Recepcion AR 
            INNER JOIN Producto_Acta_Recepcion PAR ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion 
            INNER JOIN Proveedor P ON P.Id_Proveedor = AR.Id_Proveedor 
            INNER JOIN Producto Pr ON PAR.Id_Producto = Pr.Id_Producto 
       WHERE PAR.Id_Producto IN ('.$result['Id_Producto'].') AND AR.Id_Proveedor = '.$proveedor['Id_Proveedor'].'
       GROUP BY AR.Id_Acta_Recepcion 
       ORDER BY AR.Fecha_Creacion DESC
       LIMIT 1';

        $oCon= new consulta();
        $oCon->setQuery($query1);
        $res = $oCon->getData();    
            
        if($res){
            array_push($resultado1,$res);
        }
    }
    
    unset($oCon);
    if($resultado1 == null || count($resultado1) == 0 ){
        $resultado[$i]['Compras'] = $mensaje;
    }else{
        $resultado[$i]['Compras'] = $resultado1; //resultado query
    }
    if($resultado[$i]['CantidadTotal']==0){
        unset($resultado[$i]);
    }
  
}
$resultado = array_values($resultado);
echo json_encode($resultado);

 function ordenar($n) {
    return $n;
} 

function llenarGruposContrato(){
    global $grupo4,$idcontrato, $mes, $diff;

    $grupo4 = 'SELECT  P.ATC as ATC, Con.Nombre_Contrato as Nombre_Contrato,
                       P.Cantidad, P.Unidad_Medida, 
                       P.Concentracion, P.Presentacion, 
                       P.Descripcion_ATC as Descripcion , 
		               GROUP_CONCAT(DISTINCT(P.Codigo_Cum)) AS CUM, 
                       GROUP_CONCAT(DISTINCT(P.Id_Producto)) AS Id_Producto, 
                       CONCAT_WS(" ",P.Nombre_Comercial, "(",P.Principio_Activo,P.Presentacion,P.Concentracion,")", 
   	  				   P.Cantidad, P.Unidad_Medida, ". ","LAB - ",P.Laboratorio_Generico ) AS Producto, 
	            ROUND(SUM(PC.Cantidad)'.$diff.') AS Promedio_Tiempo, P.Cantidad_Presentacion, ROUND(SUM(PC.Cantidad)) AS CantidadContrato,
                ( 
	  	          SELECT SUM(PR.Cantidad) FROM  Remision R  
		          INNER JOIN Producto_Remision PR ON P.Id_Producto = PR.Id_Producto AND PR.Id_Remision = R.Id_Remision
 		          WHERE R.Id_Contrato = PC.Id_Contrato  AND R.Estado!="Anulada"
                ) AS Consumida
                FROM Producto_Contrato PC
                 INNER JOIN Contrato Con ON PC.Id_Contrato = Con.Id_Contrato
	             INNER JOIN Producto P ON P.Codigo_Cum = PC.Cum 
                 WHERE PC.Id_Contrato = '.$idcontrato.' GROUP BY PC.Cum';

}

function llenarGrupos(){
    global $grupo1,$grupo2,$grupo3,$diff,$fechaInicial,$fechaFinal,$clienteps ;
    
    #CLIENTES
    $grupo1 = 'SELECT P.ATC as ATC, 
                        P.Cantidad, 
                        P.Unidad_Medida, 
                        P.Concentracion, 
                        P.Presentacion, 
                        P.Descripcion_ATC as Descripcion , 
                        GROUP_CONCAT(DISTINCT(P.Codigo_Cum)) AS CUM, 
                        GROUP_CONCAT(DISTINCT(P.Id_Producto)) AS Id_Producto, 
    IF(PR.Nombre_Producto="" OR PR.Nombre_Producto IS NULL, CONCAT_WS(" ",P.Nombre_Comercial, "(",P.Principio_Activo,P.Presentacion,P.Concentracion,") ", 
       P.Cantidad, P.Unidad_Medida, ". ","LAB - ",P.Laboratorio_Generico ),PR.Nombre_Producto) AS Producto,
    SUM(PR.Cantidad) AS Consumida, ROUND(SUM(PR.Cantidad)'.$diff.') AS Promedio_Tiempo, P.Cantidad_Presentacion 

    FROM Producto_Remision PR
    INNER JOIN Remision R ON R.Id_Remision = PR.Id_Remision 
    INNER JOIN Producto P ON P.Id_Producto = PR.Id_Producto 
    INNER JOIN Cliente C ON C.Id_Cliente = R.Id_Destino
    WHERE DATE(R.Fecha) BETWEEN "'.$fechaInicial.'" AND "'.$fechaFinal.'"
    AND P.Tipo = "Medicamento" AND P.ATC IS NOT NULL AND LENGTH(P.ATC) > 2 AND R.Estado!="Anulada" AND R.Tipo_Origen="Bodega" AND R.Tipo_Destino="Cliente"
    AND C.Estado="Activo"
    GROUP BY  P.ATC, P.Cantidad, P.Unidad_Medida, P.Concentracion, P.Presentacion
    #ORDER BY P.Descripcion_ATC ASC, P.Presentacion ASC';

    #POS
    $grupo2 = ' SELECT 
                       P.ATC as ATC, 
                       P.Cantidad, 
                       P.Unidad_Medida, 
                       P.Concentracion, 
                       P.Presentacion, 
                        P.Descripcion_ATC as Descripcion , 
                       GROUP_CONCAT(DISTINCT(P.  Codigo_Cum)) AS CUM, 
                       GROUP_CONCAT(DISTINCT(P.Id_Producto)) AS Id_Producto, 
                       CONCAT_WS(" ",P.Nombre_Comercial, "(",P.Principio_Activo,P.Presentacion,P.Concentracion,") ", 
                       P.Cantidad, P.Unidad_Medida, ". ","LAB - ",P.Laboratorio_Generico ) AS Producto,
                       SUM(PD.Cantidad_Formulada) AS Consumida, 
            
                IF((( IFNULL( INV.Inventario,0) + IFNULL(RE.Rems,0)) -  ROUND(SUM(PD.Cantidad_Formulada)'.$diff.')) > 0, 0, (
                    ( IFNULL(INV.Inventario,0) + IFNULL(RE.Rems,0)) - ROUND(SUM(PD.Cantidad_Formulada)'.$diff.')
                ) * -1
            ) AS Promedio_Tiempo,
            
            P.Cantidad_Presentacion
        
            FROM Producto_Dispensacion PD
            INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
            INNER JOIN Producto P ON P.Id_Producto = PD.Id_Producto 
            INNER JOIN Punto_Dispensacion PU ON PU.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
            
            LEFT JOIN(
                SELECT IFNULL(SUM(IX.Cantidad),0) AS Inventario, IX.Id_Producto, ES.Id_Punto_Dispensacion
                    FROM Inventario_Nuevo IX
                    INNER JOIN Estiba ES ON ES.Id_Estiba = IX.Id_Estiba
                    GROUP BY ES.Id_Punto_Dispensacion, IX.Id_Producto
            ) INV ON INV.Id_Punto_Dispensacion = PU.Id_Punto_Dispensacion AND INV.Id_Producto = PD.Id_Producto
            
            LEFT JOIN(
                SELECT  IFNULL(SUM(PR.Cantidad),0 )AS Rems,  PR.Id_Producto, R.Id_Destino AS Id_Punto_Dispensacion
                FROM Remision R INNER JOIN Producto_Remision PR ON PR.Id_Remision = R.Id_Remision
                WHERE R.Tipo_Destino = "Punto_Dispensacion" AND R.Estado != "Recibida" AND R.Estado != "Anulada"
                GROUP BY R.Id_Destino, PR.Id_Producto
            ) RE ON RE.Id_Punto_Dispensacion = PU.Id_Punto_Dispensacion AND RE.Id_Producto = PD.Id_Producto        
            
            WHERE DATE(D.Fecha_Actual) BETWEEN "'.$fechaInicial.'" AND "'.$fechaFinal.'"
            AND P.Tipo = "Medicamento"  AND P.ATC IS NOT NULL AND LENGTH(P.ATC) > 2
            AND D.Estado_Dispensacion!="Anulada"
            AND P.Tipo_Pos = "Pos"
            AND PU.Tipo_Dispensacion="Entrega"
            AND D.EPS = "'.$clienteps.'"
            GROUP BY  P.ATC, P.Cantidad, P.Unidad_Medida, P.Concentracion, P.Presentacion';
    
    #NO POS
    $grupo3 =' SELECT P.ATC as ATC, P.Cantidad, P.Unidad_Medida, P.Concentracion, P.Presentacion, P.Descripcion_ATC as Descripcion , 
                GROUP_CONCAT(DISTINCT(P.   Codigo_Cum)) AS CUM, GROUP_CONCAT(DISTINCT(P.Id_Producto)) AS Id_Producto, 
                CONCAT_WS(" ",P.Nombre_Comercial, "(",P.Principio_Activo,P.Presentacion,P.Concentracion,") ", P.Cantidad, P.Unidad_Medida, ". ","LAB - ",P.Laboratorio_Generico ) AS Producto,
                SUM((PD.Cantidad_Formulada - PD.Cantidad_Entregada )) AS Consumida,
                    IF(
                    (
                        (IFNULL(INV.Inventario,0) + IFNULL(RE.Rems,0)) - ROUND(SUM((PD.Cantidad_Formulada - PD.Cantidad_Entregada ) ) )
                    )> 0,
                    0,
                    (
                    (IFNULL(INV.Inventario,0) + IFNULL(RE.Rems,0)) -  ROUND(SUM((PD.Cantidad_Formulada - PD.Cantidad_Entregada ) ) )
                    ) * -1
                ) AS Promedio_Tiempo,
            
                P.Cantidad_Presentacion

            FROM Producto_Dispensacion PD
            INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
            INNER JOIN Producto P ON P.Id_Producto = PD.Id_Producto 
            INNER JOIN Punto_Dispensacion PU ON PU.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
            LEFT JOIN(
                SELECT IFNULL(SUM(IX.Cantidad),0) AS Inventario, IX.Id_Producto, ES.Id_Punto_Dispensacion
                    FROM Inventario_Nuevo IX
                    INNER JOIN Estiba ES ON ES.Id_Estiba = IX.Id_Estiba
                    GROUP BY ES.Id_Punto_Dispensacion, IX.Id_Producto
            ) INV ON INV.Id_Punto_Dispensacion = PU.Id_Punto_Dispensacion AND INV.Id_Producto = PD.Id_Producto

            LEFT JOIN(
                SELECT  IFNULL(SUM(PR.Cantidad),0 )AS Rems,  PR.Id_Producto, R.Id_Destino AS Id_Punto_Dispensacion
                FROM Remision R INNER JOIN Producto_Remision PR ON PR.Id_Remision = R.Id_Remision
                WHERE R.Tipo_Destino = "Punto_Dispensacion"AND R.Estado != "Recibida" AND R.Estado != "Anulada"
                GROUP BY R.Id_Destino, PR.Id_Producto
            ) RE ON RE.Id_Punto_Dispensacion = PU.Id_Punto_Dispensacion AND RE.Id_Producto = PD.Id_Producto 
            
            
            WHERE DATE(D.Fecha_Actual) BETWEEN "'.$fechaInicial.'" AND "'.$fechaFinal.'"
            AND P.Tipo = "Medicamento"  AND P.ATC IS NOT NULL AND LENGTH(P.ATC) > 2

            AND D.Estado_Dispensacion!="Anulada"
            AND P.Tipo_Pos = "No Pos"
            AND PU.Tipo_Dispensacion="Entrega"
            AND D.EPS = "'.$clienteps.'"
            GROUP BY  P.ATC, P.Cantidad, P.Unidad_Medida, P.Concentracion, P.Presentacion';

    
   
    
}

function llenarGruposMateriales(){
    global $grupo1,$grupo2,$grupo3,$diff,$fechaInicial,$fechaFinal;
    
    #CLIENTES
    $grupo1 = ' SELECT  GM.Nombre as ATC, GM.Id_Grupo_Materiales, P.Cantidad, P.Unidad_Medida, P.Concentracion, P.Presentacion, P.Descripcion_ATC as Descripcion , GROUP_CONCAT(DISTINCT(P.Codigo_Cum)) AS CUM, GROUP_CONCAT(DISTINCT(P.Id_Producto)) AS Id_Producto, 
        P.Nombre_Comercial AS Producto,
    SUM(PR.Cantidad) AS Consumida, ROUND(SUM(PR.Cantidad)'.$diff.') AS Promedio_Tiempo, P.Cantidad_Presentacion 

    FROM Producto_Remision PR
    INNER JOIN Remision R ON R.Id_Remision = PR.Id_Remision 
    INNER JOIN Producto P ON P.Id_Producto = PR.Id_Producto 
    INNER JOIN Cliente C ON C.Id_Cliente = R.Id_Destino
    INNER JOIN Producto_Grupo_Materiales PG ON PG.Id_Producto = P.Id_Producto
    INNER JOIN Grupo_Materiales GM ON GM.Id_Grupo_Materiales = PG.Id_Grupo_Materiales
    WHERE DATE(R.Fecha) BETWEEN "'.$fechaInicial.'" AND "'.$fechaFinal.'"
    AND P.Tipo = "Material" AND R.Estado!="Anulada" AND R.Tipo_Origen="Bodega" AND R.Tipo_Destino="Cliente"
    AND C.Estado="Activo"
    GROUP BY PG.Id_Grupo_Materiales' ;

    #POS
    $grupo2 = ' SELECT GM.Nombre as ATC,  GM.Id_Grupo_Materiales, P.Cantidad, P.Unidad_Medida, P.Concentracion, P.Presentacion, P.Descripcion_ATC as Descripcion , GROUP_CONCAT(DISTINCT(P.Codigo_Cum)) AS CUM, GROUP_CONCAT(DISTINCT(P.Id_Producto)) AS Id_Producto, 
            CONCAT_WS(" ",P.Nombre_Comercial, "(",P.Principio_Activo,P.Presentacion,P.Concentracion,") ", P.Cantidad, P.Unidad_Medida, ". ","LAB - ",P.Laboratorio_Generico ) AS Producto,
            SUM(PD.Cantidad_Formulada) AS Consumida, 
            
            #ROUND(SUM(PD.Cantidad_Formulada)'.$diff.') AS Promedio_Tiempo,
            
             IF(
               (
                   ( IFNULL( INV.Inventario,0) + IFNULL(RE.Rems,0)) -  ROUND(SUM(PD.Cantidad_Formulada)'.$diff.')
                ) > 0,
                0,
                (
                    ( IFNULL(INV.Inventario,0) + IFNULL(RE.Rems,0)) - ROUND(SUM(PD.Cantidad_Formulada)'.$diff.')
                ) * -1
            ) AS Promedio_Tiempo,
            
            
            P.Cantidad_Presentacion
        
    FROM Producto_Dispensacion PD
    INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
    INNER JOIN Producto P ON P.Id_Producto = PD.Id_Producto 
    INNER JOIN Punto_Dispensacion PU ON PU.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
    INNER JOIN Producto_Grupo_Materiales PG ON PG.Id_Producto = P.Id_Producto
    INNER JOIN Grupo_Materiales GM ON GM.Id_Grupo_Materiales = PG.Id_Grupo_Materiales
    
     /* LEFT JOIN(
        SELECT IFNULL(SUM(Cantidad),0) AS Inventario, Id_Producto, Id_Punto_Dispensacion
            FROM Inventario_Nuevo
            GROUP BY Id_Punto_Dispensacion, Id_Producto
    ) INV ON INV.Id_Punto_Dispensacion = PU.Id_Punto_Dispensacion AND INV.Id_Producto = PD.Id_Producto */

    LEFT JOIN(
        SELECT IFNULL(SUM(IX.Cantidad),0) AS Inventario, IX.Id_Producto, ES.Id_Punto_Dispensacion
            FROM Inventario_Nuevo IX
            INNER JOIN Estiba ES ON ES.Id_Estiba = IX.Id_Estiba
            GROUP BY ES.Id_Punto_Dispensacion, IX.Id_Producto
    ) INV ON INV.Id_Punto_Dispensacion = PU.Id_Punto_Dispensacion AND INV.Id_Producto = PD.Id_Producto
    
    LEFT JOIN(
        SELECT  IFNULL(SUM(PR.Cantidad),0 )AS Rems,  PR.Id_Producto, R.Id_Destino AS Id_Punto_Dispensacion
        FROM Remision R INNER JOIN Producto_Remision PR ON PR.Id_Remision = R.Id_Remision
        WHERE R.Tipo_Destino = "Punto_Dispensacion" AND R.Estado != "Recibida" AND R.Estado != "Anulada"
        GROUP BY R.Id_Destino, PR.Id_Producto
    ) RE ON RE.Id_Punto_Dispensacion = PU.Id_Punto_Dispensacion AND RE.Id_Producto = PD.Id_Producto        
    
    WHERE DATE(D.Fecha_Actual) BETWEEN "'.$fechaInicial.'" AND "'.$fechaFinal.'"
    AND P.Tipo = "Material"  
    AND D.Estado_Dispensacion!="Anulada"
    AND P.Tipo_Pos = "Pos"
    AND PU.Tipo_Dispensacion="Entrega"
    GROUP BY PG.Id_Grupo_Materiales';

    #NO POS
    $grupo3 =' SELECT GM.Nombre as ATC, GM.Id_Grupo_Materiales, P.Cantidad, P.Unidad_Medida, P.Concentracion, P.Presentacion, P.Descripcion_ATC as Descripcion , GROUP_CONCAT(DISTINCT(P.Codigo_Cum)) AS CUM, GROUP_CONCAT(DISTINCT(P.Id_Producto)) AS Id_Producto, 
        CONCAT_WS(" ",P.Nombre_Comercial, "(",P.Principio_Activo,P.Presentacion,P.Concentracion,") ", P.Cantidad, P.Unidad_Medida, ". ","LAB - ",P.Laboratorio_Generico ) AS Producto,
        SUM((PD.Cantidad_Formulada - PD.Cantidad_Entregada )) AS Consumida,
             IF(
            (
                (IFNULL(INV.Inventario,0) + IFNULL(RE.Rems,0)) - ROUND(SUM((PD.Cantidad_Formulada - PD.Cantidad_Entregada ) ) )
            )> 0,
             0,
            (
               (IFNULL(INV.Inventario,0) + IFNULL(RE.Rems,0)) -  ROUND(SUM((PD.Cantidad_Formulada - PD.Cantidad_Entregada ) ) )
            ) * -1
        ) AS Promedio_Tiempo,
        
        P.Cantidad_Presentacion

    FROM Producto_Dispensacion PD
    INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
    INNER JOIN Producto P ON P.Id_Producto = PD.Id_Producto 
    INNER JOIN Punto_Dispensacion PU ON PU.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
    INNER JOIN Producto_Grupo_Materiales PG ON PG.Id_Producto = P.Id_Producto
    INNER JOIN Grupo_Materiales GM ON GM.Id_Grupo_Materiales = PG.Id_Grupo_Materiales
    
    /* LEFT JOIN(
        SELECT IFNULL(SUM(Cantidad),0) AS Inventario, Id_Producto, Id_Punto_Dispensacion
            FROM Inventario_Nuevo
            GROUP BY Id_Punto_Dispensacion, Id_Producto
    ) INV ON INV.Id_Punto_Dispensacion = PU.Id_Punto_Dispensacion AND INV.Id_Producto = PD.Id_Producto */
    LEFT JOIN(
        SELECT IFNULL(SUM(IX.Cantidad),0) AS Inventario, IX.Id_Producto, ES.Id_Punto_Dispensacion
            FROM Inventario_Nuevo IX
            INNER JOIN Estiba ES ON ES.Id_Estiba = IX.Id_Estiba
            GROUP BY ES.Id_Punto_Dispensacion, IX.Id_Producto
    ) INV ON INV.Id_Punto_Dispensacion = PU.Id_Punto_Dispensacion AND INV.Id_Producto = PD.Id_Producto
    
    LEFT JOIN(
        SELECT  IFNULL(SUM(PR.Cantidad),0 )AS Rems,  PR.Id_Producto, R.Id_Destino AS Id_Punto_Dispensacion
        FROM Remision R INNER JOIN Producto_Remision PR ON PR.Id_Remision = R.Id_Remision
        WHERE R.Tipo_Destino = "Punto_Dispensacion" AND R.Estado != "Recibida"  AND R.Estado != "Anulada"
        GROUP BY R.Id_Destino, PR.Id_Producto
    ) RE ON RE.Id_Punto_Dispensacion = PU.Id_Punto_Dispensacion AND RE.Id_Producto = PD.Id_Producto 
    
    WHERE DATE(D.Fecha_Actual) BETWEEN "'.$fechaInicial.'" AND "'.$fechaFinal.'"
    AND P.Tipo = "Material" 

    AND D.Estado_Dispensacion!="Anulada"
    AND P.Tipo_Pos = "No Pos"
    AND PU.Tipo_Dispensacion="Entrega"
    GROUP BY PG.Id_Grupo_Materiales';

    
}

function llenarGruposNutriciones(){
    global $grupo1,$grupo2,$grupo3,$diff,$fechaInicial,$fechaFinal;
    
    #CLIENTES
    $grupo1 = ' SELECT  P.Nombre_Comercial as ATC, P.Cantidad, P.Unidad_Medida, P.Concentracion, P.Presentacion, P.Descripcion_ATC as Descripcion , GROUP_CONCAT(DISTINCT(P.Codigo_Cum)) AS CUM, GROUP_CONCAT(DISTINCT(P.Id_Producto)) AS Id_Producto, 
        P.Nombre_Comercial AS Producto,
    SUM(PR.Cantidad) AS Consumida, ROUND(SUM(PR.Cantidad)'.$diff.') AS Promedio_Tiempo, P.Cantidad_Presentacion 

    FROM Producto_Remision PR
    INNER JOIN Remision R ON R.Id_Remision = PR.Id_Remision 
    INNER JOIN Producto P ON P.Id_Producto = PR.Id_Producto 
    INNER JOIN Cliente C ON C.Id_Cliente = R.Id_Destino
    
    WHERE DATE(R.Fecha) BETWEEN "'.$fechaInicial.'" AND "'.$fechaFinal.'"
    AND P.Tipo = "nutriciones" AND R.Estado!="Anulada" AND R.Tipo_Origen="Bodega" AND R.Tipo_Destino="Cliente"
    AND C.Estado="Activo"
    GROUP BY P.Id_Producto' ;

    #POS
    $grupo2 = ' SELECT P.Nombre_Comercial as ATC,   P.Cantidad, P.Unidad_Medida, P.Concentracion, P.Presentacion, P.Descripcion_ATC as Descripcion , GROUP_CONCAT(DISTINCT(P.Codigo_Cum)) AS CUM, GROUP_CONCAT(DISTINCT(P.Id_Producto)) AS Id_Producto, 
            CONCAT_WS(" ",P.Nombre_Comercial, "(",P.Principio_Activo,P.Presentacion,P.Concentracion,") ", P.Cantidad, P.Unidad_Medida, ". ","LAB - ",P.Laboratorio_Generico ) AS Producto,
            SUM(PD.Cantidad_Formulada) AS Consumida, 
            
             IF(
               (
                   ( IFNULL( INV.Inventario,0) + IFNULL(RE.Rems,0)) -  ROUND(SUM(PD.Cantidad_Formulada)'.$diff.')
                ) > 0,
                0,
                (
                    ( IFNULL(INV.Inventario,0) + IFNULL(RE.Rems,0)) - ROUND(SUM(PD.Cantidad_Formulada)'.$diff.')
                ) * -1
            ) AS Promedio_Tiempo,
            
            P.Cantidad_Presentacion
        
    FROM Producto_Dispensacion PD
    INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
    INNER JOIN Producto P ON P.Id_Producto = PD.Id_Producto 
    INNER JOIN Punto_Dispensacion PU ON PU.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
   
    /*  LEFT JOIN(
        SELECT IFNULL(SUM(Cantidad),0) AS Inventario, Id_Producto, Id_Punto_Dispensacion
            FROM Inventario_Nuevo
            GROUP BY Id_Punto_Dispensacion, Id_Producto
    ) INV ON INV.Id_Punto_Dispensacion = PU.Id_Punto_Dispensacion AND INV.Id_Producto = PD.Id_Producto */

    LEFT JOIN(
        SELECT IFNULL(SUM(IX.Cantidad),0) AS Inventario, IX.Id_Producto, ES.Id_Punto_Dispensacion
            FROM Inventario_Nuevo IX
            INNER JOIN Estiba ES ON ES.Id_Estiba = IX.Id_Estiba
            GROUP BY ES.Id_Punto_Dispensacion, IX.Id_Producto
    ) INV ON INV.Id_Punto_Dispensacion = PU.Id_Punto_Dispensacion AND INV.Id_Producto = PD.Id_Producto
    
    LEFT JOIN(
        SELECT  IFNULL(SUM(PR.Cantidad),0 )AS Rems,  PR.Id_Producto, R.Id_Destino AS Id_Punto_Dispensacion
        FROM Remision R INNER JOIN Producto_Remision PR ON PR.Id_Remision = R.Id_Remision
        WHERE R.Tipo_Destino = "Punto_Dispensacion"AND R.Estado != "Recibida" AND R.Estado != "Anulada"
        GROUP BY R.Id_Destino, PR.Id_Producto
    ) RE ON RE.Id_Punto_Dispensacion = PU.Id_Punto_Dispensacion AND RE.Id_Producto = PD.Id_Producto        
   
    WHERE DATE(D.Fecha_Actual) BETWEEN "'.$fechaInicial.'" AND "'.$fechaFinal.'"
    AND P.Tipo = "nutriciones"  
    AND D.Estado_Dispensacion!="Anulada"
    AND P.Tipo_Pos = "Pos"
    AND PU.Tipo_Dispensacion="Entrega"
    GROUP BY P.Id_Producto';

    #NO POS
    $grupo3 =' SELECT P.Nombre_Comercial as ATC, P.Cantidad, P.Unidad_Medida, P.Concentracion, P.Presentacion, P.Descripcion_ATC as Descripcion , GROUP_CONCAT(DISTINCT(P.Codigo_Cum)) AS CUM, GROUP_CONCAT(DISTINCT(P.Id_Producto)) AS Id_Producto, 
        CONCAT_WS(" ",P.Nombre_Comercial, "(",P.Principio_Activo,P.Presentacion,P.Concentracion,") ", P.Cantidad, P.Unidad_Medida, ". ","LAB - ",P.Laboratorio_Generico ) AS Producto,
        
        SUM((PD.Cantidad_Formulada - PD.Cantidad_Entregada )) AS Consumida,
        
       IF(
            (
                (IFNULL(INV.Inventario,0) + IFNULL(RE.Rems,0)) - ROUND(SUM((PD.Cantidad_Formulada - PD.Cantidad_Entregada ) ) )
            )
            > 0,
             0,
            (
               (IFNULL(INV.Inventario,0) + IFNULL(RE.Rems,0)) -  ROUND(SUM((PD.Cantidad_Formulada - PD.Cantidad_Entregada ) ) )
            ) * -1
        ) AS Promedio_Tiempo,
        
        P.Cantidad_Presentacion

    FROM Producto_Dispensacion PD
    INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
    INNER JOIN Producto P ON P.Id_Producto = PD.Id_Producto 
    INNER JOIN Punto_Dispensacion PU ON PU.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
    
    /*  LEFT JOIN(
        SELECT IFNULL(SUM(Cantidad),0) AS Inventario, Id_Producto, Id_Punto_Dispensacion
            FROM Inventario_Nuevo
            GROUP BY Id_Punto_Dispensacion, Id_Producto
    ) INV ON INV.Id_Punto_Dispensacion = PU.Id_Punto_Dispensacion AND INV.Id_Producto = PD.Id_Producto */

    LEFT JOIN(
        SELECT IFNULL(SUM(IX.Cantidad),0) AS Inventario, IX.Id_Producto, ES.Id_Punto_Dispensacion
            FROM Inventario_Nuevo IX
            INNER JOIN Estiba ES ON ES.Id_Estiba = IX.Id_Estiba
            GROUP BY ES.Id_Punto_Dispensacion, IX.Id_Producto
    ) INV ON INV.Id_Punto_Dispensacion = PU.Id_Punto_Dispensacion AND INV.Id_Producto = PD.Id_Producto
    
    LEFT JOIN(
        SELECT  IFNULL(SUM(PR.Cantidad),0 )AS Rems,  PR.Id_Producto, R.Id_Destino AS Id_Punto_Dispensacion
        FROM Remision R INNER JOIN Producto_Remision PR ON PR.Id_Remision = R.Id_Remision
        WHERE R.Tipo_Destino = "Punto_Dispensacion" AND R.Estado != "Recibida" AND R.Estado != "Anulada"
        GROUP BY R.Id_Destino, PR.Id_Producto
    ) RE ON RE.Id_Punto_Dispensacion = PU.Id_Punto_Dispensacion AND RE.Id_Producto = PD.Id_Producto        
   
   
    WHERE DATE(D.Fecha_Actual) BETWEEN "'.$fechaInicial.'" AND "'.$fechaFinal.'"
    AND P.Tipo = "nutriciones" 

    AND D.Estado_Dispensacion!="Anulada"
    AND P.Tipo_Pos = "No Pos"
    AND PU.Tipo_Dispensacion="Entrega"
    GROUP BY P.Id_Producto';

    
}

function existePrecompra(){
    global $tipoMedicamento,$tipo;
    $cond = '';
    if($tipoMedicamento != 'Todos'){
        $cond = ' AND ( Tipo_Medicamento = "'.$tipoMedicamento.'" OR Tipo_Medicamento = "Todos")  ';
    }
    $query = ' SELECT * FROM Pre_Compra
                 WHERE Estado = "Pendiente" 
                 AND DATE(Fecha) >= DATE_SUB(NOW(),INTERVAL 15 DAY) '.$cond .' AND Tipo = "'.$tipo.'" ';
    
    $oCon= new consulta();
    $oCon->setQuery($query);

    $pre_compras = $oCon->getData();
    unset($oCon);
    return $pre_compras;

}



?>          
          