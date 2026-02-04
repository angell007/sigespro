<?
  session_start();
  unset($_SESSION["autentificado"]); 
  unset($_SESSION["user"]);
  session_destroy();
  header("Location: ../../login");
  exit;
?>
