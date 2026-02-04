<?php
header('Content-Type: application/json');

include_once("/home/sigesproph/public_html/class/class.lista.php");
include_once("/home/sigesproph/public_html/class/class.complex.php");
include_once("/home/sigesproph/public_html/class/class.consulta.php");
date_default_timezone_set('America/Bogota');


ini_set('memory_limit', '2048M');
set_time_limit(0);
$productos = GetProductos();

$hoy = date("d-m");
$handle = fopen("/home/sigesproph/public_html/php/crontabs/grupos_expediente_$hoy.csv", 'w+');

// echo json_encode($productos); exit;
fwrite($handle, "Fecha;Cum;Cambios\n");
foreach ($productos as $p) {
	$datos = GetDatosInvima($p['Exped']);
	if ($datos) {
		CrearProducto($datos, $p, $handle);
	}
}
fwrite($handle, "ok;\n");
echo "Ok";
fclose($handle);
function CrearProducto($datos, $prods, $handle)
{
	$ids = $prods['ID'];
	$id_productos = explode(',', $ids);
	// return;
	if ( isset($datos['numeroregistrosanitario']) && $datos['numeroregistrosanitario']!='') {
		foreach ($id_productos as $id) {
			

			$oItem = new complex('Producto', 'Id_Producto', $id);
			$pr = $oItem->getData();
			$campos = '';
			
			$consecutivo = (int) (explode('-', $pr['Codigo_Cum'])[1]);
			if ($consecutivo && count($datos['presentacionescum'])) {
				foreach ($datos['presentacionescum'] as $value) {
					if ($value['consecutivo'] == $consecutivo) {
						$value['estado'] = $value['estado'] == 'I' ? 'Inactivo' : 'Activo';
						if (strtolower($oItem->Estado) != strtolower($value['estado']) ) {
							$campos .= " Estado a $value[estado],";
							$oItem->Estado = $value['estado'];
						}
						if (strtolower($oItem->Embalaje) != strtolower($value['presentacion'])) {
							$campos .= " Embalaje a $value[presentacion],";
							$oItem->Embalaje = $value['presentacion'];
						}
					}
				}
			}

			if (strtolower($oItem->Estado_Registro_Invima) != strtolower($datos['estado'])) {
				$campos .= " Estado Registro Invima a $datos[estado],";
				$oItem->Estado_Registro_Invima = $datos['estado'];
			}
			
			$principios = $datos['principios'];
			foreach ($principios as $i => $v) {
				$principios[$i]['principio_activo'] = trim($v['principio_activo']);
				$principios[$i]['cantidad'] = round(floatval($v['cantidad']),2);
			}
			$principio['cantidad']= implode("/ ",  array_column($principios, 'cantidad')) ;


			$principio['principio_activo']= implode("/ ",  array_column($principios, 'principio_activo')) ;
			$principio['unidad_medida']= implode("/ ",  array_unique(array_column($principios, 'unidad_medida'))) ;
			if(strlen($principio['cantidad']) > 50){
				$principio['cantidad']= "Error";
				$principio['principio_activo']= "Error";
				$principio['unidad_medida']= "Error";
			}

			if( $principio['principio_activo']){
   		    	if (utf8_encode(strtolower($oItem->Principio_Activo)) != utf8_encode(strtolower($principio['principio_activo']))) {
    				$campos .= " Principio Activo a $principio[principio_activo],";
    				$oItem->Principio_Activo =utf8_encode($principio['principio_activo']);
    			}
    			if (strtolower($oItem->Cantidad) != strtolower($principio['cantidad'])) {
					
    				$oItem->Cantidad = $principio['cantidad'];
    				$campos .= " cantidad a ". ($principio['cantidad']). ",";
			    }
    			if (strtolower($oItem->Unidad_Medida) != strtolower($principio['unidad_medida'])) {
    				$oItem->Unidad_Medida = $principio['unidad_medida'];
    				$campos .= " Unidad de Medida a $principio[unidad_medida],";
			    }
			}
			
			foreach ($datos['roles'] as $lab) {
				if ($lab['rol'] == 'TITULAR REGISTRO SANITARIO') {
					$lab_gen = $lab['razonSocial'];
				} else
				if ($lab['rol'] == 'IMPORTADOR') {
					$lab_comercial = $lab['razonSocial'];
				}
				if ($lab['rol'] == 'FABRICANTE') {
					$fabric = $lab['razonSocial'];
				}
			};

			$fecha_exp_inv = $datos['fechaexpedicion'] ? date('Y-m-d', strtotime($datos['fechaexpedicion'])) : null;
			$fecha_venc_inv = $datos['fechavencimiento'] ? date('Y-m-d', strtotime($datos['fechavencimiento'])) : null;

			if ($oItem->Fecha_Expedicion_Invima != $fecha_exp_inv && $fecha_exp_inv) {
				$campos .= " Fecha Expedicion Invima a $fecha_exp_inv,";
				$oItem->Fecha_Expedicion_Invima = $fecha_exp_inv;
			}
			if ($oItem->Fecha_Vencimiento_Invima !=$fecha_venc_inv && $fecha_venc_inv) {
				$campos .= "Fecha Vencimiento Invima a $fecha_venc_inv,";
				$oItem->Fecha_Vencimiento_Invima = $fecha_venc_inv;
			}


			if (utf8_encode(strtolower($oItem->Nombre_Comercial)) != utf8_encode(strtolower($datos['nombreproducto'])) && ($datos['nombreproducto'])) {
				$campos .= " Nombre Comercial a $datos[nombreproducto],";
				$oItem->Nombre_Comercial = $datos['nombreproducto'];
			}

			if ($oItem->Invima != $datos['numeroregistrosanitario']) {
				$oItem->Invima = $datos['numeroregistrosanitario'];
				$campos .= " Registo_Invima a $datos[numeroregistrosanitario],";
			}
			if (strtolower($oItem->Laboratorio_Generico) != strtolower($lab_gen)) {
				$oItem->Laboratorio_Generico = $lab_gen;
			}
					
			$atc= $datos['atcs'][0];
			if($atc){
				if (strtolower($oItem->ATC) != strtolower($atc['codigo']) && $atc) {
					$campos .= " ATC a $atc[codigo],";
					$oItem->ATC = $atc['codigo'];
				}
				if (strtolower($oItem->Grupo_Farmacologico) != strtolower($atc['grupo_farmacologico']) && $atc) {
					$campos .= " GRUPO FARMACOLOGICO a $atc[grupo_farmacologico],";
					$oItem->Grupo_Farmacologico = $atc['grupo_farmacologico'];
				}
				if (strtolower($oItem->Sistema_Organico) != strtolower($atc['sistema_organico']) && $atc) {
					$campos .= " Sistema Organico a $atc[sistema_organico],";
					$oItem->Sistema_Organico = $atc['sistema_organico'];
				}
				if (strtolower($oItem->Descripcion_ATC) != strtolower($atc['sustancia_quimica'])) {
					$campos .= " Descripcion ATC a $atc[sustancia_quimica],";
					$oItem->Descripcion_ATC = $atc['sustancia_quimica'];
				}
			}
			if (strtolower($oItem->Forma_Farmaceutica) != strtolower($datos['formaFarmaceutica'])) {
				$campos .= " Forma Farmaceutica a $datos[formaFarmaceutica],";
				$oItem->Forma_Farmaceutica = $datos['formaFarmaceutica'];
			}
			
			if ($campos != '') {
				$oItem->save();
				guardarActividad($pr, $campos, $handle);
			}
			unset($oItem);




			//fin agregar
		}
	}
}

