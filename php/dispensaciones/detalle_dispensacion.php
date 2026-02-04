<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');

$query = 'SELECT D.*, PD.numeroAutorizacion,
                 DATE_FORMAT(D.Fecha_Actual, "%d/%m/%Y") as Fecha_Dis, 
                 CONCAT(F.Nombres, " ",  F.Apellidos) as Funcionario, 
                 P.Nombre as Punto_Dispensacion, 
                 P.Direccion as Direccion_Punto, 
                 P.Telefono Telefono_Punto, 
                 D.Codigo_Qr,
                 A.Id_Auditoria,
                 L.Nombre as Departamento, 
                 CONCAT_WS(" ",Paciente.Primer_Nombre,Paciente.Segundo_Nombre,Paciente.Primer_Apellido,  Paciente.Segundo_Apellido) as Nombre_Paciente , Paciente.EPS, Paciente.Direccion as Direccion_Paciente, R.Nombre as Regimen_Paciente, Paciente.Id_Paciente, (SELECT CONCAT(S.Nombre," - ",T.Nombre) as Nombre FROM Tipo_Servicio T INNER JOIN Servicio S on T.Id_Servicio=S.Id_Servicio WHERE T.Id_Tipo_Servicio = D.Id_Tipo_Servicio) AS Servicio, IFNULL((SELECT Numero_Telefono FROM Paciente_Telefono WHERE Id_Paciente = D.Numero_Documento LIMIT 1), "No Registrado" ) AS Telefono_Paciente
          FROM Dispensacion D
          LEFT JOIN Positiva_Data PD ON PD.Id_Dispensacion = D.Id_Dispensacion
          INNER JOIN Funcionario F on D.Identificacion_Funcionario=F.Identificacion_Funcionario
          LEFT JOIN Auditoria A on A.Id_Dispensacion = D.Id_Dispensacion or D.Id_Auditoria = A.Id_Auditoria
          INNER JOIN Punto_Dispensacion P on D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
          INNER JOIN Departamento L on P.Departamento = L.Id_Departamento
          INNER JOIN Paciente on D.Numero_Documento = Paciente.Id_Paciente
          INNER JOIN Regimen R on Paciente.Id_Regimen = R.Id_Regimen
          
          WHERE D.Id_Dispensacion =  ' . $id;

$oCon = new consulta();
$oCon->setQuery($query);
$dis = $oCon->getData();
unset($oCon);


/**Listar los tipos de soporte  roberth 12-10-2021*/
if($dis["Id_Auditoria"]){
    $query5 = 'SELECT S.*, T.Tipo_Soporte, "No" AS Cumple 
            FROM Soporte_Auditoria S
            INNER JOIN Tipo_Soporte T ON T.Id_Tipo_Soporte =  S.Id_Tipo_Soporte
            WHERE S.Archivo != "NULL" and S.Id_Auditoria =  '.$dis["Id_Auditoria"].'
            ORDER BY Id_Soporte_Auditoria ASC';

$oCon= new consulta();
$oCon->setQuery($query5);
$oCon->setTipo('Multiple');
$soportes = $oCon->getData();

unset($oCon);
}



/**
 * Codigo para anexar informacion del reclamante a la dispensacion
 */

$query = "SELECT * FROM Dispensacion_Reclamante WHERE Dispensacion_Id = '$id' ";
$oCon = new consulta();
$oCon->SetQuery($query);
$customReclamante = $oCon->getData()['Reclamante_Id'];
unset($oCon);

if ($customReclamante != null && $customReclamante != 'null') {

  $query = "SELECT Reclamante.* , DR.Parentesco FROM Reclamante   INNER JOIN Dispensacion_Reclamante AS DR ON Reclamante.Id_Reclamante = DR.Reclamante_Id
  WHERE Id_Reclamante = '$customReclamante'";
  $oCon = new consulta();
  $oCon->SetQuery($query);
  $customReclamante = $oCon->getData();
  unset($oCon);
} else {
  $customReclamante = ['Id_Reclamante' => '', 'Nombre' => '', 'Parentesco' => ''];
}
/****************************************************************************************************************** */

