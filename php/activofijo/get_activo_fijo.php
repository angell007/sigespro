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

	$query = '
		SELECT 
			*
		FROM Activo_Fijo
		WHERE
			Id_Activo_Fijo ='.$id_activo;

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

	//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
	$activo=$queryObj->ExecuteQuery('simple');
    $modelo['Activo']= $activo;
	$modelo['Tercero']=ObtenerTercero();
	$modelo['Centro_Costo']=ObternetypeaHead('Id_Centro_Costo','Centro_Costo',$activo['Id_Centro_Costo']);
	$modelo['Rete_Iva']=ObternetypeaHead('Id_Cuenta_Rete_Iva','Plan_Cuentas',$activo['Id_Cuenta_Rete_Iva']);
	$modelo['Contrapartida']=ObternetypeaHead('Id_Cuenta_Contrapartida','Plan_Cuentas',$activo['Id_Cuenta_Contrapartida']);
	$modelo['Rete_Fuente']=ObternetypeaHead('Id_Cuenta_Rete_Fuente','Plan_Cuentas',$activo['Id_Cuenta_Rete_Fuente']);


	echo json_encode($modelo);

	 function ObternetypeaHead($campo,$tabla,$id){
		global $queryObj;
		$query="SELECT P.Id_$tabla as Id,CONCAT_WS(' ', P.Codigo,' - ',P.Nombre) AS Nombre  FROM Activo_Fijo F  INNER JOIN $tabla P ON F.$campo=P.Id_$tabla WHERE F.$campo=$id ";
		$queryObj->SetQuery($query);
		 $datos=$queryObj->ExecuteQuery('simple');

		 return $datos;

	 }

	 function ObtenerTercero(){
		global $queryObj, $activo;
		$query='SELECT
		T.*
	FROM (SELECT 
			Identificacion_Funcionario AS Nit,
			CONCAT_WS(" ", Nombres, Apellidos) AS Nombre_Tercero,
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
	WHERE NIT='.$activo['Nit'];

		 $queryObj->SetQuery($query);
		 $datos=$queryObj->ExecuteQuery('simple');

		 return $datos;
	 }


?>