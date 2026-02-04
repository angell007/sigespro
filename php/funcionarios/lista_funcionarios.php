<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['iden']) && $_REQUEST['iden'] != "") {
  $condicion .= "WHERE F.Identificacion_Funcionario LIKE '$_REQUEST[iden]%'";
}

if ($condicion != "") {
  if (isset($_REQUEST['nombre']) && $_REQUEST['nombre'] != "") {
    $condicion .= " AND CONCAT(F.Nombres,' ', F.Apellidos) LIKE '%$_REQUEST[nombre]%'";
  }
} else {
  if (isset($_REQUEST['nombre']) && $_REQUEST['nombre'] != "") {
    $condicion .= "WHERE CONCAT(F.Nombres,' ', F.Apellidos) LIKE '%$_REQUEST[nombre]%'";
  }
}

if ($condicion != "") {
  if (isset($_REQUEST['pto_asig']) && $_REQUEST['pto_asig'] != "") {
    $condicion .= " AND P.Id_Punto_Dispensacion=$_REQUEST[pto_asig]";
  }
} else {
  if (isset($_REQUEST['pto_asig']) && $_REQUEST['pto_asig'] != "") {
    $condicion .= "WHERE P.Id_Punto_Dispensacion=$_REQUEST[pto_asig]";
  }
}

if ($condicion != "") {
  if (isset($_REQUEST['cargo']) && $_REQUEST['cargo'] != "") {
    $condicion .= " AND C.Id_Cargo LIKE '%$_REQUEST[cargo]%'";
  }
} else {
  if (isset($_REQUEST['cargo']) && $_REQUEST['cargo'] != "") {
    $condicion .= "WHERE C.Id_Cargo LIKE '%$_REQUEST[cargo]%'";
  }
}

if ($condicion != "") {
  if (isset($_REQUEST['depen']) && $_REQUEST['depen'] != "") {
    $condicion .= " AND D.Nombre LIKE '%$_REQUEST[depen]%'";
  }
} else {
  if (isset($_REQUEST['depen']) && $_REQUEST['depen'] != "") {
    $condicion .= "WHERE D.Nombre LIKE '%$_REQUEST[depen]%'";
  }
}

if ($condicion != "") {
  if (isset($_REQUEST['grupo']) && $_REQUEST['grupo'] != "") {
    $condicion .= " AND G.Nombre LIKE '%$_REQUEST[grupo]%'";
  }
} else {
  if (isset($_REQUEST['grupo']) && $_REQUEST['grupo'] != "") {
    $condicion .= "WHERE G.Nombre LIKE '%$_REQUEST[grupo]%'";
  }
}

if ($condicion != "") {
  if (isset($_REQUEST['susp']) && $_REQUEST['susp'] != "") {
    $condicion .= " AND F.Suspendido='$_REQUEST[susp]'";
  }
} else {
  if (isset($_REQUEST['susp']) && $_REQUEST['susp'] != "") {
    $condicion .= "WHERE F.Suspendido='$_REQUEST[susp]'";
  }
}
if ($condicion != "") {
  if (isset($_REQUEST['liq']) && $_REQUEST['liq'] != "") {
    $condicion .= " AND F.Liquidado='$_REQUEST[liq]'";
  }
} else {
  if (isset($_REQUEST['liq']) && $_REQUEST['liq'] != "") {
    $condicion .= "WHERE F.Liquidado='$_REQUEST[liq]'";
  }
}

$query = 'SELECT COUNT(DISTINCT(F.Identificacion_Funcionario))  AS Total
          FROM Funcionario F
          LEFT JOIN Cargo C 
            on F.Id_Cargo=C.Id_Cargo 
          LEFT JOIN Dependencia D 
            on D.Id_Dependencia = F.Id_Dependencia 
          LEFT JOIN Grupo G 
            on G.Id_Grupo = F.Id_Grupo
          ' . $condicion;

$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 18; 
$numReg = $total["Total"]; 
$paginas = ceil($numReg/$tamPag); 
$limit = ""; 
$paginaAct = "";

if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
    $paginaAct = 1; 
    $limit = 0; 
} else { 
    $paginaAct = $_REQUEST['pag']; 
    $limit = ($paginaAct-1) * $tamPag; 
} 


$query = "SELECT 

          
          IFNULL(M.Debe , 'NO')
          AS Pago_Liquidado,
          F.*, 
          CONCAT(F.Nombres, ' ', F.Apellidos) as Funcionario, 
          C.Nombre as Cargo , 
          D.Nombre as Dependencia , 
          G.Nombre as Grupo,
          CF.Id_Contrato_Funcionario

          FROM Funcionario F
          LEFT JOIN Cargo C  on F.Id_Cargo=C.Id_Cargo 
          LEFT JOIN Dependencia D on D.Id_Dependencia = F.Id_Dependencia 
          LEFT JOIN Grupo G on G.Id_Grupo = F.Id_Grupo
          LEFT JOIN (SELECT max(Id_Contrato_Funcionario) as Id_Contrato_Funcionario, Identificacion_Funcionario FROM Contrato_Funcionario CF WHERE CF.Estado='Activo' gROUP BY CF.Identificacion_Funcionario)CF on  CF.Identificacion_Funcionario=F.Identificacion_Funcionario
          LEFT JOIN (
                  SELECT M.Debe, M.Nit 
                  FROM Movimiento_Contable M INNER JOIN Plan_Cuentas P ON P.Id_Plan_Cuentas = M.Id_Plan_Cuenta  INNER JOIN Contrato_Funcionario CF on CF.Identificacion_Funcionario = M.Nit WHERE P.Codigo ='250595' and CF.Estado !='Activo' AND M.Debe >0 and M.Tipo_Nit='Funcionario'
                  GROUP BY M.Nit) M  ON M.Nit=F.Identificacion_Funcionario
          
          $condicion
          GROUP BY F.Identificacion_Funcionario
          ORDER BY F.Identificacion_Funcionario
            LIMIT $limit, $tamPag
          ";

// echo $query; exit;
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$funcionarios['funcionarios'] = $oCon->getData();
unset($oCon);

$i=-1;

$funcionarios['numReg'] = $numReg;


echo json_encode($funcionarios);

?>
