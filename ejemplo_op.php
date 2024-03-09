<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ejemplo Iframe</title>
  <!-- Bootstrap CSS -->
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    #iframe-container {
      width: 100%;
      /* Ancho completo */
      max-width: 800px;
      /* Máximo ancho de 800px */
      margin: 0 auto;
      /* Centrar horizontalmente */
      padding: 20px;
      /* Espacio alrededor del iframe */
    }

    #iframe-container iframe {
      width: 100%;
      /* Ancho completo */
      height: 500px;
      /* Altura fija de 600px */
      border: 1px solid #ccc;
      /* Borde del iframe */
      border-radius: 5px;
      /* Bordes redondeados */
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      /* Sombra ligera */
    }
  </style>
</head>

<?php
require '../bd.php';

// Consulta SQL para obtener los nombres y enlaces de iframe de la tabla op
$sql = "SELECT Nombre, Link_Iframe, Opening FROM op WHERE Link_Iframe != '' ORDER BY `op`.`ID` DESC";
$result = $conexion->query($sql);

// Creamos un array asociativo para almacenar los nombres, enlaces de iframe y openings
$enlaces_iframe = [];

// Llenamos el array con los resultados de la consulta
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $enlaces_iframe[$row['Nombre']] = ['Link' => $row['Link_Iframe'], 'Opening' => $row['Opening']];
  }
}


// Cerrar conexión
$conexion->close();
?>

<body>
  <div class="container mt-5">
    <div class="form-group">
      <label for="linkInput">Ingresa un enlace:</label>
      <input type="text" name="linkInput" id="linkInput" list="open" class="form-control" placeholder="https://example.com" required>
      <datalist id="open">
        <?php
        // Imprimir las opciones del datalist con los nombres y enlaces de iframe
        foreach ($enlaces_iframe as $nombre => $info) {
          echo "<option value='" . $nombre . " OP " . $info['Opening'] . " - " . $info['Link'] . " '></option>";
        }

        ?>
      </datalist>
    </div>
    <button onclick="mostrarEnlace()" class="btn btn-primary">Enviar</button>
  </div>
  <div id="iframe-container">
    <iframe id="miIframe" width="800" height="600" src="" frameborder="0"></iframe>
  </div>
  <script>
    function mostrarEnlace() {
      var linkInput = document.getElementById('linkInput').value;
      var enlaceParts = linkInput.split(" - ");
      var enlace = enlaceParts[1]; // Tomar solo el enlace del iframe
      // Establecer la fuente del iframe con el enlace seleccionado
      document.getElementById('miIframe').src = enlace;
    }
  </script>
</body>


</html>