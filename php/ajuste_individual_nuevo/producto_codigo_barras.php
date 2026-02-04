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
    $TipoAjuste = isset($_REQUEST['tipoAjuste']) ? $_REQUEST['tipoAjuste'] : '';
    $tipoSelected = isset($_REQUEST['tipoSelected']) ? $_REQUEST['tipoSelected'] : '';
    $id = isset($_REQUEST['id']) ? strtolower($_REQUEST['id']) : '';

    $query = '';

    if ($TipoAjuste != 'Lotes') {
        # code...
        if ($tipoSelected == 'Bodega') {
            # buscamos de bodega
            
            $query = BuscarEnBodega();
           
        }else if($tipoSelected == 'Punto'){
            #buscamos en pundo
            $query = BuscarEnPunto();
        }

    }else if ($TipoAjuste == 'Lotes' && $TipoAjuste != '') {
     
        if ($tipoSelected == 'Bodega') {
            # buscamos estiba
            
            $query = BuscarEnlotes();
    
        }else if($tipoSelected == 'Punto'){
            #buscamos en pundo
        }
    }
    
  
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    //$oCon->setTipo('Multiple');

  
    $resultado = array();
    

    if ($TipoAjuste == 'Entrada') {      
          //$oCon->setTipo('simple');          
        $resultado = $oCon->getData();
        unset($oCon);
        $resultado = $resultado == false ? "" : $resultado;
    }else{
        $oCon->setTipo('Multiple');
        $productos = $oCon->getData();
        unset($oCon);
        
      
        if ($TipoAjuste != 'Lotes') {
            # code...
       
            $resultado = AsignarLotes($productos);
        }else{
                 
            $resultado['Cantidad_Presentacion'] = $productos[0]['Cantidad_Presentacion'];
            $resultado['Embalaje'] = $productos[0]['Embalaje'];
            $resultado['Id_Producto'] = $productos[0]['Id_Producto'];
            $resultado['Nombre'] = $productos[0]['Nombre'];
          //  $resultado['Cantidad_Presentacion'] = $productos[0]['Cantidad_Presentacion'];
            $resultado['Lotes'] = $productos;
            
        }
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

    function BuscarEnBodega(){
        global $id, $codigo , $TipoAjuste;
        if ($TipoAjuste == 'Entrada') {
          
            $where =    ' WHERE E.Id_Bodega_Nuevo='.$id;
            
        }else{
            $where =    ' WHERE I.Id_Estiba='.$id .' AND (I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada) )>0 ';
        }

        $query = 'SELECT   PRD.Id_Producto, IFNULL(C.Costo_Promedio,0) as Precio_Venta,  PRD.Embalaje, I.Cantidad, I.Cantidad_Apartada,
       IFNULL(C.Costo_Promedio,0)  AS Costo,
         CONCAT_WS(" ",PRD.Nombre_Comercial,CONCAT("(",PRD.Principio_Activo),PRD.Presentacion,PRD.Concentracion, PRD.Cantidad, CONCAT(PRD.Unidad_Medida,")"),"LAB -",PRD.Laboratorio_Comercial, "CUM:",PRD.Codigo_Cum) as Nombre,
          PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, I.Id_Inventario_Nuevo,  IFNULL(C.Costo_Promedio,0) as precio,
          PRD.Cantidad_Presentacion, CONCAT("{\"label\":", CONCAT("\"Lote: ",I.Lote, " - Vencimiento: ",
            I.Fecha_Vencimiento," - Cantidad: ",(I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),"\""),
            ",\"value\":",I.Id_Inventario_Nuevo,",\"Codigo_Cum\":\"",I.Codigo_Cum,"\",\"Fecha_Vencimiento\":\"",
            I.Fecha_Vencimiento,"\",\"Lote\":\"",REPLACE(I.Lote,CHAR(13,10),""),"\",\"Cantidad\":\"",
            (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),"\",\"Costo\":\"", IFNULL(C.Costo_Promedio,0),"\",\"Id_Inventario_Nuevo\":\"",
            I.Id_Inventario_Nuevo,"\",\"Cantidad_Apartada\":\"",I.Cantidad_Apartada,"\",\"Nombre\":\"",
            CONCAT_WS(" ",PRD.Nombre_Comercial,CONCAT("(",PRD.Principio_Activo),PRD.Presentacion,PRD.Concentracion, 
            PRD.Cantidad, CONCAT(PRD.Unidad_Medida,")"),"LAB -",PRD.Laboratorio_Comercial, "CUM:",PRD.Codigo_Cum),
            "\",\"Embalaje\":\"",PRD.Embalaje,"\",\"Laboratorio_Comercial\":\"",PRD.Laboratorio_Comercial,"\",\"Id_Producto\":\"",
            PRD.Id_Producto,"\"}") as Lote
        FROM Inventario_Nuevo I
        INNER JOIN Producto PRD
        On I.Id_Producto=PRD.Id_Producto   
        INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
        LEFT JOIN Costo_Promedio C
	    ON C.Id_Producto = PRD.Id_Producto
        '.$where.'
        AND PRD.Codigo_Barras LIKE "'.$codigo.'%" 
        ORDER BY  PRD.Id_Producto, I.Fecha_Vencimiento ASC';
            return $query;
        
    } 

    function BuscarEnPunto(){
        global $id, $codigo, $TipoAjuste;
        $cond = '';
        if ($TipoAjuste=='Salida') {
           $cond=' AND (I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada) )>0 ';
        }
        $query = 'SELECT   PRD.Id_Producto,IFNULL(C.Costo_Promedio,0) as Precio_Venta,  IFNULL(C.Costo_Promedio,0) as Costo, PRD.Embalaje, I.Cantidad,
         I.Cantidad_Apartada, CONCAT_WS(" ",PRD.Nombre_Comercial,CONCAT("(",PRD.Principio_Activo),
         PRD.Presentacion,PRD.Concentracion, PRD.Cantidad, CONCAT(PRD.Unidad_Medida,")"),"LAB -",PRD.Laboratorio_Comercial,
          "CUM:",PRD.Codigo_Cum) as Nombre,  PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, I.Id_Inventario_Nuevo, IFNULL(C.Costo_Promedio,0)  as precio,
          PRD.Cantidad_Presentacion, 
          CONCAT("{\"label\":", CONCAT("\"Lote:", TRIM(I.Lote), " - Vencimiento: ",  I.Fecha_Vencimiento," - Cantidad: ",
          (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),"\""),",\"value\":",I.Id_Inventario_Nuevo,",\"Codigo_Cum\":\"",
          I.Codigo_Cum,"\",\"Fecha_Vencimiento\":\"",I.Fecha_Vencimiento,"\",\"Lote\":\"",TRIM(I.Lote),"\",\"Cantidad\":\"",
          (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada),"\",\"Costo\":\"",IFNULL(C.Costo_Promedio,0),"\",\"Id_Inventario_Nuevo\":\"",
          I.Id_Inventario_Nuevo,"\",\"Id_Categoria\":\"",PRD.Id_Categoria,"\",\"Cantidad_Apartada\":\"",I.Cantidad_Apartada,"\",
          \"Nombre\":\"",CONCAT_WS(" ",PRD.Nombre_Comercial,CONCAT("(",PRD.Principio_Activo),PRD.Presentacion,PRD.Concentracion, 
          PRD.Cantidad, CONCAT(PRD.Unidad_Medida,")"),"LAB -",PRD.Laboratorio_Comercial, "CUM:",PRD.Codigo_Cum),"\",\"Embalaje\":\"",
          PRD.Embalaje,"\",\"Laboratorio_Comercial\":\"",PRD.Laboratorio_Comercial,"\",\"Id_Producto\":\"",PRD.Id_Producto,"\"}") as Lote
        FROM Inventario_Nuevo I
        INNER JOIN Producto PRD
        On I.Id_Producto=PRD.Id_Producto   
        LEFT JOIN Costo_Promedio C
	    ON C.Id_Producto = PRD.Id_Producto
        WHERE I.Id_Punto_Dispensacion='.$id.' AND PRD.Codigo_Barras LIKE "'.$codigo.'"  '.$cond.'
        ORDER BY  PRD.Id_Producto, I.Fecha_Vencimiento ASC';
        return $query;
    }

    function BuscarEnLotes(){
        global $id, $codigo, $tipoSelected;
    switch($tipoSelected){
            case "Bodega":{
                /*$query = 'SELECT   PRD.Id_Producto , I.Fecha_Vencimiento, I.Id_Estiba ,I.Costo as Precio_Venta, PRD.Embalaje, I.Cantidad, I.Cantidad_Apartada, I.Id_Estiba,
                CONCAT_WS(" ",PRD.Nombre_Comercial,CONCAT("(",PRD.Principio_Activo),PRD.Presentacion,PRD.Concentracion,
                PRD.Cantidad, CONCAT(PRD.Unidad_Medida,")"),"LAB -",PRD.Laboratorio_Comercial, "CUM:",PRD.Codigo_Cum) as Nombre,
                    PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, I.Id_Inventario_Nuevo, I.Costo as precio,PRD.Cantidad_Presentacion
            FROM Inventario_Nuevo I
            INNER JOIN Producto PRD
            On I.Id_Producto=PRD.Id_Producto   
            WHERE I.Id_Estiba='.$id.' AND (I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada) )>0 
            AND PRD.Codigo_Barras LIKE "'.$codigo.'" 
            GROUP BY I.Id_Producto, I.Estiba, I.Fecha_Vencimiento


            ORDER BY  PRD.Id_Producto, I.Fecha_Vencimiento ASC';
                    
*/
            $query = 'SELECT   PRD.Id_Producto , I.Lote , SUM(I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada)) AS Cantidad,
            I.Fecha_Vencimiento,IFNULL(C.Costo_Promedio,0) as Precio_Venta, 
            PRD.Embalaje, I.Id_Estiba,

            CONCAT_WS(" ",PRD.Nombre_Comercial,CONCAT("(",PRD.Principio_Activo),PRD.Presentacion,PRD.Concentracion,
            PRD.Cantidad, CONCAT(PRD.Unidad_Medida,")"),"LAB -",PRD.Laboratorio_Comercial, "CUM:",PRD.Codigo_Cum) as Nombre,


            PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, I.Id_Inventario_Nuevo, IFNULL(C.Costo_Promedio,0) as precio,PRD.Cantidad_Presentacion
        FROM Inventario_Nuevo I
        INNER JOIN Producto PRD
        On I.Id_Producto=PRD.Id_Producto 
        LEFT JOIN Costo_Promedio C
         ON C.Id_Producto = I.Id_Producto  
        WHERE I.Id_Estiba='.$id.' AND (I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada) )>0 
        AND PRD.Codigo_Barras LIKE "'.$codigo.'" 
        GROUP BY I.Id_Inventario_Nuevo
        ORDER BY   I.Fecha_Vencimiento ASC';
            break;
            }
            case "Punto":{
                $query = 'SELECT   PRD.Id_Producto,I.Costo as Precio_Venta, PRD.Embalaje, I.Cantidad, I.Cantidad_Apartada,
                CONCAT_WS(" ",PRD.Nombre_Comercial,CONCAT("(",PRD.Principio_Activo),PRD.Presentacion,PRD.Concentracion, PRD.Cantidad,
                    CONCAT(PRD.Unidad_Medida,")"),"LAB -",PRD.Laboratorio_Comercial, "CUM:",PRD.Codigo_Cum) as Nombre,  
                    PRD.Nombre_Comercial, PRD.Laboratorio_Comercial,I.Id_Inventario_Nuevo, I.Costo as precio,PRD.Cantidad_Presentacion
                
                    FROM Inventario_Nuevo I
                    INNER JOIN Producto PRD
                    On I.Id_Producto=PRD.Id_Producto   
                WHERE I.Id_Punto_Dispensacion='.$id.' AND (I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada) )>0 
                AND PRD.Codigo_Barras LIKE "'.$codigo.'" 
                GROUP BY I.Id_Producto
                ORDER BY  PRD.Id_Producto, I.Fecha_Vencimiento ASC';
                break;
            }
        }
        return $query;
    }
    function AsignarLotes($productos){
        $resultado="";

   
        $i=-1;
        $idproducto='';
        $resultado=[];
        $pos=-1;
        $poslotes=0;
        $lotes=[];
        $cantidad_disponible=0;
      
        foreach($productos as $producto){ $i++;
            if ($producto['Id_Producto']!=$idproducto){
                if($pos>=0){
                $resultado[$pos]["Lotes"]=$lotes;
                $resultado[$pos]["Cantidad_Disponible"]=$cantidad_disponible;
                $poslotes=0;
                }
                $pos++;
                $resultado[$pos]["Id_Producto"]=$producto["Id_Producto"];
                if($producto["Nombre"]==''){
                    //var_dump($producto["Nombre_Comercial"]);
                    //var_dump ($producto["Id_Producto"]);
                    $resultado[$pos]["Nombre"]=$producto["Nombre_Comercial"]." LAB- ".$producto["Laboratorio_Comercial"];
                }else{
                    $resultado[$pos]["Nombre"]=$producto["Nombre"];
                }
            
                
                $resultado[$pos]["precio"]=$producto["precio"];
                $resultado[$pos]["Precio_Venta"]=$producto["precio"];
                $resultado[$pos]["Id_Inventario_Nuevo"]=$producto["Id_Inventario_Nuevo"];
                $resultado[$pos]["Cantidad_Presentacion"]=$producto["Cantidad_Presentacion"];
                $resultado[$pos]["Embalaje"]=$producto["Embalaje"];
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

         $resultado[$pos]["Lotes"]=$lotes;
    
            return $resultado[0];
    }

   function Asignarby(){
   
        global $productos, $id;
        $res = [];
        $res = $productos[0];
        $res['Lotes']= [];
        echo json_encode($productos);
      
        exit;

        foreach ($productos as $key => $producto) {
            # code...
    
            $query = 'SELECT I.Lote , SUM(I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada)) AS Cantidad , I.Fecha_Vencimiento,
                    I.Id_Producto ,
                    "'.$producto['Nombre'].'" Nombre,
                    CONCAT("Lote :",I.Lote," - Cantidad :",SUM(I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada))) AS label,
                    Id_Producto AS value,
                    IFNULL( (SELECT Costo_Promedio FROM Costo_Promedio WHERE Id_Producto = I.Id_Producto), 0) AS Costo,
                    "'.$producto['Laboratorio_Comercial'].'" Laboratorio_Comercial
                    FROM inventario_nuevo I 
                    WHERE I.Id_Estiba = "'.$id.'" AND Id_Producto = '.$producto['Id_Producto'].' AND I.Id_I
                    GROUP BY I.Id_Producto, I.Lote ';
    
                    
         
            $oCon= new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $lotes = $oCon->getData();
            unset($oCon);
           $vars = extract($lotes);
          // var_dump($lotes);
           //echo json_encode($lotes);
           foreach ($lotes as $key => $lote) {
               # lote por lote ingresado a la variable que retornamos en la respuesta http
               array_push($res['Lotes'],$lote);
           }
                    
                
        }


        return $res;
    
    }

    function BuscarEnBodegaPorLotes(){

    }

    function BuscarEnPuntoPorLotes(){
        
    }