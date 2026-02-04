<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');
	require('../../class/class.guardar_archivos.php');

	//Objeto de la clase que almacena los archivos    
	$storer = new FileStorer();

	$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
	$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
	$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
	$lista = ( isset( $_REQUEST['lista'] ) ? $_REQUEST['lista'] : '' );
	$datos = (array) json_decode(utf8_decode($datos) , true);
	$lista = (array) json_decode($lista , true);

	if(isset($datos['id']) && ($datos['id']!=null || $datos['id']!="")){
		 $oItem = new complex($mod,"Id_".$mod,$datos['id']);
		 $producto=$oItem->getData();
	}else{
	 	$oItem = new complex($mod,"Id_".$mod);
	}
	
	if (!empty($_FILES['Foto']['name'])){
	    //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
	    $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'IMAGENES/PRODUCTOS/');
	    $datos["Imagen"] = $nombre_archivo[0]; 
	}

	$datos['Peso_Presentacion_Minima'] = str_replace(',', '.', $datos['Peso_Presentacion_Minima']);
	$datos['Peso_Presentacion_Regular'] = str_replace(',', '.', $datos['Peso_Presentacion_Regular']);
	$datos['Peso_Presentacion_Maxima'] = str_replace(',', '.', $datos['Peso_Presentacion_Maxima']);
	$datos['Laboratorio_Generico'] = addslashes($datos['Laboratorio_Generico']);
	$datos['Laboratorio_Comercial'] = addslashes($datos['Laboratorio_Comercial']);
	$datos['Nombre_Comercial'] = addslashes($datos['Nombre_Comercial']);

	$cambios = 'Se modimidificaron los siguientes parametros: ';
	$edito = false;
	unset($datos['id']);

	foreach($datos as $index=>$value){
		if(isset($producto)){
			if ($datos[$index] != $producto[$index]) {
				$edito = true;
				$cambios .= " $index = $producto[$index] por $datos[$index],";
			}
		}

		if ($index == 'Porcentaje_Arancel') {
			$oItem->Porcentaje_Arancel = number_format($value, 0, ".", "");
		}else{
	    	$oItem->$index=$value;
		}
	
	}

	$oItem->save();
	$productocum=$oItem->getData();
	unset($oItem);

	if (isset($producto)) {
		if ($edito) {
			$oItem = new complex("Actividad_Producto","Id_Actividad_Producto");
			$oItem->Identificacion_Funcionario=$funcionario;
			$oItem->Id_Producto=$producto['Id_Producto'];
			$oItem->Detalles=trim($cambios,",");
			$oItem->save();			
			unset($oItem);
		}
	}
	$cum = [];
	$i=-1;
	foreach($lista as $lis){$i++;
		
		$query = "SELECT Id_Producto_Lista_Ganancia FROM Producto_Lista_Ganancia WHERE Cum = '$datos[Codigo_Cum]' AND Id_Lista_Ganancia = '$lis[Id_Lista_Ganancia]'";
		$oCon = new consulta();
		$oCon->setQuery($query);
		$result = $oCon->getData();
		unset($oCon);

		if ($result) {	
			$oItem = new complex('Producto_Lista_Ganancia','Id_Producto_Lista_Ganancia',$result['Id_Producto_Lista_Ganancia']);
			$pl=$oItem->getData();
			$oItem->Precio_Anterior = number_format($pl['Precio'],2,".","");
			$oItem->Ultima_Actualizacion = date("Y-m-d H:i:s");
			$oItem->Precio = number_format($lis['Precio'],2,".","");
			$oItem->save();
			unset($oItem);
			if( $pl['Precio'] !=  $lis['Precio']){
				guardarActListaGanancia($result['Id_Producto_Lista_Ganancia'], "Producto Editado", $pl['Precio'], $lis['Precio']);
			}
		} else {
			if(isset($lis['Precio']) && $lis['Precio']!=null){
				$lis['Cum'] = !isset($lis['Cum']) ? $datos['Codigo_Cum'] : $lis['Cum'];
				$lis['Id_Lista_Ganancia'] = !isset($lis['Id_Lista_Ganancia']) ? ($i+1) : $lis['Id_Lista_Ganancia'];

				$oItem = new complex('Producto_Lista_Ganancia','Id_Producto_Lista_Ganancia');
				foreach ($lis as $index => $value) {
					if($value)
					$oItem->$index = $value;
				}
				$oItem->Precio = str_replace(",", ".", $lis['Precio']);
				$oItem->Cum = $datos['Codigo_Cum'];
				$oItem->save();
				$id= $oItem->getId();
				guardarActListaGanancia($id, "Producto Creado");
				unset($oItem);
			}	
		} 
	}
	$resultado['mensaje'] = "¡Producto Guardado Exitosamente!";
	$resultado['tipo'] = "success";

	echo json_encode($resultado);


function guardarActListaGanancia($id_producto_Lista, $detalle, $precio_Actual=null, $precio_nuevo=null)
{
    global $funcionario;
    $oCon = new complex("Actividad_Producto_Lista_Ganancia", "Id_Actividad_Producto_Lista_Ganancia");
    $precio_Actual?$oCon->Precio_Actual= str_replace(",", ".", $precio_Actual):'';
    $precio_nuevo?$oCon->Precio_Nuevo=str_replace(",", ".", $precio_nuevo):'';
    $oCon->Identificacion_Funcionario=$funcionario;
    $oCon->Id_Producto_Lista_Ganancia=$id_producto_Lista;
    $oCon->Fecha=date("Y-m-d H:i:s");
    $oCon->Detalle=$detalle;
    $oCon->save();
}
?>