$query2 = 'SELECT PD.*, CONCAT_WS(" ",
P.Presentacion,
P.Concentracion, 
P.Principio_Activo,
P.Cantidad,
P.Unidad_Medida) as Nombre_Producto,
CONCAT(P.Nombre_Comercial," - CUM: ",P.Codigo_Cum) as Nombre_Comercial,
if(PD.Cantidad_Entregada=0, AD.Id_Actividades_Dispensacion, 1) AS Producto_Editado
FROM Producto_Dispensacion as PD 
INNER JOIN Producto P
on P.Id_Producto=PD.Id_Producto
LEFT JOIN Actividades_Dispensacion AD ON AD.Detalle LIKE CONCAT("%", P.Codigo_Cum, "%") AND AD.Estado ="Edicion" AND AD.Id_Dispensacion = PD.Id_Dispensacion
WHERE PD.Id_Dispensacion = ' . $id;


$oCon = new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

$query3 = 'SELECT AD.Identificacion_Funcionario, AD.Fecha, AD.Detalle, AD.Estado, CONCAT(F.Nombres," ",F.Apellidos) as Nombre, F.Imagen 
            FROM Actividades_Dispensacion AD
            INNER JOIN Funcionario F
            ON AD.Identificacion_Funcionario=F.Identificacion_Funcionario
            WHERE AD.Id_Dispensacion=  ' . $id;


$oCon = new consulta();
$oCon->setQuery($query3);
$oCon->setTipo('Multiple');
$acti = $oCon->getData();
unset($oCon);

$query4 = 'SELECT * FROM Auditoria
            WHERE Id_Dispensacion=  ' . $id;

$oCon = new consulta();
$oCon->setQuery($query4);
$auditoria = $oCon->getData();
unset($oCon);

if ($auditoria == NULL) {
  $auditoria['Id_Auditoria'] = '';
  $auditoria['Archivo'] = '';
}
$factura = null;
if ($dis['Tipo'] != 'Capita') {
  $query4 = 'SELECT F.Imagen, CONCAT(F.Nombres," ",F.Apellidos) as Nombre,(SELECT F.Codigo FROM Factura F WHERE F.Id_Factura=D.Id_Factura ) as Detalle , D.Estado_Facturacion as Estado,D.Fecha_Facturado as Fecha FROM Dispensacion D
            INNER JOIN Funcionario F ON D.Facturador_Asignado=F.Identificacion_Funcionario
            WHERE D.Id_Dispensacion= ' . $id . ' AND D.Estado_Facturacion="Facturada"';

  $oCon = new consulta();
  $oCon->setQuery($query4);
  $factura = $oCon->getData();
  unset($oCon);
} elseif ($dis['Tipo'] == 'Capita') {
  $query4 = 'SELECT F.Imagen, CONCAT(F.Nombres," ",F.Apellidos) as Nombre,(SELECT F.Codigo FROM Factura_Capita F WHERE F.Id_Factura_Capita=D.Id_Factura ) as Detalle , D.Estado_Facturacion as Estado,D.Fecha_Facturado as Fecha FROM Dispensacion D
            INNER JOIN Funcionario F ON D.Facturador_Asignado=F.Identificacion_Funcionario
            WHERE D.Id_Dispensacion= ' . $id . ' AND D.Estado_Facturacion="Facturada"';

  $oCon = new consulta();
  $oCon->setQuery($query4);
  $factura = $oCon->getData();
  unset($oCon);
}


$resultado["Datos"] = $dis;
$resultado["Productos"] = $productos;
$resultado["AcDispensacion"] = $acti;
$resultado["Auditoria"] = $auditoria;
$resultado["Factura"] = $factura;
$resultado["Reclamante"] = $customReclamante;
$resultado["Soportes"]=$soportes;


echo json_encode($resultado);
