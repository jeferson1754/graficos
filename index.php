<?php

require '../bd.php';

// Verificar si no hay registros con Repeticion = 0 en 'op'
$sql = "SELECT COUNT(*) AS Count FROM op WHERE Repeticion = 0";
$result = mysqli_query($conexion, $sql);
$row = mysqli_fetch_assoc($result);

if ($row['Count'] == 0) {
    // Reiniciar repetición en 'op'
    $updateSql = "UPDATE op SET Repeticion = 0 WHERE Repeticion = 1";
    mysqli_query($conexion, $updateSql);
}

// Verificar si no hay registros con Repeticion = 0 en 'ed'
$sql = "SELECT COUNT(*) AS Count FROM ed WHERE Repeticion = 0";
$result = mysqli_query($conexion, $sql);
$row = mysqli_fetch_assoc($result);

if ($row['Count'] == 0) {
    // Reiniciar repetición en 'ed'
    $updateSql = "UPDATE ed SET Repeticion = 0 WHERE Repeticion = 1";
    mysqli_query($conexion, $updateSql);
}

//-----------GRAFICO CIRCULAR---------------
$consulta = "SELECT 'op' AS Tipo, COUNT(*) AS Recuento FROM op
             UNION ALL
             SELECT 'ed' AS Tipo, COUNT(*) AS Recuento FROM ed";
$resultado = $conexion->query($consulta);
// Verificar si hay resultados
if ($resultado) {
    // Guardar resultados en variables asociativas
    $resultados = $resultado->fetch_all(MYSQLI_ASSOC);
    // Obtener los resultados por tipo
    $opResult = $resultados[0]['Recuento'] ?? 0;
    $edResult = $resultados[1]['Recuento'] ?? 0;
    // Liberar memoria del resultado
    $resultado->free();
} else {
    // Manejar errores si es necesario
    $opResult = 0;
    $edResult = 0;
}

$totalResult = $opResult + $edResult;

//-----------GRAFICO CIRCULAR GRANDE---------------

$consulta1 = "SELECT autor.Autor, autor.Canciones AS CantidadRepeticiones 
FROM autor WHERE autor.ID != 1 
GROUP BY autor.ID, autor.Autor 
ORDER BY CantidadRepeticiones DESC LIMIT 15";

$resultado1 = $conexion->query($consulta1);

$resultados1 = ($resultado1) ? $resultado1->fetch_all(MYSQLI_ASSOC) : array();

// Variables individuales para los 10 primeros resultados
for ($i = 1; $i <= 15; $i++) {
    ${"autor" . $i} = isset($resultados1[$i - 1]) ? $resultados1[$i - 1]['Autor'] : '';
    ${"repeticiones" . $i} = isset($resultados1[$i - 1]) ? $resultados1[$i - 1]['CantidadRepeticiones'] : 0;
}

// Liberar memoria del resultado
if ($resultado1) {
    $resultado1->free();
}

//-----------GRAFICO DE AREA ---------------

// Función para ejecutar consulta y construir el array de datos
function fetchData($conexion, $tabla)
{
    $sql = "SELECT CONCAT(YEAR(Fecha_Ingreso), '-', WEEK(Fecha_Ingreso)+1,' Semana') AS Semana, COUNT(*) AS RecuentoSemana FROM $tabla WHERE YEARWEEK(Fecha_Ingreso) BETWEEN YEARWEEK(CURDATE() - INTERVAL 4 WEEK) AND YEARWEEK(CURDATE()) GROUP BY Semana ORDER BY Semana";

    // Ejecutar la consulta
    $resultado = $conexion->query($sql);

    // Inicializar un array para almacenar los datos
    $data = array();

    // Procesar los resultados y construir el array
    while ($row = $resultado->fetch_assoc()) {
        $data[] = array(
            "Semana" => $row["Semana"],
            "RecuentoSemana" => $row["RecuentoSemana"]
        );
    }

    // Liberar memoria del resultado
    $resultado->free();

    return $data;
}


