    <?php
	/* header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');*/
	//header('Content-Type: application/json'); 

	//include_once('../../class/class.querybasedatos.php');
	//include_once('../../class/class.http_response.php');

    //$queryObj = new QueryBaseDatos();
    $productos=GetProductos();



    foreach ($productos as $p) {
        echo "<br>Codigo CUM:<br>";
        echo $p['Codigo_Cum'];
        echo "<br>";
        if(ValidarProducto($p['Codigo_Cum'])){
            
            $datos=GetDatosInvima($p['Codigo_Cum']);
            echo "Producto por crear<br>";
            var_dump($datos);
            echo "<br>------------------------------------------<br><br>";
            //if($datos['registrosanitario']){

            if($datos){
                CrearProducto($datos,$p['Codigo_Cum']);
            } else {
                setEstadoSinCum($p['Codigo_Cum']);
            }
           
        }
      
    }


    // if(count($productos)>0){
    //     header("Location:get_direccionamiento.php");

    //     exit;
    // }

function CrearProducto($datos,$cum){

    if(ValidarProducto($cum)){
        if($datos['producto']!=''){
            $oItem=new complex('Producto','Id_Producto');
            $oItem->Codigo_Cum=$cum;
            $oItem->Principio_Activo=addslashes($datos['principioactivo']);
            $oItem->Presentacion=$datos['unidadreferencia'];
            $oItem->Concentracion=$datos['concentracion'];
            $oItem->Nombre_Comercial=addslashes($datos['producto']);
            $oItem->Embalaje=addslashes($datos['descripcioncomercial']);
            $oItem->Laboratorio_Generico=addslashes($datos['titular']);
            $oItem->Laboratorio_Comercial=addslashes($datos['nombrerol']);
            $oItem->ATC=$datos['atc'];
            $oItem->Descripcion_ATC=$datos['descripcionatc'];
            $oItem->Invima=$datos['registrosanitario'];
            $oItem->Via_Administracion=$datos['viaadministracion'];
            $oItem->Unidad_Medida=$datos['unidadmedida'];
            $oItem->Cantidad=$datos['cantidad'] !='' ? $datos['cantidad'] : '0';
            $oItem->Forma_Farmaceutica=$datos['formafarmaceutica'];
            $oItem->Fecha_Expedicion_Invima=date('Y-m-d',strtotime($datos['fechaexpedicion']));
            $oItem->Fecha_Vencimiento_Invima=date('Y-m-d',strtotime($datos['fechavencimiento']));
            $oItem->save();
            unset($oItem);

            echo "\nPRODUCTOS CREADO $cum\n";

           
        }

 
     
    }
    
        $fecha=date('Y-m-d H:i:s');
        $query = "UPDATE Producto_No_Encontrados SET Estado = 'Creado', Fecha_Creacion='$fecha' WHERE Codigo_Cum='$cum'";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->createData();
        unset($oCon);


}

function setEstadoSinCum($cum_no_encontrado) {
    $query = "UPDATE Producto_No_Encontrados SET Estado = 'Cum_No_Encontrado' WHERE Codigo_Cum='$cum_no_encontrado'";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);
}

function ValidarProducto($cum){
    $tem=explode('-',$cum);
    $cum2=$tem[0].'-'.(INT)$tem[1];
    $query="SELECT Codigo_Cum FROM Producto WHERE Codigo_Cum='$cum' OR Codigo_Cum='$cum2' ";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $producto = $oCon->getData();
    unset($oCon);

    return $producto['Codigo_Cum'] ? false : true;
}


  function GetProductos(){

    $query="SELECT Codigo_Cum FROM Producto_No_Encontrados WHERE Estado='Pendiente' group BY Codigo_Cum";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);

    return $productos;
}

function GetDatosInvima($cum){
    $rutas = array('wqeu-3uhz.json','994u-gm46.json','8tya-2uai.json','6nr4-fx8r.json','7c5e-muu4.json');
    if($cum) {
        $cum = explode('-', $cum);
    } else {
        $cum = [];
    }
    $result = [];


    if (count($cum) > 1) {
        for ($i=0; $i < count($rutas); $i++) { 

            if ($i < 3) {
                $curl = curl_init();
                // Set some options - we are passing in a useragent too here
                curl_setopt_array($curl, array(
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => 'https://www.datos.gov.co/resource/'.$rutas[$i].'?expediente=' . $cum[0] . '&consecutivocum=' . $cum[1],
                    CURLOPT_USERAGENT => 'Codular Sample cURL Request'
                ));
                // Send the request & save response to $resp
                $resp   = curl_exec($curl);
                $result = (array) json_decode($resp, true);
    
                if (count($result) > 0) {
                   
                    return $result[0];
                }
    
                // Close request to clear up some resources
                curl_close($curl);
            }
        }    
    } else {
        for ($i=0; $i < count($rutas); $i++) { 
    
            if ($i >3) {
                $curl = curl_init();
                // Set some options - we are passing in a useragent too here
                curl_setopt_array($curl, array(
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => 'https://www.datos.gov.co/resource/'.$rutas[$i].'?expediente=' . $cum[0],
                    CURLOPT_USERAGENT => 'Codular Sample cURL Request'
                ));
                // Send the request & save response to $resp
                $resp   = curl_exec($curl);
                $result = (array) json_decode($resp, true);
    
                if (count($result) > 0) {
                    return $result[0];
                }
                // Close request to clear up some resources
                curl_close($curl);
            }
        }
    }

    return $result;
}  
?>