<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.consulta.php');
	require('../comprobantes/funciones.php');
	require('../contabilidad/funciones.php');
	include_once('../../class/class.contabilizar.php');
	
	$contabilizar = new Contabilizar();

	$http_response = new HttpResponse();
	$response = array();
	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
	$ctas_anticipo = ( isset( $_REQUEST['ctas_anticipo'] ) ? $_REQUEST['ctas_anticipo'] : '' );
	$modelo = json_decode($modelo, true);
	$ctas_anticipo = json_decode($ctas_anticipo, true);

	$otros=($modelo['Base']+$modelo['Iva'])-($modelo['Costo_Rete_Fuente']+$modelo['Costo_Rete_Iva']);
	$modelo['Costo_NIIF']=number_format($modelo['Costo_NIIF'],2,".","");
	$modelo['Costo_PCGA']=number_format($modelo['Costo_PCGA'],2,".","");
	$modelo['Iva']=number_format($modelo['Iva'],2,".","");
	$modelo['Base']=number_format($modelo['Base'],2,".","");
	$modelo['Iva_Niif']=number_format($modelo['Iva_NIIF'],2,".","");
	$modelo['Base_Niif']=number_format($modelo['Base_NIIF'],2,".","");


	$modelo['Otros']=number_format($otros,2,'.','');
	$fecha = date('Y-m-d H:i:s');
	
   
	$modelo['Tipo_Depreciacion']=ObtenerTipoDepreciacion($modelo['Costo_PCGA']);

	$modelo['Ctas_Anticipo'] = $ctas_anticipo;
	$modelo['Codigo_Activo_Fijo'] = generarConsecutivoTipoActivo($modelo['Id_Tipo_Activo_Fijo']);
	$bandera=false;

	if ($modelo['Id_Activo_Fijo'] == '') {
		$oItem= new complex("Activo_Fijo","Id_Activo_Fijo");
		$mes = isset($modelo['Fecha']) ? date('m', strtotime($modelo['Fecha'])) : date('m');
		$anio = isset($modelo['Fecha']) ? date('Y', strtotime($modelo['Fecha'])) : date('Y');
		$cod = generarConsecutivo('Activo', $mes, $anio);
		$modelo['Codigo']=$cod;
	
	 
	}else{
	
		EliminarMovimientosContable($modelo['Id_Activo_Fijo']);
		
		$oItem= new complex("Activo_Fijo","Id_Activo_Fijo", $modelo['Id_Activo_Fijo']);  
		unset($modelo['Id_Activo_Fijo']); 
		
	}

	foreach($modelo as $index=>$value) {
		if($value!='' && $value!=null){
			$oItem->$index=$value;
		}
		
	}
	$oItem->save();
	$id_activo= $oItem->getId();
	unset($oItem);
	

	$movimiento['Datos']=ArmarConceptoContabilizzacion($modelo);
	$movimiento['Datos_Anticipos']=crearMovimientosCtasAnticipo($modelo);
	$movimiento['Id_Registro']=$id_activo;
	$movimiento['Nit']=$modelo['Nit'];
	$movimiento['Fecha']=$modelo['Fecha'];
	// var_dump($movimiento);		
	$contabilizar->CrearMovimientoContable('Activo Fijo', $movimiento);


	$http_response->SetRespuesta(0, 'Actualizacion Exitosa', 'Se ha actualizado el activo fijo exitosamente!');
	$response = $http_response->GetRespuesta();
	$response['Id']=$id_activo;

	echo json_encode($response);

	function ArmarConceptoContabilizzacion($modelo){
		
		$modelo['Id_Plan_Cuenta']=GetTipoActivoFijo($modelo['Id_Tipo_Activo_Fijo']);
		$contabilizacion['Base']=CrearMovimiento($modelo,'Base');
		if($modelo['Iva']!=0){
			$contabilizacion['Iva']=CrearMovimiento($modelo,'Iva');
		}
		if($modelo['Id_Cuenta_Rete_Ica']!=0 && $modelo['Id_Cuenta_Rete_Ica']!='' && $modelo['Costo_Rete_Ica']!=0){
			$contabilizacion['Rete_Ica']=CrearMovimiento($modelo,'Rete_Ica');
		}
		if($modelo['Id_Cuenta_Rete_Fuente']!=0 && $modelo['Id_Cuenta_Rete_Fuente']!='' && $modelo['Costo_Rete_Fuente']!=0){
			$contabilizacion['Rete_Fuente']=CrearMovimiento($modelo,'Rete_Fuente');
		}
		if($modelo['Id_Cuenta_Cuenta_Por_Pagar']!=0 && $modelo['Id_Cuenta_Cuenta_Por_Pagar']!=''){
			$contabilizacion['CtaPorPagar']=CrearMovimiento($modelo,'CtaPorPagar');
		}

		return $contabilizacion;
		
	}

	function GetTipoActivoFijo($id){
		$query="SELECT Id_Plan_Cuenta_PCGA FROM Tipo_Activo_Fijo WHERE Id_Tipo_Activo_Fijo=$id";
		$oCon= new consulta();
		$oCon->setQuery($query);
		$id_tipo = $oCon->getData();
		unset($oCon);

		return $id_tipo['Id_Plan_Cuenta_PCGA'];
	}

	function crearMovimientosCtasAnticipo($modelo) {
		global $id_activo,$fecha;

		$mod = [];
		unset($modelo['Ctas_Anticipo'][count($modelo['Ctas_Anticipo'])-1]);
		if (count($modelo['Ctas_Anticipo']) > 0) {
			foreach ($modelo['Ctas_Anticipo'] as $i => $value) {
				$datos['Id_Plan_Cuenta']=$value['Id_Plan_Cuenta'];
				$datos['Debe']='0';
				$datos['Haber']=$value['Valor'];
				$datos['Debe_Niif']='0';
				$datos['Haber_Niif']=$value['Valor'];
				$datos['Documento']=$value['Documento'];
				$datos['Detalles']= $value['Detalles'];
				$datos['Nit'] = $value['Nit'];
				$datos['Fecha_Movimiento']=$modelo['Fecha'];
				$datos['Id_Modulo']=27;
				$datos['Id_Registro_Modulo']=$id_activo;
				$datos['Id_Centro_Costo']=$modelo['Id_Centro_Costo'];
				$datos['Numero_Comprobante']=$modelo['Codigo'];
				$datos['Estado']='Activo';
				$datos['Fecha_Registro']=date('Y-m-d H:i:s');
	
				$mod[] = $datos;
			}
		}

		return $mod;
	}

	function CrearMovimiento($modelo,$tipo){
		global $id_activo,$fecha;
		$mod=[];

		$mod['Fecha_Movimiento']=$modelo['Fecha'];
		$mod['Id_Modulo']=27;
		$mod['Id_Registro_Modulo']=$id_activo;
		$mod['Nit']=$modelo['Nit'];
		$mod['Tipo_Nit']=$modelo['Tipo'];
		$mod['Documento']=$modelo['Documento'];
		$mod['Detalles']=$modelo['Concepto'];
		$mod['Id_Centro_Costo']=$modelo['Id_Centro_Costo'];
		$mod['Numero_Comprobante']=$modelo['Codigo'];
		$mod['Estado']='Activo';
		$mod['Fecha_Registro']=date('Y-m-d H:i:s');
		
		if($tipo=='Base'){
			$mod['Id_Plan_Cuenta']=$modelo['Id_Plan_Cuenta'];
			$mod['Debe']=$modelo['Base'];
			$mod['Haber']='0';
			$mod['Debe_Niif']=$modelo['Base_NIIF'];
			$mod['Haber_Niif']='0';
		}elseif ($tipo=='Iva') {
			$mod['Id_Plan_Cuenta']=$modelo['Id_Plan_Cuenta'];
			$mod['Debe']=$modelo['Iva'];
			$mod['Haber']='0';
			$mod['Debe_Niif']=$modelo['Iva_NIIF'];
			$mod['Haber_Niif']='0';
		}elseif ($tipo=='Rete_Ica') {
			$mod['Id_Plan_Cuenta']=$modelo['Id_Cuenta_Rete_Ica'];
			$mod['Debe']='0';
			$mod['Haber']=$modelo['Costo_Rete_Ica'];
			$mod['Debe_Niif']='0';
			$mod['Haber_Niif']=$modelo['Costo_Rete_Ica_NIIF'];
		}elseif ($tipo=='Rete_Fuente') {
			$mod['Id_Plan_Cuenta']=$modelo['Id_Cuenta_Rete_Fuente'];
			$mod['Debe']='0';
			$mod['Haber']=$modelo['Costo_Rete_Fuente'];
			$mod['Debe_Niif']='0';
			$mod['Haber_Niif']=$modelo['Costo_Rete_Fuente_NIIF'];
		} elseif ($tipo == 'CtaPorPagar') {
			$mod['Id_Plan_Cuenta']=$modelo['Id_Cuenta_Cuenta_Por_Pagar'];
			$mod['Debe']='0';
			$mod['Haber']=$modelo['Valor_CtaPorPagar'];
			$mod['Debe_Niif']='0';
			$mod['Haber_Niif']=$modelo['Valor_CtaPorPagar'];
			$mod['Nit']=$modelo['Nit_CtaPorPagar'];
			$mod['Documento']=$modelo['Documento_CtaPorPagar'];
			$mod['Detalles']=$modelo['Detalles'];
		}		
		
		return $mod;
	}

	function ObtenerTipoDepreciacion($base){
		$tipo='0';
		$query="SELECT 	Valor_Unidad_Tributaria FROM Configuracion WHERE Id_Configuracion=1";
		$oCon= new consulta();
		$oCon->setQuery($query);
		$uvt = $oCon->getData();
		unset($oCon);

		$valor=$uvt['Valor_Unidad_Tributaria']*50;

		if($base<$valor){
			$tipo='1';
		}
		return $tipo; 
	}

	function EliminarMovimientosContable($id){
		$query="DELETE FROM Movimiento_Contable WHERE Id_Modulo=27 AND Id_Registro_Modulo=$id AND Detalles NOT LIKE 'Adicion%'";
	
		$oCon= new consulta();
		$oCon->setQuery($query);
		$oCon->deleteData();     
		unset($oCon);
	}
?>