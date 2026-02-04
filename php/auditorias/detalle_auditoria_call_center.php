<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$callCenter = ( isset( $_REQUEST['callCenter'] ) ? $_REQUEST['callCenter'] : '' );
$query0 = 'SELECT A.* ,
CONCAT_WS(" ",FP.Nombres, FP.Apellidos) as FuncionarioPreauditoria, FP.Imagen

FROM Auditoria A 
LEFT JOIN Funcionario FP
ON A.Funcionario_Preauditoria=FP.Identificacion_Funcionario
WHERE Id_Auditoria =  '.$id;

$oCon= new consulta();
$oCon->setQuery($query0);
//$oCon->setTipo('Multiple');
$auditoria = $oCon->getData();

unset($oCon);

$callCond = $callCenter ? ' AND Cumple = 1 ' : '';

if ($auditoria["Id_Dispensacion"]) {
  $query = 'SELECT D.*,  Ep.Nit, EP.Id_Eps, IF( D.Fecha_Formula like "0000-00-00","",D.Fecha_Formula) as Fecha_Formula ,DATE_FORMAT(D.Fecha_Actual, "%d/%m/%Y") as Fecha_Dis, CONCAT(F.Nombres, " ",  F.Apellidos) as Funcionario, 
  P.Nombre as Punto_Dispensacion, P.Direccion as Direccion_Punto, P.Telefono Telefono_Punto, L.Nombre as Departamento, 
  CONCAT_WS(" ",Paciente.Primer_Nombre,Paciente.Segundo_Nombre,Paciente.Primer_Apellido,  Paciente.Segundo_Apellido) as Nombre_Paciente , 
  Paciente.EPS, Paciente.Direccion as Direccion_Paciente, R.Nombre as Regimen_Paciente,N.Id_Nivel,  R.Id_Regimen,Paciente.Id_Paciente,

   (SELECT CONCAT(S.Nombre,"-",T.Nombre) FROM Tipo_Servicio T INNER JOIN Servicio S ON T.Id_Servicio=S.Id_Servicio 
   WHERE T.Id_Tipo_Servicio=D.Id_Tipo_Servicio ) as Tipo
          FROM Dispensacion D
          LEFT JOIN Funcionario F
          on D.Identificacion_Funcionario=F.Identificacion_Funcionario
          INNER JOIN Punto_Dispensacion P
          on D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
          INNER JOIN Departamento L
          on P.Departamento=L.Id_Departamento
          INNER JOIN Paciente
          on D.Numero_Documento = Paciente.Id_Paciente
          left JOIN Regimen R
          on Paciente.Id_Regimen = R.Id_Regimen
          LEFT JOIN Nivel N 
          ON N.Id_Nivel  = Paciente.Id_Nivel
          LEFT JOIN Eps Ep ON EP.Nit = Paciente.Nit
	  WHERE D.Id_Dispensacion =  '.$auditoria["Id_Dispensacion"]
	  
	  ;

$oCon= new consulta();
$oCon->setQuery($query);
//$oCon->setTipo('Multiple');
$dis = $oCon->getData();
unset($oCon);


$query2 = 'SELECT PD.*, CONCAT_WS(" ",
            P.Nombre_Comercial,
            P.Presentacion,
            P.Concentracion, " (",
            P.Principio_Activo,") ",
            P.Cantidad,
            P.Unidad_Medida) as Nombre_Producto,CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida) as Nombre, P.Nombre_Comercial, P.Embalaje
          FROM Producto_Dispensacion as PD 
          INNER JOIN Producto P 
          on P.Id_Producto=PD.Id_Producto
          WHERE PD.Id_Dispensacion =  '.$auditoria["Id_Dispensacion"] ;


$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

    $query3 = '(SELECT AD.Identificacion_Funcionario, AD.Fecha, AD.Detalle as Detalles , AD.Estado, CONCAT(F.Nombres," ",F.Apellidos) as Funcionario, F.Imagen 
    FROM Actividad_Auditoria AD
    INNER JOIN Funcionario F
    ON AD.Identificacion_Funcionario=F.Identificacion_Funcionario
    WHERE AD.Id_Auditoria=  '.$id.') 
    UNION (
      SELECT AD.Identificacion_Funcionario, AD.Fecha, AD.Detalle, AD.Estado, CONCAT(F.Nombres," ",F.Apellidos) as Nombre, F.Imagen 
    FROM Actividades_Dispensacion AD
    INNER JOIN Funcionario F
    ON AD.Identificacion_Funcionario=F.Identificacion_Funcionario
    WHERE AD.Id_Dispensacion='.$auditoria["Id_Dispensacion"].'
    ) ORDER BY Fecha' ;


$oCon= new consulta();
$oCon->setQuery($query3);
$oCon->setTipo('Multiple');
$acti = $oCon->getData();
unset($oCon);

} else {
  $dis = [];
  $productos = [];
  $acti = [];
}

$query4 = 'SELECT S.*, T.Tipo_Soporte  FROM Soporte_Auditoria S
INNER JOIN Tipo_Soporte T ON T.Id_Tipo_Soporte =  S.Id_Tipo_Soporte
WHERE S.Id_Auditoria =  '.$id.'
'.$callCond.'
ORDER BY Id_Soporte_Auditoria ASC' ;

$oCon= new consulta();
$oCon->setQuery($query4);
$oCon->setTipo('Multiple');
$soportes = $oCon->getData();

unset($oCon);


$query4 = 'SELECT Numero_Telefono 
	  FROM Paciente_Telefono 
	  WHERE Id_Paciente =  '.$dis['Id_Paciente'];

$oCon= new consulta();
$oCon->setQuery($query4);
$oCon->setTipo('Multiple');
$telefonos = $oCon->getData();

unset($oCon);


$resultado["Auditoria"]=$auditoria;
$resultado["Datos"]=$dis;
$resultado["Productos"]=$productos;
$resultado["Telefonos"]=$telefonos;
$resultado["Soportes"]=$soportes;
$resultado["AcDispensacion"]=$acti;

echo json_encode($resultado);

?>
