<?php

function customUcWord($string) {

    $string =ucwords(strtolower($string));
    foreach (array('_') as $delimiter) {
      if (strpos($string, $delimiter)!==false) {
        $string =implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
      }
    }
    return $string;
}