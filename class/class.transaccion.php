<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/config/start.inc.php');
include_once('class.lista.php');
include_once('class.complex.php');
include_once('class.consulta.php');
include_once('class.paginacion.php');

class Transaccion{	
    protected	$query      = 0,
                $begin       = '',
                $commit    = ""
                $resultado  = [];
    
}