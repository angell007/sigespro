<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.complex.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.consulta.php');

  
    $Id_Categoria = ( isset( $_REQUEST['Id_Categoria'] ) ? $_REQUEST['Id_Categoria'] : '' );
    $EstadoCat    = ( isset( $_REQUEST['EstadoCat'] ) ? $_REQUEST['EstadoCat'] : '' );
	$NombreCat    = ( isset( $_REQUEST['NombreCat'] ) ? $_REQUEST['NombreCat'] : '' );
    $NombreCat    = trim($NombreCat);

    if( $EstadoCat == 'Activado' ){
        $EstadoCat = trim('Desactivado');
    }else{
        $EstadoCat = trim('Activado');
    }

    if($Id_Categoria){
        

        $oItem = new complex("Categorias_Memorando","Id_Categorias_Memorando",$Id_Categoria);   

        $oItem->Estado = $EstadoCat;
        $oItem->save();
        unset($oItem);

        $resultado['title']   = "Estado Actualizado";
        $resultado['mensaje'] = "El estado de su Categoria esta actualizado";
        $resultado['tipo']    = "success";
        
    }else{

        // $resultado['mensaje'] = "Categoria guardada";
        // $resultado['tipo']    = "success";

        $query = " SELECT * FROM Categorias_Memorando WHERE Nombre_Categoria = '$NombreCat' "; 
        $oCon  = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $data = $oCon->getData();

        if($data != null){
            
            $resultado['title']   = "Esta Categoria ya Existe";
            $resultado['mensaje'] = "Esta categoria ya fue encontrada en la Base de datos, por favor escriba otra";
            $resultado['tipo']    = "error";
        
        }else{
            $oItem = new complex("Categorias_Memorando","Id_Categorias_Memorando");   
            $oItem->Estado = 'Activado';
            $oItem->Nombre_Categoria = $NombreCat;
            $oItem->save();
            unset($oItem);
    
            $resultado['title']   = "Categoria guardada";
            $resultado['mensaje'] = "La categoria se guardo de forma correcta";
            $resultado['tipo']    = "success";
        }
        //// FINAL IF DATA NULL

    }/// FINAL Id_Categoria

    echo json_encode($resultado);

?>