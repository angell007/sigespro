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
	include_once('../../class/class.mensajes.php');
	include_once('../../class/class.php_mailer.php');
	include_once('../../class/class.mipres.php');
	include_once('../../class/class.querybasedatos.php');

	$http_response = new HttpResponse();	
	$mipres= new Mipres();
	$queryObj = new QueryBaseDatos();
	$sms_sender = new Mensaje();
	
	$response = array();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '');
    $func = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '');
    $rem = ( isset( $_REQUEST['rem'] ) ? $_REQUEST['rem'] : '');
    
	$modelo = json_decode($modelo, true);
	$rem = json_decode(($rem), true);
	$rem=trim($rem,",");

	$query_rem='SELECT Id_Remision, Id_Destino, Codigo FROM Remision  WHERE Id_Remision IN ('.$rem.')';
	$oCon= new consulta();
	$oCon->setQuery($query_rem);
	$oCon->setTipo('Multiple');
	$remisiones = $oCon->getData();
	unset($oCon);
	
	$codigo ='';

	foreach ($remisiones as  $value) {
		$punto=$value['Id_Destino'];
		$codigo.=$value['Codigo'].",";
		$query2="UPDATE Remision SET  Entrega_Pendientes='Si' WHERE Id_Remision=$value[Id_Remision]";

		$oCon = new consulta();
		$oCon->setQuery($query2);
		$oCon->createData();
		unset($oCon);
	}

	$codigo=trim($codigo,",");
	$fecha=date("Y-m-d H:i:s");
	/*$queryr="INSERT INTO Descarga_Pendiente_Remision (Identificacion_Funcionario,Fecha,Remisiones,Id_Punto_Dispensacion) VALUES ('$func','$fecha','$codigo',$punto)";

		
	$oCon = new consulta();
	$oCon->setQuery($queryr);
	$oCon->createData();
	unset($oCon);*/

	$oItem=new complex('Descarga_Pendiente_Remision','Id_Descarga_Pendiente_Remision');
	$oItem->Identificacion_Funcionario=$func;
	$oItem->Fecha=$fecha;
	$oItem->Remisiones=$codigo;
	$oItem->Id_Punto_Dispensacion=(INT)$punto;
	$oItem->save();
	$id_descarga_pendientes=$oItem->getId();
	unset($oItem);



	$query='SELECT PR.*, R.Id_Destino as Punto, P.Codigo_Cum FROM Producto_Remision PR INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision INNER JOIN Producto P ON PR.Id_Producto=P.Id_Producto WHERE PR.Id_Remision IN ('.$rem.') ORDER BY PR.Id_Remision';


	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$productos = $oCon->getData();
	unset($oCon);


	
	foreach ($productos as  $item) {
		$query = 'SELECT I.Id_Inventario 
		FROM Inventario I
		WHERE I.Id_Punto_Dispensacion='.$item['Punto'].' AND I.Id_Producto='.$item['Id_Producto'].' AND  I.Lote="'.$item['Lote'].'"' ;

		$oCon= new consulta();
		$oCon->setQuery($query);
		$inventario = $oCon->getData();
		unset($oCon);
		if($inventario){
			$query2="UPDATE Inventario SET  Cantidad_Pendientes=(Cantidad_Pendientes+$item[Cantidad]) WHERE Id_Inventario=$inventario[Id_Inventario]";

			$oCon = new consulta();
			$oCon->setQuery($query2);
			$oCon->createData();
			unset($oCon);


		}else{
			$fecha=date("Y-m-d H:i:s");
			$queryInsert[] = "($item[Id_Producto],'$item[Lote]',$item[Cantidad],'$item[Precio]','$item[Fecha_Vencimiento]',0,$item[Punto],'$item[Codigo_Cum]',$func,'$fecha',0 )";
		}

		
	}

