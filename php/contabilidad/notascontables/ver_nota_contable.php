<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('content-type: application/json');

require_once('../../../config/start.inc.php');
//include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
//require_once('../../../class/html2pdf.class.php');

$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');


$query = "SELECT 
        NC.*,
        (
        CASE
        NC.Tipo_Beneficiario
        WHEN 'Cliente' THEN (SELECT IF(Nombre IS NULL OR Nombre = '', CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido), Nombre) FROM Cliente WHERE Id_Cliente = NC.Beneficiario)
        WHEN 'Proveedor' THEN (SELECT IF(Nombre IS NULL OR Nombre = '', CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido), Nombre) FROM Proveedor WHERE Id_Proveedor = NC.Beneficiario)
        WHEN 'Funcionario' THEN (SELECT CONCAT_WS(' ', Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = NC.Beneficiario)
        END
        ) AS Tercero,
        IFNULL(CC.Nombre,'Sin Centro Costo')  AS Centro_Costo, 
        CC.Codigo as Codigo_Centro
        FROM Documento_Contable NC 
        LEFT JOIN Centro_Costo CC ON CC.Id_Centro_Costo = NC.Id_Centro_Costo
        WHERE NC.Id_Documento_Contable = $id";

$oCon = new consulta();
$oCon->setQuery($query);
$data = $oCon->getData();
unset($oCon);


$query = "SELECT PC.Codigo, PC.Nombre AS Cuenta, PC.Nombre_Niif AS Cuenta_Niif, PC.Codigo_Niif, CNC.Concepto, CNC.Documento,
            PC.Id_Plan_Cuentas,
            CNC.Base,
            CNC.Nit, 
            CNC.Tipo_Nit, 
            (
            CASE
                CNC.Tipo_Nit
                WHEN 'Cliente' THEN (SELECT IF(Nombre IS NULL OR Nombre = '', CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido), Nombre) FROM Cliente WHERE Id_Cliente = CNC.Nit)
                WHEN 'Proveedor' THEN (SELECT IF(Nombre IS NULL OR Nombre = '', CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido), Nombre) FROM Proveedor WHERE Id_Proveedor = CNC.Nit)
                WHEN 'Funcionario' THEN (SELECT CONCAT_WS(' ', Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = CNC.Nit)
                END
                ) AS Tercero, 
            concat(CC.Codigo, ' - ', CC.Nombre )as Centro_Costo, 
            CC.Codigo as Codigo_Centro,
            CNC.Id_Centro_Costo,
            CNC.Debito, 
            CNC.Credito,
             CNC.Cred_Niif,
             CNC.Deb_Niif 
             FROM Cuenta_Documento_Contable CNC 
             Left JOIN Plan_Cuentas PC ON CNC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
             Left JOIN Centro_Costo CC ON CC.Id_Centro_Costo = CNC.Id_Centro_Costo
             WHERE CNC.Id_Documento_Contable = $id";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$cuentas = $oCon->getData();
unset($oCon);

$query = "SELECT CNC.Cheque FROM Cuenta_Documento_Contable CNC WHERE CNC.Id_Documento_Contable = $id AND CNC.Cheque IS NOT NULL";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$cheq = $oCon->getData();

$respuesta = array( "cheque"=>$cheq, "datos"=>$data, "cuentas"=>$cuentas);

echo json_encode($respuesta);