// Obtener datos para OP
$dataOp = fetchData($conexion, "op");
// Obtener datos para ED
$dataEd = fetchData($conexion, "ed");

// Ahora las dos listas tienen el mismo tamaño
$dataOpArray = [];
$dataEdArray = [];

foreach ($dataOp as $item) {
    $dataOpArray[] = $item['RecuentoSemana'];
    $dataOpArray[] = $item['Semana'];
}

foreach ($dataEd as $item) {
    $dataEdArray[] = $item['RecuentoSemana'];
    $dataEdArray[] = $item['Semana'];
}
/*
echo "DataOp: [" . implode(', ', $dataOpArray) . "]<br>";
echo "DataEd: [" . implode(', ', $dataEdArray) . "]<br>";
*/




// Crear un mapa para dataEd con clave como la fecha y valor como el número antes de la fecha
$edMap = array();
for ($i = 0; $i < count($dataEdArray); $i += 2) {
    $edMap[$dataEdArray[$i + 1]] = $dataEdArray[$i];
}

// Inicializar el nuevo array para los resultados
$newDataEd = array();

// Recorrer dataOp, tomando las fechas y agregando el valor correspondiente de dataEd
for ($i = 1; $i < count($dataOpArray); $i += 2) {
    $semana = $dataOpArray[$i]; // Obtener la fecha (semana)

    if (array_key_exists($semana, $edMap)) {
        // Si la fecha está en el mapa de dataEd, obtener su valor
        $correspondingValue = $edMap[$semana];
    } else {
        // Si no está, el valor es cero
        $correspondingValue = 0;
    }

    // Agregar el valor correspondiente y la fecha al nuevo array
    $newDataEd[] = $correspondingValue;
    $newDataEd[] = $semana;
}

// Imprimir el resultado final
//echo "DataEd: [" . implode(", ", $newDataEd) . "]\n";

// Extraer solo los valores numéricos
$numValues = array_filter($newDataEd, 'is_numeric');

// Re-indexar el arreglo para que tenga índices consecutivos
$numValues = array_values($numValues);

//echo "Valores numéricos: [" . implode(", ", $numValues) . "]";



//-----------GRAFICO DE CASILLAS FALTANTES ---------------

// Consulta SQL
$consulta = "SELECT (SELECT COUNT(*) FROM op) + (SELECT COUNT(*) FROM ed) AS total_tablas;";
// Ejecutar la consulta
$resultado = $conexion->query($consulta);
// Obtener los resultados como un array asociativo
$resultados = $resultado->fetch_assoc();
$total_tablas = $resultados['total_tablas'];
// Liberar memoria del resultado
$resultado->free();


$consulta = "
    SELECT
        (SELECT COUNT(*) FROM op WHERE Cancion='') +
        (SELECT COUNT(*) FROM ed WHERE Cancion='') AS sinnombre,
        
        (SELECT COUNT(*) FROM op WHERE ID_Autor=1 OR ID_Autor='') +
        (SELECT COUNT(*) FROM ed WHERE ID_Autor=1 OR ID_Autor='') AS sinautor,
        
        (SELECT COUNT(*) FROM op WHERE Link='' OR Estado_Link='Faltante' OR Estado_link!='Correcto') +
        (SELECT COUNT(*) FROM ed WHERE Link='' OR Estado_Link='Faltante' OR Estado_link!='Correcto')  AS sinlink,
        
        (SELECT COUNT(*) FROM op WHERE Link_Iframe='') +
        (SELECT COUNT(*) FROM ed WHERE Link_Iframe='') AS sinifra;
";

$resultado = $conexion->query($consulta);

