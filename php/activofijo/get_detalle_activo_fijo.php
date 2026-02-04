<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$id_activo = ( isset( $_REQUEST['id_activo'] ) ? $_REQUEST['id_activo'] : '' );

	$query = 'SELECT 
		AF.*, 
		(SELECT	T.Nombre_Tercero 	FROM 
		(SELECT Identificacion_Funcionario AS Nit, 	CONCAT_WS(" ", Nombres, Apellidos) AS Nombre_Tercero,
		"Funcionario" as Tipo
		FROM Funcionario
		UNION
		SELECT 
		Id_Cliente AS Nit,
		Nombre AS Nombre_Tercero,"Cliente" as Tipo
		FROM Cliente
		UNION
		SELECT 
		Id_Proveedor AS Nit,
		IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido)) AS Nombre_Tercero, "Proveedor" as Tipo
		FROM Proveedor) T
		WHERE NIT=AF.Nit LIMIT 1) as Proveedor,
		-- (SELECT Nombre FROM Plan_Cuentas WHERE Id_Plan_Cuentas=AF.Id_Cuenta_Contrapartida) as Contrapartida,
		(SELECT Nombre FROM Centro_Costo WHERE Id_Centro_Costo=AF.Id_Centro_Costo) as Centro_Costo,
		(SELECT Nombre_Tipo_Activo FROM Tipo_Activo_Fijo WHERE Id_Tipo_Activo_Fijo=AF.Id_Tipo_Activo_Fijo ) as Tipo_Activo,
		 IFNULL((SELECT Nombre FROM   Plan_Cuentas WHERE Id_Plan_Cuentas=AF.Id_Cuenta_Rete_Ica),"No Tiene Cuenta Asociada") as Cuenta_Rete_Iva, 
		 IFNULL((SELECT Nombre FROM Plan_Cuentas WHERE Id_Plan_Cuentas=AF.Id_Cuenta_Rete_Fuente),"No Tiene Cuenta Asociada") as Cuenta_Rete_Ica,(SELECT CONCAT(Nombres," ",Apellidos ) FROM Funcionario WHERE Identificacion_Funcionario=AF.Identificacion_Funcionario) as Funcionario
	FROM Activo_Fijo AF
	WHERE
	AF.Id_Activo_Fijo ='.$id_activo;

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

	//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
	$activo=$queryObj->ExecuteQuery('simple');
   

	echo json_encode($activo);

?>