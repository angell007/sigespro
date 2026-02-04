<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

	$nombreCliente = ( isset( $_REQUEST['Cliente'] ) ? $_REQUEST['Cliente'] : '' );
	$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );


    $condicion = '';
    $condicion = ' ORDER BY Codigo DESC ';

	$query = 'SELECT 
	            R.Id_Remision as IdRemision, 
	            R.Codigo as Codigo, 
                R.Fecha as Fecha,
	            (SELECT COUNT(*) FROM Producto_Remision PR WHERE PR.Id_Remision=R.Id_Remision) AS Productos,  
	            (SELECT Imagen FROM Funcionario F WHERE F.Identificacion_Funcionario = R.Identificacion_Funcionario) AS Imagen, 
	            (SELECT Nombre FROM Cliente C WHERE C.Id_Cliente = R.Id_Destino) AS Cliente  
	          FROM 
	            Remision R
	          WHERE 
	          	R.Tipo = "Cliente"
	          	AND R.Estado="Enviada"
            
           UNION ALL
              (
              SELECT 
                  R.Id_Remision as IdRemision, 
                  R.Codigo as Codigo, 
                  R.Fecha as Fecha,
                  (SELECT COUNT(*) FROM Producto_Remision PR WHERE PR.Id_Remision=R.Id_Remision) AS Productos,  
                  (SELECT Imagen FROM Funcionario F WHERE F.Identificacion_Funcionario = R.Identificacion_Funcionario) AS Imagen,   
                  (SELECT Nombre_Contrato as Nombre FROM Contrato CO WHERE R.Id_Destino = CO.Id_Contrato) AS Cliente
                FROM 
                  Remision R
                WHERE 
                  R.Tipo = "Contrato"
                  AND R.Estado="Enviada"
                  ORDER BY R.Codigo DESC  
              )
               '.$condicion.' ';

	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$resultado = $oCon->getData();
	unset($oCon);

	echo json_encode($resultado);





/*

	

	$nombreCliente = ( isset( $_REQUEST['Cliente'] ) ? $_REQUEST['Cliente'] : '' );
	$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

	$condicion = '';

	if ($funcionario != '') {
		$condicion = ' AND R.Identificacion_Funcionario ='.$funcionario.' ';
	}

	$query = 'SELECT 
	            R.Id_Remision as IdRemision, 
	            R.Codigo as Codigo, 
	            (SELECT COUNT(*) FROM Producto_Remision PR WHERE PR.Id_Remision=R.Id_Remision) AS Productos, 
	            R.Fecha as Fecha, 
	            (SELECT Imagen FROM Funcionario F WHERE F.Identificacion_Funcionario = R.Identificacion_Funcionario) AS Imagen, 
	            (SELECT Nombre FROM Cliente C WHERE C.Id_Cliente = R.Id_Destino) AS Cliente
	          FROM Remision R
	          WHERE R.Tipo = "Cliente" OR R.Tipo = "Contrato" AND R.Estado="Enviada"'
	          	.$condicion
	          	.' ORDER BY R.Codigo DESC';

	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$resultado = $oCon->getData();
	unset($oCon);

	echo json_encode($resultado);
*/
?>