if ($resultado) {
    $resultados = $resultado->fetch_assoc();

    // Obtén los valores de cada categoría
    $sinnombre = $resultados['sinnombre'];
    $sinautor = $resultados['sinautor'];
    $sinlink = $resultados['sinlink'];
    $sinifra = $resultados['sinifra'];

    // Suma total
    $tablas_vacias = $sinnombre + $sinautor + $sinlink + $sinifra;

    // Liberar memoria del resultado
    $resultado->free();
} else {
    // Manejar errores si es necesario
    echo "Error en la consulta: " . $conexion->error;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="./css/style.css?<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/checkbox.css?<?php echo time(); ?>">
    <title>Graficos</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"><!--Iconos-->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script><!--Graficos de Area-->
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script><!--Graficos de Pie-->
</head>

<body>
    <?php

    // Consulta SQL
    $sql = "(SELECT * FROM op WHERE Repeticion = 0 ORDER BY RAND() LIMIT 5)
    UNION ALL
    (SELECT * FROM ed WHERE Repeticion = 0 ORDER BY RAND() LIMIT 5)
    ORDER BY RAND()
    LIMIT 1;
    ";

    //echo $sql;
    // Ejecutar la consulta
    $result = $conexion->query($sql);

    // Verificar si hay resultados
    if ($result->num_rows > 0) {
        // Obtener los datos
        $row = $result->fetch_assoc();
        $idRegistros = $row['ID'];

        $sql1 = "SELECT * FROM `op` WHERE ID='$row[ID]'";
        $result1 = $conexion->query($sql1);
        $fila = $result1->fetch_assoc();

        $sql2 = "SELECT * FROM `autor` WHERE ID='$row[ID_Autor]'";
        $result2 = $conexion->query($sql2);
        $columna = $result2->fetch_assoc();
        //echo "" . $columna["Autor"] . "</br>";

        echo "<h1 class='hover-text'>";

        if ($fila["ID_Anime"] == $row["ID_Anime"] && $fila["Link"] == $row["Link"]) {
            echo "<span class='visible-text'>" . $row["Nombre"] . " - OP " . $row["Opening"] . "</span>";
        } else {
            echo "<span class='visible-text'>" . $row["Nombre"] . " - ED " . $row["Opening"] . "</span>";
        }

        if ($row["Link"] == NULL) {
            echo "<a  href=" . $row["Link"] . " target='_blanck'>";
            if ($columna["Autor"] != "") {
                echo "<span style='color:red' class='hidden-text'>" . $row["Cancion"] . " - " . $columna["Autor"] . "</span>";
            } else {
                echo "<span style='color:red' class='hidden-text'>" . $row["Cancion"] . "</span>";
            }
            echo "</a>";
        } else if ($row["Estado_Link"] != "Correcto") {
            echo "<a  href=" . $row["Link"] . " target='_blanck'>";
            if ($columna["Autor"] != "") {
                echo "<span style='color:purple' class='hidden-text'>" . $row["Cancion"] . " - " . $columna["Autor"] . "</span>";
            } else {
                echo "<span style='color:purple' class='hidden-text'>" . $row["Cancion"] . "</span>";
            }
            echo "</a>";
        } else {
            echo "<a  href=" . $row["Link"] . " target='_blanck'>";
            if ($columna["Autor"] != "") {
                echo "<span class='hidden-text'>" . $row["Cancion"] . " - " . $columna["Autor"] . "</span>";
            } else {
                echo "<span class='hidden-text'>" . $row["Cancion"] . "</span>";
            }
            echo "</a>";
        }

        echo "</h1>";
        if ($fila["ID_Anime"] == $row["ID_Anime"] && $fila["Link"] == $row["Link"]) {
            $sql4 = "UPDATE op SET Repeticion=1 WHERE ID='$idRegistros';";
            $result4 = mysqli_query($conexion, $sql4);
    ?>
            <div class="todo">
                <label class="contenedor">
                    <input type="checkbox" id="redireccionarCheckbox" name="op" unchecked>
                    <span class="text">El link esta mal?</span>
                    <div class="checkmark"></div>
                </label>
            </div>
        <?php
        } else {
            $sql4 = "UPDATE ed SET Repeticion=1 WHERE ID='$idRegistros';";
            $result4 = mysqli_query($conexion, $sql4);
        ?>
            <div class="todo">
                <label class="contenedor">
                    <input type="checkbox" id="redireccionarCheckbox2" name="ed" unchecked>
                    <span class="text">El link esta mal?</span>
                    <div class="checkmark"></div>
                </label>
            </div>
    <?php
        }
    } else {
        echo "No se encontraron resultados";
    }

    $sql3 = "SELECT * FROM `autor` ORDER BY `autor`.`ID` DESC limit 5;";
    $result3 = mysqli_query($conexion, $sql3);
    ?>

    <div class="container">
        <div class="content">
            <?php
            if ($row["Link_Iframe"] == "") {
                echo "<img src='' alt='Sin video'>";
            ?>
                <div class="tooltip-container">
                    <!-- Icono de información -->
                    <i class="fas fa-info-circle"></i>
                    <!-- Texto informativo -->
                    <span class="tooltip-text">
                        <!-- Cuadro de color rojo -->
                        <div class="color-box red"></div>: Sin link<br>
                        <!-- Cuadro de color azul -->
                        <div class="color-box purple"></div>: Estado Link no es Correcto
                </div>
            <?php
            } else {
                echo "<iframe src='" . $row["Link_Iframe"] . "' frameborder='0' allow='clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share' allowfullscreen></iframe>";
            }
            ?>


        </div>
    </div>

    <div class="container">
        <div id="pie"></div>
        <canvas id="areaChart"></canvas>
    </div>

    <div class="container">

        <table>
            <thead>
                <th>Ultimos Artistas:</th>
            </thead>
            <tbody>
                <?php
                while ($mostrar = mysqli_fetch_array($result3)) {
                    echo "<tr>";
                    echo "<td>" . $mostrar['Autor'] . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
        <div id="main"></div>
    </div>

    <div class="container">
        <div id="pie2"></div>
    </div>


    <script type="text/javascript">
        //Grafico Circular
        var myChart = echarts.init(document.getElementById('pie'));
        <?php
        // Supongamos que $opResult y $edResult son variables con los resultados deseados
        // Datos para la serie del gráfico
        $data = [
            ['value' => $opResult, 'name' => 'Openings'],
            ['value' => $edResult, 'name' => 'Endings'],
        ];

        // Configuración del gráfico
        $option = [
            'title' => [
                'text' => 'Openings vs. Endings',
                'left' => 'center',
                'top' => 0
            ],
            'tooltip' => [
                'trigger' => 'item',
                'formatter' => '{a} <br/>{b}: {c} ({d}%)'
            ],
            'series' => [
                [
                    'name' => 'Cantidad de',
                    'type' => 'pie',
                    'radius' => '65%',
                    'data' => $data,
                    'emphasis' => [
                        'itemStyle' => [
                            'shadowBlur' => 10,
                            'shadowOffsetX' => 0,
                            'shadowColor' => 'rgba(0, 0, 0, 0.5)'
                        ]
                    ],
                    'label' => [
                        'show' => true,
                        'formatter' => '{b}: {d}%'
                    ],
                ]
            ]
        ];

        // Convertir a JSON
        $jsonOption = json_encode($option, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        ?>

        var option = <?php echo $jsonOption; ?>;

        myChart.setOption(option);

        //Grafico de Area
        var ctx = document.getElementById('areaChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [<?php
                            foreach ($dataOp as $item) {
                                echo "'" . $item['Semana'] . "', ";
                            }
                            ?>],
                datasets: [{
                    label: 'Cant. de Openings',
                    data: [<?php
                            foreach ($dataOp as $item) {
                                echo $item['RecuentoSemana'] . ", ";
                            }
                            ?>],
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    fill: true,
                }, {
                    label: 'Cant. de Endings',
                    data: <?php
                            echo "[" . implode(", ", $numValues) . "]";
                            ?>,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1,
                    fill: true,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });





        //Grafico Circular Grande
        var myChart2 = echarts.init(document.getElementById('main'));
        option = {
            title: {
                text: 'Artistas Más Repetidos',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },

            series: [{
                type: 'pie',
                radius: [50, 200],
                center: ['50%', '62%'],
                roseType: 'area',
                itemStyle: {
                    borderRadius: 0
                },
                emphasis: {
                    itemStyle: {
                        shadowBlur: 10,
                        shadowOffsetX: 0,
                        shadowColor: 'rgba(0, 0, 0, 0.5)'
                    }
                },
                data: [
                    <?php
                    $dataArray = array(
                        array('value' => $repeticiones1, 'name' => $autor1),
                        array('value' => $repeticiones2, 'name' => $autor2),
                        array('value' => $repeticiones3, 'name' => $autor3),
                        array('value' => $repeticiones4, 'name' => $autor4),
                        array('value' => $repeticiones5, 'name' => $autor5),
                        array('value' => $repeticiones6, 'name' => $autor6),
                        array('value' => $repeticiones7, 'name' => $autor7),
                        array('value' => $repeticiones8, 'name' => $autor8),
                        array('value' => $repeticiones9, 'name' => $autor9),
                        array('value' => $repeticiones10, 'name' => $autor10),
                        array('value' => $repeticiones11, 'name' => $autor11),
                        array('value' => $repeticiones12, 'name' => $autor12),
                        array('value' => $repeticiones13, 'name' => $autor13),
                        array('value' => $repeticiones14, 'name' => $autor14),
                        array('value' => $repeticiones15, 'name' => $autor15)
                    );

                    foreach ($dataArray as $item) {
                        echo '{';
                        echo 'value: ' . $item['value'] . ',';
                        echo "name: '{$item['name']}'";
                        echo '},';
                    }
                    ?>

                ]
            }]
        };
        myChart2.setOption(option);

        var myChart3 = echarts.init(document.getElementById('pie2'));
        <?php
        // Supongamos que $opResult y $edResult son variables con los resultados deseados

        // Datos para la serie del gráfico
        $data1 = [
            ['value' => $tablas_vacias, 'name' => 'Faltantes'],
            ['value' => $total_tablas, 'name' => 'Total'],
        ];

        // Configuración del gráfico
        $option1 = [
            'title' => [
                'text' => 'Casillas Faltantes',
                'left' => 'center',
                'top' => 0
            ],
            'tooltip' => [
                'trigger' => 'item',
                'formatter' => '{a} <br/>{b}: {c} ({d}%)',
            ],
            'series' => [
                [

                    'name' => 'Cantidad de Casillas',
                    'type' => 'pie',
                    'radius' => '65%',
                    'data' => $data1,
                    'emphasis' => [
                        'itemStyle' => [
                            'shadowBlur' => 10,
                            'shadowOffsetX' => 0,
                            'shadowColor' => 'rgba(0, 0, 0, 0.5)'
                        ]
                    ],
                    'label' => [
                        'show' => true,
                        'formatter' => '{b}: {d}%'
                    ],

                ]
            ]
        ];

        // Convertir a JSON
        $jsonOption1 = json_encode($option1, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        ?>

        var option1 = <?php echo $jsonOption1; ?>;

        myChart3.setOption(option1);

        //Redirección con Checkbox

        // Primera función
        document.addEventListener('DOMContentLoaded', function() {
            // Obtener el checkbox
            var checkbox = document.getElementById('redireccionarCheckbox');

            // Agregar un listener para el evento de cambio
            checkbox.addEventListener('change', function() {
                // Verificar si el checkbox está marcado
                if (this.checked) {
                    // Redireccionar a otra página
                    window.location.href = 'update_checkbox_op.php?variable=<?php echo urlencode($idRegistros); ?>'; // Cambia esta URL por la que desees
                }
            });
        });

        // Segunda función (con nombre diferente)
        document.addEventListener('DOMContentLoaded', function() {
            // Obtener el checkbox
            var checkbox = document.getElementById('redireccionarCheckbox2');

            // Agregar un listener para el evento de cambio
            checkbox.addEventListener('change', function() {
                // Verificar si el checkbox está marcado
                if (this.checked) {
                    // Redireccionar a otra página
                    window.location.href = 'update_checkbox_ed.php?variable=<?php echo urlencode($idRegistros); ?>';
                }
            });
        });
    </script>
</body>

</html>