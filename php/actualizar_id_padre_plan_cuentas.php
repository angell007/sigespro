<?php

require_once '../class/class.consulta.php';



$codigo = isset($_REQUEST['Codigo']) ? $_REQUEST['Codigo'] : false;
$codigo1 = isset($_REQUEST['Codigo_Inicial']) ? $_REQUEST['Codigo_Inicial'] : false;
$codigo2 = isset($_REQUEST['Codigo_Final']) ? $_REQUEST['Codigo_Final'] : false;

if ($codigo1 && $codigo2) {
    # code...

$query = 'SELECT * FROM Plan_Cuentas WHERE Codigo = "'.$codigo.'"';
$query = 'SELECT Codigo FROM Plan_Cuentas WHERE Codigo BETWEEN "'.$codigo1.'" and  "'.$codigo2.'" ';

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$data = $oCon->getData();

unset($oCon);

$cod = $data['Codigo'];
$cod = substr($cod,0,-1);

#echo ' <br>* Cuenta a buscar ----->'.$data['Codigo'].'<br>';


foreach($data as $d){
      echo '<br>Cuenta :'.$d['Codigo'].'<br>';  
}

exit;
$x = 0;
do {
   
     
   /*  var_dump($cod); */
    $query = 'SELECT Id_Plan_Cuentas, Codigo FROM Plan_Cuentas WHERE Codigo = '.$cod;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $padre = $oCon->getData();
    unset($oCon);
    
   /*  echo '<pre>';
    var_dump($padre);
    echo '</pre>'; */

    if ($padre) {
        $x++;
        echo '<br>Cuenta Anterior :'.$padre['Codigo'].'<br>';    
    }
    
    $cod = substr($cod,0,-1);
    
    
} while ($cod != '' && $x!=2);

/*
echo '<pre>';
var_dump($data);*/


}