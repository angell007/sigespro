<?php

$file = fopen("file.txt", "a");

fwrite($file, "Añadimos línea 1 \n \n" . PHP_EOL);




fclose($file);
?>