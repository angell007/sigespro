<?php 
// SCRIPT PARA UNIR CÓDIGOS POR FECHAS PARA AMBOS TIPOS DE NOTAS


include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$query = ' SELECT N.* 
            FROM(
                     (SELECT Id_Nota_Credito AS Id , "Nota_Credito" AS Tipo, Codigo, Fecha FROM Nota_Credito 
                     WHERE DATE(Fecha) >= "2020-09-10"
                     )
                     
                     UNION  (
                        SELECT Id_Nota_Credito_Global AS Id , "Nota_Credito_Global" AS Tipo, Codigo, Fecha  FROM Nota_Credito_Global 
                    )
                ) N
            ORDER BY N.Fecha # DESC
        ';

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$data = $oCon->getData();
unset($oCon);
//echo '<pre>';
//var_dump($data);
//exit;
$cod = 394;
$x=0;
$y=0;
foreach ($data as $nota) {
    $codAnterior = $nota['Codigo'];
    
    //actualizar nota
    $oItem = new complex($nota['Tipo'],'Id_'.$nota['Tipo'],$nota['Id']);
    $oItem->Codigo = 'NC'.$cod;
    //$oItem->save();
    unset($oItem);
    
    //actualizar movimientos contables
    $query = 'UPDATE Movimiento_Contable 
                SET 
                    Numero_Comprobante = "NC'.$cod.'" ,
                     Documento = "NC'.$cod.'"
                WHERE Numero_Comprobante = "'.$codAnterior.'"';
   // echo $query;
    $oCon = new consulta();
    $oCon->setQuery($query);
   // $data = $oCon->createData();
    unset($oCon);

    echo ' Cod Anterior : '.$codAnterior.'<br> Cod Nuevo : NC'.$cod.'<br><br>';
    $cod++;


    if ($x==200) {
   
       // $logFile = fopen('prueba.txt','w') or die("Error creando archivo");;
        //fwrite($logFile,$y);
       // $y++;
        sleep(5);
        $x=0;
    }

    $x++; 
                
}