function GetProductos()
{

	$query = "SELECT 
		if(CONVERT (substring_index(P.Codigo_Cum, '-', 1), Unsigned) !=0, CONVERT (substring_index(P.Codigo_Cum, '-', 1), Unsigned), substring_index(P.Codigo_Cum, '-', 1))		as Exped,
		if(P.Codigo_Cum not like '%-%', '', substring_index(P.Codigo_Cum, '-', -1)) as Consecutivo, 
		
		group_Concat(distinct P.Id_Producto) AS ID
		
		FROM Producto P 
		Where P.Tipo ='Medicamento'
		And P.Codigo_Cum Not LIKE '%*%'

		GROUP BY Exped  
		Having Exped >0 -- AND Exped < '20089355'
		ORDER BY Exped  DESC
			";

			// echo $query; exit;
	$oCon = new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$productos = $oCon->getData();
	unset($oCon);
	return $productos;
}

function GetDatosInvima($cum)
{
	//			 1= Alimentos
	//			 2= Medicamentos
	//			 3= Bebidas Alcoholicas
	//			 4= Cosmeticos
	//			 5= Odontologicos
	//			 6= Plaguicidas
	//			 7= Medico quirurgico
	//			 8= Aseo y limpieza
	//			 9= Reactivo Diagnostico
	//			10= Homeopaticos
	//			11= Suplemento dietario
	//			12= Med. Oficinales
	//			13= Fito terapeutivo
	//			25= Biol車gico
	$rutas = array("2");
	$curl = curl_init();
	foreach ($rutas as $value) {
		$url = "https://api-interno.www.gov.co/api/tramites-y-servicios/invima/ConsultaRegistroSanitario/ConsultaDetalleRegistro/$cum/group/$value";
		$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36';
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
			CURLOPT_USERAGENT => $userAgent,

		));

		$resp   = curl_exec($curl);
		$resp = (array)json_decode($resp, true);

		if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 200 && $resp) {
			$result = $resp;

			$datos = json_encode($result);
			
			$datos = str_replace("'", '.', $datos );
			$datos = str_replace(['\n', '\r', '\t'], ' ', $datos);
			
			$result = json_decode($datos,true);
			foreach ($result as $key => $value) {
				$resultado[$key] = str_replace("\"", '-', acentos($value) );
			}
			
			return $resultado;
		}
	}
	curl_close($curl);
}

function guardarActividad($productos, $campos, $handle)
{
	$fecha = date('Y-m-d H:i:s') ;
	$texto =  "\"$fecha\";\"$productos[Codigo_Cum]\";\"$campos\"\n";
	echo $texto;
	fwrite($handle, $texto);

	$oItem = new complex('Actividad_Producto', "Id_Actividad_Producto");
	$oItem->Id_Producto = $productos['Id_Producto'];
	$oItem->Identificacion_Funcionario = '12345';
	$oItem->Detalles = "Se modificaron los siguientes parametros: $campos";
	$oItem->Fecha = date("Y-m-d H:i:s");
	$oItem->save();
	unset($oItem);
}

function acentos($cadena) 
{
	if(is_array($cadena)){
	    foreach ($cadena as $key => $value) {
			$cadena[$key] = str_replace("\"", '-', acentos($value) );
		}
	}
	elseif($cadena){
		$originales =  'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿ®©™µ´';
		$modificadas = 'AAAAAAAcEEEEIIIIDNOOOOOOUUUUybsaaaaaaaceeeeiiiidnoooooouuuyyby%%%%> ';
		$cadena = utf8_decode($cadena);
		$cadena =strtr($cadena, utf8_decode($originales), $modificadas);
		$cadena =utf8_encode($cadena);
	}
	return $cadena;
}
