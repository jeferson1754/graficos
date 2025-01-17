<!DOCTYPE html>
<html>

<head>
  <title>Índice de Archivos</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
  <h1>Índice de Archivos</h1>
  <ul>
    <?php
    // Ruta de la carpeta que deseas indexar
    $carpeta = './';

    // Escanea la carpeta y obtiene la lista de elementos
    $elementos = scandir($carpeta);

    // Itera a través de los elementos y genera enlaces con iconos
    foreach ($elementos as $elemento) {
      // Excluye las entradas "." y ".."
      if ($elemento != "." && $elemento != "..") {
        // Ruta completa al elemento
        $rutaCompleta = $carpeta . $elemento;

        // Verifica si el elemento es una carpeta
        if (is_dir($rutaCompleta)) {
          echo '<li><i class="fas fa-folder"></i> <a href="' . $rutaCompleta . '/">' . $elemento . '</a></li>';
        } elseif (is_file($rutaCompleta)) {
          // Verifica si el elemento es un archivo
          echo '<li><i class="fas fa-file"></i> <a href="' . $rutaCompleta . '">' . $elemento . '</a></li>';
        }
      }
    }
    ?>
  </ul>
</body>

</html>