<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../class/class.querybasedatos.php');

	$id_acta = ( isset( $_REQUEST['id_acta'] ) ? $_REQUEST['id_acta'] : '' );

	//Consultar el codigo del acta y el id de la orden de compra
    $query_id_codigo = 'SELECT 
                            Id_Orden_Compra_Nacional
                        FROM Acta_Recepcion
                        WHERE
                            Id_Acta_Recepcion = '.$id_acta;

    $oCon= new consulta();
    $oCon->setQuery($query_id_codigo);
    $id_codigo = $oCon->getData();
    unset($oCon);

	$query = 'SELECT
				AOC.*, 
				F.Imagen,
				IFNULL((CASE
				    WHEN AOC.Estado="Creacion" THEN CONCAT("1 ",AOC.Estado)
				    WHEN AOC.Estado="Recepcion" THEN CONCAT("2 ",AOC.Estado)
				    WHEN AOC.Estado="Edicion" THEN CONCAT("2 ",AOC.Estado)
				    WHEN AOC.Estado="Aprobacion" THEN CONCAT("2 ",AOC.Estado)
				END), "0 Sin Estado") as Estado_Actividad
			FROM Actividad_Orden_Compra AOC
			INNER JOIN Funcionario F ON AOC.Identificacion_Funcionario=F.Identificacion_Funcionario
			WHERE
				AOC.Id_Orden_Compra_Nacional = '.$id_codigo['Id_Orden_Compra_Nacional'].'
			Order BY Fecha ASC';
			
	try {
		
		$queryObj = new QueryBaseDatos($query);
		$result = $queryObj->Consultar('Multiple');

		echo json_encode($result);
	} catch (Exception $e) {
		echo json_encode($e);
	}
?>