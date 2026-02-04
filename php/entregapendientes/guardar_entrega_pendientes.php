<?php

	header('Access-Control-Allow-Origin: *');

	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

	header('Content-Type: application/json');



	date_default_timezone_set('America/Bogota');



	require_once('../../config/start.inc.php');

	include_once('../../class/class.complex.php');

	include_once('../../class/class.configuracion.php');

	include_once('../../class/class.http_response.php');

	include_once('../../class/class.consulta.php');

	require('../../class/class.guardar_archivos.php');

	include_once('../../class/class.mipres.php');

	include_once('../../class/class.querybasedatos.php');



	//Objeto de la clase que almacena los archivos    

	$storer = new FileStorer();

	$mipres= new Mipres();

	$queryObj = new QueryBaseDatos();

	$http_response = new HttpResponse();

	$response = array();



	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '');

    $tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '');

    

	$modelo = json_decode($modelo, true);



	$query='UPDATE Producto_Descarga_Pendiente_Remision SET Entregado="Si"  WHERE Id_Producto_Descarga_Pendiente_Remision IN ('.$modelo['Descarga'].')';

	$oCon= new consulta();

	$oCon->setQuery($query);

	$oCon->createData();

	unset($oCon);

	

	foreach ($modelo['Productos'] as  $value) {

		$oItem = new complex('Actividades_Dispensacion',"Id_Actividad_Dispensacion");

		$oItem->Id_Dispensacion = $modelo["Id_Dispensacion"];

		$oItem->Identificacion_Funcionario = $modelo['Identificacion_Funcionario'];

		$oItem->Detalle = "Se hizo la entrega formal del pendiente. Se entrega el producto: $value[Nombre_Comercial] - Cantidad: $value[Cantidad] Lote: $value[Lote]";

		$oItem->Estado = "Creado";

		$oItem->save();

		unset($oItem);

	}



	if($tipo=='Acta'){

		if (!empty($_FILES['acta']['name'])){ // Archivo de la Acta de Entrega.

		    //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO

		    $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'ARCHIVOS/DISPENSACION/ACTAS_ENTREGAS/');

		    $nombre_archivo = $nombre_archivo[0];



		    if ($nombre_archivo != '') {

		    	$oItem = new complex('Dispensacion','Id_Dispensacion',$modelo["Id_Dispensacion"]);

                $oItem->Acta_Entrega = $nombre_archivo;

                $oItem->save();

		    }



            // $posicion1 = strrpos($_FILES['acta']['name'],'.')+1;

            // $extension1 =  substr($_FILES['acta']['name'],$posicion1);

            // $extension1 =  strtolower($extension1);

            // $_filename1 = uniqid() . "." . $extension1;

            // $_file1 = $MY_FILE . "ARCHIVOS/DISPENSACION/ACTAS_ENTREGAS/" . $_filename1;

            

            // $subido1 = move_uploaded_file($_FILES['acta']['tmp_name'], $_file1);

            //     if ($subido1){		

            //         @chmod ( $_file1, 0777 );

            //         $nombre_archivo = $_filename1;

            //         $oItem = new complex('Dispensacion','Id_Dispensacion',$modelo["Id_Dispensacion"]);

            //         $oItem->Acta_Entrega = $nombre_archivo;

            //         $oItem->save();

            //         unset($oItem);

            //     } 

        }



	}elseif($tipo=='Wacom'){

		$imagen=$modelo["Firma_Digital"];



		if ($imagen != "") {

			list($type, $imagen) = explode(';', $imagen);

			list(, $imagen)      = explode(',', $imagen);

			$imagen = base64_decode($imagen);



			$fot="firma".uniqid().".jpg";

			$archi=$MY_FILE . "IMAGENES/FIRMAS-DIS/".$fot;

			file_put_contents($archi, $imagen);

			chmod($archi, 0644);



			$oItem = new complex('Dispensacion','Id_Dispensacion',$modelo["Id_Dispensacion"]);

			$oItem->Firma_Reclamante = $fot;

			$oItem->save();

		}

	}





    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha(n) agregado(s) exitosamente los datos de las dispensaciones pendientes!');

    $response = $http_response->GetRespuesta();

	ValidarDispensacionMipres($modelo["Id_Dispensacion"]);



	echo json_encode($response);



	function ValidarDispensacionMipres($idDis){

		global $queryObj,$mipres;

	

		$pendientes=GetPendientes($idDis);		

		$reclamante=GetReclamante();

	

		if(count($pendientes)==0){

			$dispensacion=GetDispensacion($idDis);

			if($dispensacion['Id_Dispensacion_Mipres']!='0'){

				$productos_mipres=GetProductosMipres($dispensacion['Id_Dispensacion_Mipres']);

				foreach ($productos_mipres as  $pm) {

					$lote=GetLoteEntregado($pm['Id_Producto'],$idDis);

					$data['ID']=(INT)$pm['ID'];

					$data['CodSerTecEntregado']=$pm['CodSerTecAEntregar'];

					$data['CantTotEntregada']=$pm['Cantidad'];

					$data['EntTotal']=0;

					$data['CausaNoEntrega']=0;

					$data['FecEntrega']=date('Y-m-d');

					$data['NoLote']=$lote;

					$data['TipoIDRecibe']='CC';

					$data['NoIDRecibe']= $reclamante;					

					$entrega=$mipres->ReportarEntrega($data);

					if($entrega[0]['Id']){

						$oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$pm['Id_Producto_Dispensacion_Mipres']);

						$oItem->IdEntrega=$entrega[0]['IdEntrega'];

						$oItem->save();

						unset($oItem);

					}

				}

	

				$oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres',$dispensacion['Id_Dispensacion_Mipres']);

				$oItem->Estado='Entregado';

				$oItem->save();

				unset($oItem);

	

			}

		}

	}

	

	function GetPendientes($idDis){

		global $queryObj;

	

		$query="SELECT PD.Id_Dispensacion,PD.Id_Producto,(SELECT Codigo FROM Dispensacion WHERE Id_Dispensacion=PD.Id_Dispensacion) as Codigo, CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre,P.Nombre_Comercial,P.Codigo_Cum

		FROM Producto_Dispensacion PD 

		INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto

		WHERE PD.Id_Dispensacion=$idDis AND PD.Cantidad_Formulada !=PD.Cantidad_Entregada";

	

		$queryObj->SetQuery($query);

		$pendientes = $queryObj->ExecuteQuery('Multiple');

	

		return $pendientes;

	}

	

	function GetDispensacion($idDis){

		global $queryObj;

	

		$query="SELECT Id_Dispensacion_Mipres,Id_Dispensacion FROM Dispensacion WHERE Id_Dispensacion=$idDis";

		$queryObj->SetQuery($query);

		$dispensacion = $queryObj->ExecuteQuery('simple');

	

	

	

		return $dispensacion; 

	}

	

	function GetProductosMipres($id){

		global $queryObj;

		$query = 'SELECT

		PD.*, D.Fecha_Maxima_Entrega		

		FROM Producto_Dispensacion_Mipres PD INNER JOIN Dispensacion_Mipres D ON PD.Id_Dispensacion_Mipres=D.Id_dispensacion_Mipres

		WHERE

		PD.Id_Dispensacion_Mipres='.$id;

	

		$queryObj->SetQuery($query);

		$productos = $queryObj->ExecuteQuery('Multiple');

		return $productos;

	}



	function GetLoteEntregado($idProducto,$idDis){

		global $queryObj;

	

		$query = "SELECT Lote 

			From Producto_Dispensacion 

			WHERE Id_Producto_Mipres=$idProducto AND Id_Dispensacion=$idDis ";

	

		$queryObj->SetQuery($query);

		$lote = $queryObj->ExecuteQuery('simple');

	

		return $lote['Lote'];

	}

	

	function GetReclamante(){

		global $queryObj,$modelo;

	

		$query = "SELECT Identificacion_Persona FROM Auditoria A INNER JOIN  Turnero T ON A.Id_Auditoria=T.Id_Auditoria

		WHERE A.Id_Dispensacion=$modelo[Id_Dispensacion] ";

	

		$queryObj->SetQuery($query);

		$persona = $queryObj->ExecuteQuery('simple');

	

		if($persona){

			return $persona['Identificacion_Persona'];

		}else{

			return $modelo['Numero_Documento'];

		}

	}



	



	

?>