<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.lista.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');
    include_once('../../class/class.http_response.php');

    $http_response = new HttpResponse();

    $codigo = isset($_REQUEST['codigo']) ? $_REQUEST['codigo'] : '';
    $IO = isset($_REQUEST['IO']) ? $_REQUEST['IO'] : '';
    $tipo = isset($_REQUEST['tipo']) ? strtolower($_REQUEST['tipo']) : '';
    $id = isset($_REQUEST['id']) ? strtolower($_REQUEST['id']) : '';

    $query = '';

    if ($IO == '1') {
        $query = "
            SELECT 
                P.Id_Producto, 
                P.Codigo_Cum,
                IFNULL(CONCAT_WS(' ',P.Principio_Activo,P.Presentacion,P.Concentracion, P.Cantidad, P.Unidad_Medida),
                       CONCAT_WS(' ',P.Nombre_Comercial,P.Laboratorio_Comercial)) as Nombre_Producto,
               P.Embalaje,
               P.Id_Categoria, 
               P.Peso_Presentacion_Regular AS Peso,
               P.Id_Producto,
               P.Nombre_Comercial, 
               P.Laboratorio_Comercial,
               '' AS Lote,
               '' AS Cantidad,
               '' AS Costo,
               '' AS Fecha_Vencimiento,
               '' AS Observacion,IFNULL((SELECT Precio FROM Producto_Acta_Recepcion WHERE Id_Producto=P.Id_Producto Order BY Id_Producto_Acta_Recepcion DESC LIMIT 1 ),'') as Costo
            FROM Producto P
            WHERE 
                Codigo_Barras LIKE '$codigo%'";
    }else{
        switch($tipo){
    
         case strtolower("Bodega"):{
         $query = 'SELECT   PRD.Id_Producto,I.Costo as Precio_Venta, PRD.Embalaje, I.Cantidad, I.Cantidad_Apartada, CONCAT_WS(" ",PRD.Nombre_Comercial,CONCAT("(",PRD.Principio_Activo),PRD.Presentacion,PRD.Concentracion, PRD.Cantidad, CONCAT(PRD.Unidad_Medida,")"),"LAB -",PRD.Laboratorio_Comercial, "CUM:",PRD.Codigo_Cum) as Nombre,  PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, I.Id_Inventario, I.Costo as precio,PRD.Cantidad_Presentacion, CONCAT("{\"label\":", CONCAT("\"Lote: ",I.Lote, " - Vencimiento: ",  I.Fecha_Vencimiento," - Cantidad: ",(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),"\""),",\"value\":",I.Id_Inventario,",\"Codigo_Cum\":\"",I.Codigo_Cum,"\",\"Fecha_Vencimiento\":\"",I.Fecha_Vencimiento,"\",\"Lote\":\"",REPLACE(I.Lote,CHAR(13,10),""),"\",\"Cantidad\":\"",(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),"\",\"Costo\":\"",IFNULL(I.Costo,0),"\",\"Id_Inventario\":\"",I.Id_Inventario,"\",\"Cantidad_Apartada\":\"",I.Cantidad_Apartada,"\",\"Nombre\":\"",CONCAT_WS(" ",PRD.Nombre_Comercial,CONCAT("(",PRD.Principio_Activo),PRD.Presentacion,PRD.Concentracion, PRD.Cantidad, CONCAT(PRD.Unidad_Medida,")"),"LAB -",PRD.Laboratorio_Comercial, "CUM:",PRD.Codigo_Cum),"\",\"Embalaje\":\"",PRD.Embalaje,"\",\"Laboratorio_Comercial\":\"",PRD.Laboratorio_Comercial,"\",\"Id_Producto\":\"",PRD.Id_Producto,"\"}") as Lote
        FROM Inventario I
        INNER JOIN Producto PRD
        On I.Id_Producto=PRD.Id_Producto   
        WHERE I.Id_Bodega='.$id.' AND PRD.Codigo_Barras LIKE "'.$codigo.'%" AND I.Cantidad>0 
        ORDER BY  PRD.Id_Producto, I.Fecha_Vencimiento ASC';
               
            break;
            }
        case strtolower("Punto"):{
            $query = 'SELECT   PRD.Id_Producto,I.Costo as Precio_Venta, PRD.Embalaje, I.Cantidad, I.Cantidad_Apartada, CONCAT_WS(" ",PRD.Nombre_Comercial,CONCAT("(",PRD.Principio_Activo),PRD.Presentacion,PRD.Concentracion, PRD.Cantidad, CONCAT(PRD.Unidad_Medida,")"),"LAB -",PRD.Laboratorio_Comercial, "CUM:",PRD.Codigo_Cum) as Nombre,  PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, I.Id_Inventario, I.Costo as precio,PRD.Cantidad_Presentacion, CONCAT("{\"label\":", CONCAT("\"Lote:", TRIM(I.Lote), " - Vencimiento: ",  I.Fecha_Vencimiento," - Cantidad: ",(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),"\""),",\"value\":",I.Id_Inventario,",\"Codigo_Cum\":\"",I.Codigo_Cum,"\",\"Fecha_Vencimiento\":\"",I.Fecha_Vencimiento,"\",\"Lote\":\"",TRIM(I.Lote),"\",\"Cantidad\":\"",(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),"\",\"Costo\":\"",IFNULL(I.Costo,0),"\",\"Id_Inventario\":\"",I.Id_Inventario,"\",\"Id_Categoria\":\"",PRD.Id_Categoria,"\",\"Cantidad_Apartada\":\"",I.Cantidad_Apartada,"\",\"Nombre\":\"",CONCAT_WS(" ",PRD.Nombre_Comercial,CONCAT("(",PRD.Principio_Activo),PRD.Presentacion,PRD.Concentracion, PRD.Cantidad, CONCAT(PRD.Unidad_Medida,")"),"LAB -",PRD.Laboratorio_Comercial, "CUM:",PRD.Codigo_Cum),"\",\"Embalaje\":\"",PRD.Embalaje,"\",\"Laboratorio_Comercial\":\"",PRD.Laboratorio_Comercial,"\",\"Id_Producto\":\"",PRD.Id_Producto,"\"}") as Lote
                FROM Inventario I
                INNER JOIN Producto PRD
                On I.Id_Producto=PRD.Id_Producto   
         WHERE I.Id_Punto_Dispensacion='.$id.' AND PRD.Codigo_Barras LIKE "'.$codigo.'" AND I.Cantidad>0 
          ORDER BY  PRD.Id_Producto, I.Fecha_Vencimiento ASC';
            break;
            }
        }
    }
      
    $oCon= new consulta();
    $oCon->setQuery($query);
    if ($IO == '1') {        
        $oCon->setTipo('simple');
    }else{
        $oCon->setTipo('multiple');
    }

    $resultado = array();

    if ($IO == '1') {                
        $resultado = $oCon->getData();
        $resultado = $resultado == false ? "" : $resultado;
    }else{
        $productos = $oCon->getData();
        $resultado = AsignarLotes($productos);
    }

    $http_response->SetDatosRespuesta($resultado);
    if ($resultado != '') {
    	$http_response->SetRespuesta(0, 'Consulta Exitosa', 'Se han encontrado datos!');	
    }else{
    	$http_response->SetRespuesta(2, 'Consulta Exitosa', 'No se han encontrado datos!');
    }

    $resultado = $http_response->GetRespuesta(); 
              
    unset($oCon);
    echo json_encode($resultado);

    function AsignarLotes($productos){
        $resultado="";

        if (count($productos) > 0) {
            $i=-1;
            $idproducto='';
            $pos=-1;
            $poslotes=0;
            $lotes=[];
            $cantidad_disponible=0;
            foreach($productos as $producto){ $i++;
                if ($producto['Id_Producto']!=$idproducto){
                    if($pos>=0){
                       $resultado["Lotes"]=$lotes;
                       $resultado["Cantidad_Disponible"]=$cantidad_disponible;
                       $poslotes=0;
                    }
                    $pos++;
                    $resultado["Id_Producto"]=$producto["Id_Producto"];
                    if($producto["Nombre"]==''){
                        //var_dump($producto["Nombre_Comercial"]);
                        //var_dump ($producto["Id_Producto"]);
                        $resultado["Nombre"]=$producto["Nombre_Comercial"]." LAB- ".$producto["Laboratorio_Comercial"];
                    }else{
                        $resultado["Nombre"]=$producto["Nombre"];
                    }
                
                    
                    $resultado["precio"]=$producto["precio"];
                    $resultado["Precio_Venta"]=$producto["precio"];
                    $resultado["Id_Inventario"]=$producto["Id_Inventario"];
                    $resultado["Cantidad_Presentacion"]=$producto["Cantidad_Presentacion"];
                    $resultado["Embalaje"]=$producto["Embalaje"];
                    $idproducto=$producto['Id_Producto'];
                    $lotes=[];
                    $cantidad_disponible=0;
                    $borrar=array("\t","\r","\n");
                    $borra2=array("","","");
                    $lotes[$poslotes]=(array) json_decode(trim(str_replace($borrar,$borra2,$producto["Lote"]) , true));
             
                    $cantidad_disponible+=($producto['Cantidad']-$producto['Cantidad_Apartada']-$producto['Cantidad_Seleccionada']);
                }else{
                    $poslotes++;
                    $borrar=array("\t","\r","\n");
                    $borra2=array("","","");
                    $lotes[$poslotes]=(array) json_decode(trim(str_replace($borrar,$borra2,$producto["Lote"]) , true));
                    
                    $cantidad_disponible+=$producto['Cantidad']-$producto['Cantidad_Apartada']-$producto['Cantidad_Seleccionada'];
                }             
            }

            $resultado["Lotes"]=$lotes;
        }
   
        return $resultado;
    }
?>