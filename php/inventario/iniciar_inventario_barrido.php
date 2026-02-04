<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.lista.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');

    $idCategoria = ( isset( $_REQUEST['idCategoria'] ) ? $_REQUEST['idCategoria'] : '' );
    $idBodega = ( isset( $_REQUEST['idBodega'] ) ? $_REQUEST['idBodega'] : '' );
    $Letras = ( isset( $_REQUEST['Letras'] ) ? $_REQUEST['Letras'] : '' );
    $Contador = ( isset( $_REQUEST['Contador'] ) ? $_REQUEST['Contador'] : '' );
    $Digitador = ( isset( $_REQUEST['Digitador'] ) ? $_REQUEST['Digitador'] : '' );
    $Tipo = ( isset( $_REQUEST['Tipo'] ) ? $_REQUEST['Tipo'] : '' );
    
    $inicio = date("Y-m-d H:i:s");

    $query="SELECT Identificacion_Funcionario, Nombres, Apellidos,Imagen FROM Funcionario WHERE Identificacion_Funcionario=".$Contador;
        $oCon= new consulta();
        $oCon->setQuery($query);
        $func_contador = $oCon->getData();
        unset($oCon);

    $query="SELECT Identificacion_Funcionario, Nombres, Apellidos,Imagen FROM Funcionario WHERE Identificacion_Funcionario=".$Digitador;
    $oCon= new consulta();
    $oCon->setQuery($query);
    $func_digitador = $oCon->getData();
    unset($oCon);
            
    if(isset($func_contador["Identificacion_Funcionario"])&&isset($func_digitador["Identificacion_Funcionario"])){
        $query = 'SELECT COUNT(*) as Total_Productos FROM Inventario I
        INNER JOIN Producto PRD
        ON I.Id_Producto = PRD.Id_Producto
        WHERE I.Id_Bodega='.$idBodega.$cond;
        
        $oCon= new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $total = $oCon->getData();
        unset($oCon);

        $total["Total_Productos"] = count($total);


        $oItem = new complex("Bodega","Id_Bodega",$idBodega);
        $bodega = $oItem->getData();
        unset($oItem);

        if($idCategoria!="0" && $idCategoria!=0){            
            /* ----- */
            $query="SELECT * FROM Categoria WHERE Id_Categoria IN (".$idCategoria.")";
            $oCon = new consulta();
            $oCon->setQuery($query);
            $categoria = $oCon->getData();            
            unset($oCon);
            /* ----- */
            $total["Total_Productos"] = count($total);
            
            $oItem = new complex("Bodega","Id_Bodega",$idBodega);
            $bodega = $oItem->getData();
            unset($oItem);
            if($idCategoria!="0" && $idCategoria!=0){
                $cats=explode(",",$idCategoria);
                $categoria["Nombre"]="";
                $categoria["Id_Categoria"]="";
                foreach($cats as $c){
                    $oItem = new complex("Categoria","Id_Categoria",$c);
                    $cate = $oItem->getData();
                    unset($oItem);

                    $categoria["Nombre"].=$cate["Nombre"].",";
                    $categoria["Id_Categoria"].=$cate["Id_Categoria"].",";
                }
                $categoria["Nombre"]=trim($categoria["Nombre"],",");
                $categoria["Id_Categoria"]=trim($categoria["Id_Categoria"],",");
            }else{
                $categoria["Nombre"]="Todas";
                $categoria["Id_Categoria"]="0";
            }
        }

     
        $oItem = new complex("Inventario_Fisico","Id_Inventario_Fisico");
        $oItem->Fecha_Inicio = $inicio;
        $oItem->Bodega = $idBodega;
        $oItem->Categoria = $idCategoria;
        //$oItem->Letras = $Letras;
        $oItem->Conteo_Productos = 0;
        $oItem->Funcionario_Digita = $Digitador;
        $oItem->Funcionario_Cuenta = $Contador;
        $oItem->Tipo_Inventario = $Tipo;
        $oItem->save();
        $id_inv= $oItem->getId();
        unset($oItem);
        
        $resultado["Id_Inventario_Fisico"]=$id_inv;
        $resultado["Funcionario_Digita"]=$func_digitador;
        $resultado["Funcionario_Cuenta"]=$func_contador;
        $resultado["Bodega"] = $bodega;
        $resultado["Categoria"]=$categoria;
        $resultado["Letras"]='';
        $resultado["Inicio"] = $inicio;
        $resultado["Productos_Conteo"] = 0;
        $resultado["Tipo_Inventario"] = $Tipo;
        $resultado["Tipo"] = "success";
        $resultado["Title"] = "Inventario Iniciado Correctamente";
        $resultado["Text"] = "Vamos a dar Inicio al Inventario Físico por barrido de la siguiente categoria: \"".$categoria['Nombre']."\".<br>¡Muchos Exitos!";        
    }else{
        $resultado["Tipo"] = "error";
        $resultado["Title"] = "Error de Funcionario";
        $resultado["Text"] = "Alguna de las Cédulas de los Funcionarios, no coincide con Funcionarios Registrados en el sistema";
    }
    
    echo json_encode($resultado);
?>