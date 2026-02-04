<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('./actividades/helper_contrato/funciones_contrato.php');
	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

	
  
    $Id_Bono = ( isset( $_REQUEST['Id_Bono'] ) ? $_REQUEST['Id_Bono'] : '' );

    if($Id_Bono){      
        $oItem = new complex("Bono_Funcionario","Id_Bono_Funcionario",$Id_Bono);   
        $oItem->Estado = 'Inactivo';
        $oItem->save();
        unset($oItem);
		$resultado["mensaje"]="Se ha cancelado el Bono correctamente!";
		$resultado["tipo"]="success";
        // $resultado['title']   = "Estado Actualizado";
        // $resultado['mensaje'] = "El estado de su Categoria esta actualizado";
        // $resultado['tipo']    = "success";
    }


	$fecha_inicial = date("Y-m-d");
	$fecha_fin=date ( 'Y-m-d' , strtotime ( $fecha_inicial.'+ 45 days') ) ;
	$fecha=date ( 'Y-m-d' , strtotime ( $fecha_inicial.'+ 30 days') ) ;

	$condicion = ' WHERE FC.Fecha_Fin_Contrato BETWEEN "'.$fecha.'" AND "'.$fecha_fin.'" AND (FC.Estado="Activo" OR FC.Estado="Preliquidado")';
	$condicion1 = ' WHERE FC.Fecha_Fin_Contrato BETWEEN "'.$fecha.'" AND "'.$fecha_fin.'" AND FC.Estado="Preliquidado"';

	$query_cumpleanos = 'SELECT 
							x.Imagen,
							CONCAT(x.Nombres, " ", x.Apellidos) AS Nombre_Funcionario, FC.Estado,
							DATE_FORMAT(x.Fecha_Nacimiento, "%m-%d") as Fecha_N, FC.*, 
							DATE_FORMAT(FC.Fecha_Fin_Contrato, "%m-%d") as Contrato,
							(IFNULL((SELECT COUNT(*) FROM Alerta WHERE Tipo="Preaviso" AND Id=FC.Id_COntrato_Funcionario),0) ) as Alertas
				        FROM Contrato_Funcionario FC
						INNER JOIN Funcionario x ON FC.Identificacion_Funcionario=x.Identificacion_Funcionario'.$condicion.' ORDER BY FC.Fecha_Fin_Contrato ASC';
    $oCon= new consulta();
	$oCon->setQuery($query_cumpleanos);
	$oCon->setTipo('Multiple');
	$resultado['Contratos'] = $oCon->getData();
	unset($oCon);

	$query_preliquidado = 'SELECT 
							x.Imagen,
							CONCAT(x.Nombres, " ", x.Apellidos) AS Nombre_Funcionario, 
							DATE_FORMAT(x.Fecha_Nacimiento, "%m-%d") as Fecha_N, FC.*, 
							DATE_FORMAT(FC.Fecha_Fin_Contrato, "%m-%d") as Contrato,
							(IFNULL((SELECT COUNT(*) FROM Alerta WHERE Tipo="Preaviso" AND Id=FC.Id_COntrato_Funcionario),0) ) as Alertas
				        FROM Contrato_Funcionario FC
						INNER JOIN Funcionario x ON FC.Identificacion_Funcionario=x.Identificacion_Funcionario'.$condicion1.' ORDER BY FC.Fecha_Fin_Contrato ASC';
    $oCon= new consulta();
	$oCon->setQuery($query_preliquidado);
	$oCon->setTipo('Multiple');
	$resultado['ContratosPreliquidados'] = $oCon->getData();
	unset($oCon);

	$queryP = 'SELECT *
	FROM(
	SELECT CF.Identificacion_Funcionario Identificacion, CONCAT(F.Nombres, " ", F.Apellidos) NombreFuncionario, 
			CF.Fecha_Inicio_Contrato InicioContrato, DATE_FORMAT(DATE_ADD(CF.Fecha_Inicio_Contrato, INTERVAL 60 DAY),"%m-%d") FinalPrueba,
			TIMESTAMPDIFF(DAY, CF.Fecha_Inicio_Contrato, CURDATE()) AS DiasLaborados
	FROM Contrato_Funcionario CF
	INNER JOIN Funcionario F ON CF.Identificacion_Funcionario = F.Identificacion_Funcionario
	WHERE TIMESTAMPDIFF(DAY, CF.Fecha_Inicio_Contrato, CURDATE()) <= 60 AND CF.Id_Tipo_Contrato = 1 AND CF.Estado = "Activo") a
	WHERE a.DiasLaborados BETWEEN 40 AND 60
UNION 
SELECT *
	FROM (
	SELECT CF.Id_Contrato_Funcionario Identificacion, CONCAT(F.Nombres, " ", F.Apellidos) NombreFuncionario, 
			CF.Fecha_Inicio_Contrato InicioContrato, DATE_FORMAT(DATE_ADD(CF.Fecha_Inicio_Contrato, INTERVAL 36 DAY),"%m-%d") FinalPrueba,
		   TIMESTAMPDIFF(DAY, CF.Fecha_Inicio_Contrato, CURDATE()) AS DiasLaborados
	FROM Contrato_Funcionario CF
	INNER JOIN Funcionario F ON CF.Identificacion_Funcionario = F.Identificacion_Funcionario
	WHERE CF.Id_Tipo_Contrato = 2	AND CF.Estado = "Activo"
			AND TIMESTAMPDIFF(DAY, CF.Fecha_Inicio_Contrato, CURDATE())<=TRUNCATE((TIMESTAMPDIFF(DAY, CF.Fecha_Inicio_Contrato, CF.Fecha_Fin_Contrato) / 5),0)) R';
	$consult = new consulta();
	$consult->setQuery($queryP);
	$consult->setTipo('Multiple');
	$resultado['ContratosPrueba'] = $consult->getData();
	unset($consult);


	echo json_encode($resultado);
?>