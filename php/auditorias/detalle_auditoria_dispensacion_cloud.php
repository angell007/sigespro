<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';

$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');

$callCenter = (isset($_REQUEST['callCenter']) ? $_REQUEST['callCenter'] : '');


$dis = (isset($_REQUEST['dis']) ? $_REQUEST['dis'] : null);
$dis = json_decode($dis, true);

$query0 = "SELECT A.* , D.Tipo_Entrega,
CONCAT_WS(' ',FP.Nombres, FP.Apellidos) as FuncionarioPreauditoria, FP.Imagen, 
Group_Concat(A.Id_Dispensacion) as Id_Dispensacion

FROM Auditoria A
INNER JOIN Dispensacion D ON A.Id_Dispensacion=D.Id_Dispensacion
LEFT JOIN Funcionario FP ON A.Funcionario_Preauditoria=FP.Identificacion_Funcionario
WHERE A.Id_Auditoria in($id)";

$oCon = new consulta();
$oCon->setQuery($query0);
//$oCon->setTipo('Multiple');
$auditoria = $oCon->getData();

unset($oCon);

$callCond = $callCenter ? ' AND ( S.Cumple =  0  OR S.Cumple IS NULL  ) ' : '';

if ($auditoria["Id_Dispensacion"]) {
  $disp = "$auditoria[Id_Dispensacion]";
  if ($auditoria["Estado"] == 'Pre Auditado') {
    foreach ($dis as $key) {
      $disp .= ", $key";
    }
  }

  $query = "SELECT D.*,  Ep.Nit, Ep.Id_Eps, IF( D.Fecha_Formula like '0000-00-00','',D.Fecha_Formula) as Fecha_Formula ,DATE_FORMAT(D.Fecha_Actual, '%d/%m/%Y') as Fecha_Dis, CONCAT(F.Nombres, ' ',  F.Apellidos) as Funcionario,
        P.Nombre as Punto_Dispensacion, P.Direccion as Direccion_Punto, P.Telefono Telefono_Punto, L.Nombre as Departamento,
        CONCAT_WS(' ',Paciente.Primer_Nombre,Paciente.Segundo_Nombre,Paciente.Primer_Apellido,  Paciente.Segundo_Apellido) as Nombre_Paciente ,
        Paciente.EPS, Paciente.Direccion as Direccion_Paciente, R.Nombre as Regimen_Paciente,N.Id_Nivel,  R.Id_Regimen,Paciente.Id_Paciente,
        (Select PD.numeroAutorizacion from Positiva_Data PD where PD.Id_Dispensacion = D.Id_Dispensacion) as numeroAutorizacion,
        (SELECT CONCAT(S.Nombre,'-',T.Nombre) FROM Tipo_Servicio T INNER JOIN Servicio S ON T.Id_Servicio=S.Id_Servicio
        WHERE T.Id_Tipo_Servicio=D.Id_Tipo_Servicio ) as Tipo
          FROM Dispensacion D
          LEFT JOIN Funcionario F on D.Identificacion_Funcionario=F.Identificacion_Funcionario
          INNER JOIN Punto_Dispensacion P on D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
          INNER JOIN Departamento L on P.Departamento=L.Id_Departamento
          INNER JOIN Paciente on D.Numero_Documento = Paciente.Id_Paciente
          left JOIN Regimen R on Paciente.Id_Regimen = R.Id_Regimen
          LEFT JOIN Nivel N ON N.Id_Nivel  = Paciente.Id_Nivel
          LEFT JOIN Eps Ep ON Ep.Nit = Paciente.Nit
	      WHERE D.Id_Dispensacion in($disp)";
  $oCon = new consulta();
  $oCon->setQuery($query);
  $callCenter ? '' : $oCon->setTipo('Multiple');
  $disp = $oCon->getData();
  unset($oCon);

  $callCenter ? $dis[] = $disp : $dis = $disp;
  foreach ($dis as $index => $dispensacion) {

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
          WHERE PD.Id_Dispensacion =  ' . $dispensacion["Id_Dispensacion"];

    $oCon = new consulta();
    $oCon->setQuery($query2);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);
    $dis[$index]['Productos'] = $productos;
  }
  $id = explode(',', $id)[0];
  $auditoria["Id_Dispensacion"] = explode(',',  $auditoria["Id_Dispensacion"])[0];

  $query3 = '(SELECT AD.Identificacion_Funcionario, AD.Fecha, AD.Detalle as Detalles , AD.Estado, CONCAT(F.Nombres," ",F.Apellidos) as Funcionario, F.Imagen
    FROM Actividad_Auditoria AD
    INNER JOIN Funcionario F
    ON AD.Identificacion_Funcionario=F.Identificacion_Funcionario
    WHERE AD.Id_Auditoria like  (' . $id . '))
    UNION (
      SELECT AD.Identificacion_Funcionario, AD.Fecha, AD.Detalle, AD.Estado, CONCAT(F.Nombres," ",F.Apellidos) as Nombre, F.Imagen
    FROM Actividades_Dispensacion AD
    INNER JOIN Funcionario F
    ON AD.Identificacion_Funcionario=F.Identificacion_Funcionario
    WHERE AD.Id_Dispensacion like (' . $auditoria["Id_Dispensacion"] . ')
    ) ORDER BY Fecha';

  $oCon = new consulta();
  $oCon->setQuery($query3);
  $oCon->setTipo('Multiple');
  $acti = $oCon->getData();
  unset($oCon);
} else {
  $dis = [];
  $productos = [];
  $acti = [];
}

if ($callCenter) {
  $query4 = "SELECT S.*, T.Tipo_Soporte  ,   'No' AS Cumple
        FROM Auditoria A
        INNER JOIN Tipo_Soporte T ON T.Id_Tipo_Servicio =  A.Id_Tipo_Servicio And T.Auditoria = 'Si'
        Left Join Soporte_Auditoria S on S.Id_Tipo_Soporte = T.Id_Tipo_Soporte and S.Id_Auditoria = A.Id_Auditoria      
        WHERE A.Id_Auditoria =  '$id'
        $callCond
        ORDER BY Id_Soporte_Auditoria ASC";
} else {
  $query4 = 'SELECT T.Tipo_Soporte ,  S.Id_Soporte_Auditoria,
                  T.Id_Tipo_Soporte,
                  S.Cumple,
                  S.Archivo,
                  S.Id_Auditoria
          FROM Tipo_Soporte T
          LEFT JOIN (SELECT S.* FROM Soporte_Auditoria S  WHERE S.Id_Auditoria =  (' . $id . ')) S ON T.Id_Tipo_Soporte =  S.Id_Tipo_Soporte
          WHERE T.Id_Tipo_Servicio = ' . $dis[0]['Id_Tipo_Servicio'] . ' AND T.Auditoria = "Si"';
}

// echo $query4;
$oCon = new consulta();
$oCon->setQuery($query4);
$oCon->setTipo('Multiple');
$soportes = $oCon->getData();

unset($oCon);

$resultado["Auditoria"] = $auditoria;
$resultado["Datos"] =     $callCenter ? $dis[0] : $dis;
// $resultado["Productos"] = $productos;
$resultado["Soportes"] = $soportes;
$resultado["AcDispensacion"] = $acti;

echo json_encode($resultado);
