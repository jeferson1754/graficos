
<?php

require '../bd.php';

if (isset($_GET['variable'])) {
    $idRegistros = urldecode($_GET['variable']);
    //echo "La variable recibida es: " . $variable;
}

try {
    $conn = new PDO("mysql:host=$servidor;dbname=$basededatos", $usuario, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql_update_op = "UPDATE `op` SET `Estado_Link` = 'Erroneo/Inexistente' WHERE `op`.`ID` = $idRegistros";
    $conn->exec($sql_update_op);
    //echo $sql_update_op;
    $conn = null;
} catch (PDOException $e) {
    $conn = null;
}

//Redirigir a la página index.html
header("Location: index.php");
exit();
