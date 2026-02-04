<?php

require_once('../../vendor/autoload.php');
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');
require_once('../../helper/response.php');

class makeConditions {

    static $conditions;

    public function makeConditionLike($dato, $columna)
    {
        try {
            if ($this->conditions != '') {
                $this->conditions.="AND $columna LIKE '%$dato%' ";
               }else{
                $this->conditions.="WHERE $columna LIKE '%$dato%'  ";
               }

               return $this->conditions;

        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }
    }
    
    public function makeConditionEqual($dato, $columna)
    {
        try {
            if ($this->conditions != '') {
                $this->conditions.="AND $columna = '$dato' ";
               }else{
                $this->conditions.="WHERE $columna = '$dato'  ";
               }

               return $this->conditions;

        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }
    }

    // public function SetConditions()
    // {
    //     try {
    //         foreach ($this->request as $key => $param) {
    //             if ($key != 'estado') {
    //                 $this->conditions = $this->makeConditionClass->makeConditionLike($param,  $this->modulo . '.' . ucfirst ($key) );
    //             }
    //             if ($key == 'estado') {
    //                 $this->conditions = $this->makeConditionClass->makeConditionEqual($param,  $this->modulo . '.' . ucfirst ($key) );
    //             }
    //         }
    //     } catch (\Throwable $th) {
    //         show(myerror($th->getMessage()));
    //     }
    // }

}

 