if(count($queryInsert)>0){
	registrarSaldos($queryInsert);
}

	foreach ($modelo as  $item) {

		$inventario=cantidadInventario($item['Id_Producto'], $item['Lote'], $item['Id_Punto_Dispensacion']);
		
	
		if(ValidarCantidad($inventario['Cantidad_Pendientes'],$item['Cantidad'])){

			$query2=' UPDATE Inventario SET  Cantidad_Pendientes=(Cantidad_Pendientes-'.$item['Cantidad'].') WHERE Id_Inventario='.$inventario['Id_Inventario'];
			$oCon = new consulta();
			$oCon->setQuery($query2);
			$oCon->createData();
			unset($oCon);
			$cantidadentregada=$item["Cantidad_Entregada"]+$item["Cantidad"];

			$prod_disp=GetProducto($item); 
	
			if($prod_disp['Cantidad_Pendiente']>0){
				$oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion",$item["Id_Producto_Dispensacion"]); 
				$comparar=$oItem->getData();
				unset($oItem);
				if ($prod_disp["Cantidad_Entregada"] == 0) { 
					$oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion",$prod_disp["Id_Producto_Dispensacion"]);
					$oItem->Id_Producto=$item["Id_Producto"];
					$oItem->Id_Inventario=$inventario['Id_Inventario'];
					$oItem->Cum=$item["Codigo_Cum"];
					$oItem->Lote=$item["Lote"];
					$oItem->Entregar_Faltante=$item["Cantidad"];
					$oItem->Cantidad_Entregada=number_format($item["Cantidad"],0,"","");
					$oItem->save();
					$id_producto_pendiente=$oItem->getId();
					unset($oItem);
				} else {
					
					$oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion",$prod_disp["Id_Producto_Dispensacion"]); // Modificando los datos del producto para poder registrar un producto con el otro laboratorio y así cumplir entregar el pendiente completo.
					$oItem->Entregar_Faltante=0;
					$oItem->Cantidad_Formulada=number_format($prod_disp['Cantidad_Entregada'],0,"","");
					$autorizacion=$oItem->Numero_Autorizacion;
					$fecha_autorizacion=$oItem->Fecha_Autorizacion;
					$prescripcion=$oItem->Numero_Prescripcion;
					$oItem->save();
					unset($oItem);
					
					$cantidad_pendiente=$comparar['Cantidad_Formulada']-$comparar['Cantidad_Entregada']; 
					if($cantidad_pendiente==0){
						$cantidad_pendiente=$item["Cantidad"];
					}
	
					$oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion");
					$oItem->Id_Dispensacion=$item["Id_Dispensacion"];
					$oItem->Id_Producto=$item["Id_Producto"];
					$oItem->Id_Inventario=$inventario["Id_Inventario"];
					$oItem->Cum=$item["Codigo_Cum"];
					$oItem->Lote=$item["Lote"];
					$oItem->Entregar_Faltante=$item["Cantidad"];
					$oItem->Cantidad_Entregada=$item["Cantidad"];
					$oItem->Cantidad_Formulada=$prod_disp['Cantidad_Pendiente'];
					$oItem->Fecha_Autorizacion=$item["Fecha_Autorizacion"];
					$oItem->Numero_Autorizacion=$item["Numero_Autorizacion"];
					$oItem->save();
					$id_producto_pendiente=$oItem->getId();
					unset($oItem);
				}
				$oItem = new complex('Producto_Dispensacion_Pendiente',"Id_Producto_Dispensacion_Pendiente");
				$oItem->Id_Producto_Dispensacion=$id_producto_pendiente;
				$oItem->Cantidad_Entregada=$item["Cantidad"];
				$oItem->Cantidad_Pendiente=$item["Cantidad_Pendiente"];
				$oItem->Entregar_Faltante=$item["Cantidad_Pendiente"];
				$oItem->save();
				unset($oItem);
	
				$oItem = new complex('Dispensacion',"Id_Dispensacion", $item['Id_Dispensacion']);
				$pendientes = $oItem->Pendientes - $item["Cantidad"];
				$entregados = $oItem->Productos_Entregados + $item["Cantidad"];
				if ($pendientes >= 0) {
					$oItem->Pendientes = number_format($pendientes,0,"","");
					$oItem->Productos_Entregados = number_format($entregados,0,"","");
				} else { // Evitar por si cae en negativo.
					$oItem->Pendientes = 0;
					$oItem->Productos_Entregados = number_format($entregados,0,"","");
				}
				$oItem->save();
				unset($oItem);
	
				$oItem = new complex('Actividades_Dispensacion',"Id_Actividad_Dispensacion");
				$oItem->Id_Dispensacion = $item["Id_Dispensacion"];
				$oItem->Identificacion_Funcionario = $func;
				$oItem->Detalle = "Se actualizo el pendiente. Producto: $item[Nombre_Comercial] - Cantidad: $item[Cantidad]";			
				$oItem->Estado = "Creado";
				$oItem->save();
				unset($oItem);
	
				$fecha=date("Y-m-d H:i:s");
				$queryInsertPendienteRemision[]="('$item[Id_Remision]',$item[Id_Dispensacion],$item[Id_Paciente],$item[Id_Producto],$item[Cantidad],'$item[Lote]',$func,'$fecha',$item[Id_Producto_Remision],$id_descarga_pendientes)";	
			}

	

		}
		
	}

	if(count($queryInsertPendienteRemision)>0){
		registrarDescargaPendientesRemision($queryInsertPendienteRemision);
	}

     
	EnviarMensajes();

	//GuardarActividadAuditoria($modelo['Id_Auditoria'], $modelo['Identificacion_Funcionario'],$modelo['Estado'], $modelo['Observacion']);
	//GuardarAlerta($modelo['Id_Auditoria']);

    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha(n) agregado(s) exitosamente los datos de las dispensaciones pendientes!');
    $response = $http_response->GetRespuesta();

	echo json_encode($response);

	ValidarDispensaciones($id_descarga_pendientes);


	function registrarSaldos($queryInsert){
		$query = "INSERT INTO Inventario (Id_Producto,Lote,Cantidad_Pendientes,Costo,Fecha_Vencimiento,Id_Bodega,Id_Punto_Dispensacion,Codigo_CUM, Identificacion_Funcionario,Fecha_Carga,Cantidad) VALUES " . implode(',',$queryInsert);
	
		$oCon = new consulta();
		$oCon->setQuery($query);
		$oCon->createData();
		unset($oCon);
	
		return;
	}

	function registrarDescargaPendientesRemision($queryInsertPendienteRemision){
		$query = "INSERT INTO Producto_Descarga_Pendiente_Remision (Id_Remision,Id_Dispensacion, Id_Paciente, Id_Producto,Cantidad,Lote,Identificacion_Funcionario,Fecha,Id_Producto_Remision,Id_Descarga_Pendiente_Remision) VALUES " . implode(',',$queryInsertPendienteRemision);
	
		$oCon = new consulta();
		$oCon->setQuery($query);
		$oCon->createData();
		unset($oCon);
	
		return;
	}

	function cantidadInventario($id_producto, $lote,$punto) {

		$query = "SELECT Id_Inventario, Cantidad_Pendientes FROM Inventario WHERE Id_Producto =".$id_producto." AND Lote='".$lote."' AND Id_Punto_Dispensacion=".$punto." AND Cantidad_Pendientes>0 "  ;

		$oCon = new consulta();
		$oCon->setQuery($query);
		$inventario = $oCon->getData();
		unset($oCon);
	
		return $inventario;
		
	}
	function ValidarCantidad($cantidad_pendientes,$cantidad_entrega){
		if (($cantidad_pendientes-$cantidad_entrega) >= 0) {
			return true;
		}
	
		return false;
	}

	function GetProducto($prod){

		$id_producto = $prod['Id_Producto'];
	
		$query="SELECT *,(Cantidad_Formulada-Cantidad_Entregada) as Cantidad_Pendiente
		FROM Producto_Dispensacion WHERE Id_Dispensacion=$prod[Id_Dispensacion] AND Id_Producto=$id_producto HAVING Cantidad_Pendiente>0 " ;
	
	
		$oCon = new consulta();
		$oCon->setQuery($query);
		$pd = $oCon->getData();
		unset($oCon);
	
		return $pd;
	}

	function CargarMensajes($idProducto, $idPaciente, $idFuncionario, $idRemision, $idDis){
		global $punto_dispensacion, $enviar_mensajes;

		$paciente = GetInfoPaciente($idPaciente);
		$producto = GetInfoProducto($idProducto);
		$fecha_actual = date('Y-m-d');
		$fecha = date('d-m-Y', strtotime($fecha_actual.' + 3 days'));

		$mensaje = $paciente["Nombre_Paciente"].' Proh S.A. le informa que su medicamento pendiente '.$producto.' estara disponible a partir del '.$fecha.' en '.$punto_dispensacion;

		$enviar_mensajes[] = array('Mensaje' => $mensaje, 'Identificacion_Funcionario' => $idFuncionario, 'Id_Paciente' => $idPaciente, 'Fecha' => date('Y-m-d H:i:s'), 'Numero_Telefono' => $paciente['Numero_Telefono'], 'Id_Remision' => $idRemision, 'Id_Dispensacion' => $idDis);
	}

	function EnviarMensaje($idProducto, $idPaciente, $idFuncionario){
		global $punto_dispensacion, $sms_sender; 

		$paciente = GetInfoPaciente($idPaciente);
		$producto = GetInfoProducto($idProducto);
		$fecha_actual = date('Y-m-d');
		$fecha = date('d-m-Y', strtotime($fecha_actual.' + 3 days'));

		$mensaje = $paciente["Nombre_Paciente"].' Proh S.A. le informa que su medicamento pendiente '.$producto.' estara disponible a partir del '.$fecha.' en '.$punto_dispensacion;	
		$enviado = $sms_sender->Enviar($paciente['Numero_Telefono'], $mensaje);

		if ((INT)$paciente['Numero_Telefono'] =! 0) {
			$oItem = new complex('Mensaje',"Id_Mensaje");
			$oItem->Mensaje = $mensaje;
			$oItem->Identificacion_Funcionario = $idFuncionario;
			$oItem->Id_Paciente = $idPaciente;			
			$oItem->Fecha = date('Y-m-d H:i:s');
			$oItem->Numero_Telefono = $paciente['Numero_Telefono'];
			$oItem->save();
			unset($oItem);
		}
	}

	function EnviarMensajes(){
		global $enviar_mensajes, $sms_sender; 

		foreach ($enviar_mensajes as $key => $m) {
			
			if ((INT)$m['Numero_Telefono'] =! 0) {
				$sms_sender->Enviar($m['Numero_Telefono'], $m['Mensaje']);
			}

			$oItem = new complex('Mensaje',"Id_Mensaje");
			$oItem->Mensaje = $m['Mensaje'];
			$oItem->Identificacion_Funcionario = $m['Identificacion_Funcionario'];
			$oItem->Id_Paciente = $m['Id_Paciente'];			
			$oItem->Fecha = date('Y-m-d H:i:s');
			$oItem->Numero_Telefono = $m['Numero_Telefono'];
			$oItem->Id_Remision = $m['Id_Remision'];
			$oItem->Id_Dispensacion = $m['Id_Dispensacion'];
			$oItem->save();
			unset($oItem);
		}
	}

	function GetInfoPaciente($idPaciente){
		global $queryObj;

		$query = '
			SELECT
                CONCAT_WS(" ", P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) AS Nombre_Paciente,
                IFNULL(PT.Numero_Telefono, CAST(P.Telefono AS SIGNED)) AS Numero_Telefono
			FROM Paciente P
			LEFT JOIN Paciente_Telefono PT ON P.Id_Paciente = PT.Id_Paciente
			WHERE
				P.Id_Paciente ='.$idPaciente;

		$queryObj->SetQuery($query);
		$paciente = $queryObj->ExecuteQuery('simple');
		return $paciente;
	}

	function GetInfoProducto($idProducto){
		global $queryObj;

		$query = '
			SELECT
                Nombre_Comercial				
			FROM Producto
			WHERE
				Id_Producto ='.$idProducto;

		$queryObj->SetQuery($query);
		$producto = $queryObj->ExecuteQuery('simple');
		return $producto['Nombre_Comercial'];
	}

	function ValidarDispensaciones($id){
		global $queryObj,$mipres;

		$query = "SELECT PR.Id_Dispensacion,D.Id_Dispensacion_Mipres
                				
		FROM Producto_Descarga_Pendiente_Remision PR INNER JOIN Dispensacion D On 
		PR.Id_Dispensacion=D.Id_Dispensacion
		WHERE
			PR.Id_Descarga_Pendiente_Remision =$id
			 group by PR.Id_Dispensacion";

		$queryObj->SetQuery($query);
		$dispensaciones = $queryObj->ExecuteQuery('Multiple');

		$codigo_sede=GetCodigoSede();
		$nit=GetNitProh();

		foreach ($dispensaciones as $value) {
			$query="SELECT * FROM Producto_Dispensacion WHERE Cantidad_Formulada!=Cantidad_Entregada AND Id_Dispensacion=$value[Id_Dispensacion]";	$queryObj->SetQuery($query);
			$pendientes = $queryObj->ExecuteQuery('Multiple');

			if(count($pendientes)==0 && $value['Id_Dispensacion_Mipres']!='0'){
				$productos_mipres=GetProductosMipres($value['Id_Dispensacion_Mipres']);
				foreach ($productos_mipres as  $pm) {
					$data['ID']=(INT)$pm['ID'];
					$data['FecMaxEnt']=$pm['Fecha_Maxima_Entrega'];
					$data['TipoIDSedeProv']='NI';
					$data['NoIDSedeProv']=$nit;
					$data['CodSedeProv']=$codigo_sede;
					$data['CodSerTecAEntregar']=$pm['CodSerTecAEntregar'];
					$data['CantTotAEntregar']=$pm['Cantidad'];
					$respuesta=$mipres->Programacion($data);

					if($respuesta[0]['Id']){
						$oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$pm['Id_Producto_Dispensacion_Mipres']);
						$oItem->IdProgramacion=$respuesta[0]['IdProgramacion'];
						$oItem->save();
						unset($oItem);
					}
				}

				$oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres',$value['Id_Dispensacion_Mipres']);
				$oItem->Estado='Entregado';
				$oItem->save();
				unset($oItem);
				
			}
		}
		
	}
	
	function GetCodigoSede(){
		global $queryObj;

		$query = '
			SELECT
                Codigo_Sede				
			FROM Configuracion
			WHERE
				Id_Configuracion=1';

		$queryObj->SetQuery($query);
		$dato = $queryObj->ExecuteQuery('simple');
		return $dato['Codigo_Sede'];
	}

	function GetNitProh(){
		global $queryObj;
		$query = '
			SELECT
				NIT				
			FROM Configuracion
			WHERE
				Id_Configuracion=1';

		$queryObj->SetQuery($query);
		$dato = $queryObj->ExecuteQuery('simple');

		$n=explode('-',$dato['NIT']);
		$nit=$n[0];
		$nit=str_replace('.','',$nit);
		return $nit;
		
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


	
?>