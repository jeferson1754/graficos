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
    $sql = "SELECT CONCAT(YEAR(Fecha_Ingreso), '-', WEEK(Fecha_Ingreso)+1) AS Semana, COUNT(*) AS RecuentoSemana FROM $tabla WHERE YEARWEEK(Fecha_Ingreso) BETWEEN YEARWEEK(CURDATE() - INTERVAL 4 WEEK) AND YEARWEEK(CURDATE()) GROUP BY Semana ORDER BY Semana";

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


function obtenerUltimas4Semanas()
{
    $semanas = [];
    $fechaActual = new DateTime();

    // Obtener las últimas 4 semanas
    for ($i = 0; $i < 4; $i++) {
        // Obtener el año y la semana en formato '2025-02 Semana'
        $anio = $fechaActual->format('Y');
        $semana = sprintf('%02d', $fechaActual->format('W')); // Aseguramos que la semana tenga dos dígitos
        $semanas[] = "'" . $anio . '-' . $semana . ' Semana' . "'"; // Añadimos las comillas simples

        // Retrocedemos una semana
        $fechaActual->modify('-1 week');
    }

    // Invertir el arreglo para tener las semanas en orden ascendente
    return array_reverse($semanas);
}

// Llamada a la función
$ultimasSemanas = obtenerUltimas4Semanas();

function completarValoresSemanas($data, $inicio, $fin)
{
    $result = [];
    $dataAssoc = [];

    // Convertir los datos a un formato clave-valor (semana => valor)
    for ($i = 0; $i < count($data); $i += 2) {
        $dataAssoc[$data[$i + 1]] = $data[$i];
    }

    // Generar todas las semanas desde $inicio hasta $fin
    for ($week = $inicio; $week <= $fin; $week++) {
        $key = "2025-$week";
        $result[] = isset($dataAssoc[$key]) ? $dataAssoc[$key] : 0;
    }

    return $result;
}

// Calcular la semana actual
$currentDate = new DateTime();
$year = $currentDate->format('Y');
$currentWeek = (int)$currentDate->format('W'); // Número de semana actual

// Determinar las últimas 4 semanas
$inicioSemana = $currentWeek - 3; // Hace 3 semanas
$finSemana = $currentWeek;       // Semana actual

// Completar las semanas para DataOp y DataEd
$valoresDataOp = completarValoresSemanas($dataOpArray, $inicioSemana, $finSemana);
$valoresDataEd = completarValoresSemanas($dataEdArray, $inicioSemana, $finSemana);

// Formatear la salida
/*
echo "DataOp: [" . implode(", ", $valoresDataOp) . "]\n";
echo "DataEd: [" . implode(", ", $valoresDataEd) . "]\n";
*/


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

    <style>

    </style>
</head>

<body>
    <?php //include('../Anime/menu.php'); 
    ?>
    <header class="header">
        <?php

        // Consulta SQL
        $sql = "( SELECT op.*, CONCAT(anime.Nombre, ' ', op.Temporada) AS Nombre FROM op INNER JOIN anime ON op.ID_Anime = anime.id WHERE op.Repeticion = 0 ORDER BY RAND() LIMIT 5 ) UNION ALL ( SELECT ed.*, CONCAT(anime.Nombre, ' ', ed.Temporada) AS Nombre FROM ed INNER JOIN anime ON ed.ID_Anime = anime.id WHERE ed.Repeticion = 0 ORDER BY RAND() LIMIT 5 ) ORDER BY RAND() LIMIT 1;
    ";
        //echo $sql;
        // Ejecutar la consulta
        $result = $conexion->query($sql);

        // Verificar si hay resultados
        if ($result->num_rows > 0) {
            // Obtener los datos
            $row = $result->fetch_assoc();
            $idRegistros = $row['ID'];

            // Consultas
            $fila = $conexion->query("SELECT * FROM `op` WHERE ID='{$row['ID']}'")->fetch_assoc();
            $columna = $conexion->query("SELECT * FROM `autor` WHERE ID='{$row['ID_Autor']}'")->fetch_assoc();

            // Generar contenido
            echo "<h1 class='hover-text'>";
            $opEd = ($fila["ID_Anime"] == $row["ID_Anime"] && $fila["Link"] == $row["Link"]) ? "OP" : "ED";
            echo "<span class='visible-text'>{$row['Nombre']} - {$opEd} {$row['Opening']}</span>";

            $linkColor = $row["Link"] == NULL ? "red" : ($row["Estado_Link"] != "Correcto" ? "purple" : "inherit");

            // Si no hay canción, mostrar "Sin Canción"
            $cancion = $row["Cancion"] ? $row["Cancion"] : "Sin Canción";
            $autorInfo = $columna["Autor"] ? " - {$columna['Autor']}" : "";

            echo "<a href='{$row['Link']}' target='_blank'>
                    <span style='color:{$linkColor}' class='hidden-text'>{$cancion}{$autorInfo}</span>
                  </a>";

            echo "</h1>";

            // Actualización de base de datos y checkbox
            $tabla = ($opEd === "OP") ? "op" : "ed";
            $numero = ($opEd === "OP") ? "" : "2";
            $sql4 = "UPDATE {$tabla} SET Repeticion=1 WHERE ID='$idRegistros';";
            $conexion->query($sql4);

            echo "<div class='todo'>
                    <label class='contenedor'>
                        <input type='checkbox' id='redireccionarCheckbox{$numero}' name='{$tabla}' unchecked>
                        <span class='text'>¿El link está mal?</span>
                        <div class='checkmark'></div>
                    </label>
                  </div>";
        } else {
            echo "No se encontraron resultados";
        }


        $sql3 = "SELECT * FROM `autor` ORDER BY `autor`.`ID` DESC limit 10;";
        $result3 = mysqli_query($conexion, $sql3);

        $sql5 = "SELECT mix.ID, COUNT(op.Mix) AS MixCount FROM mix INNER JOIN op ON mix.ID = op.Mix GROUP BY mix.ID ORDER BY `mix`.`ID` DESC; ";
        $listas_op = mysqli_query($conexion, $sql5);


        $sql6 = "SELECT mix_ed.ID, COUNT(ed.Mix) AS MixCount FROM mix_ed INNER JOIN ed ON mix_ed.ID = ed.Mix GROUP BY mix_ed.ID ORDER BY `mix_ed`.`ID` DESC;";
        $listas_ed = mysqli_query($conexion, $sql6);
        ?>
    </header>

    <?php
    if ($row["Link_Iframe"] == "") {
        echo "";
    ?>

        <div class="container">
            <div class="content">
                <img src='' alt='Sin video'>

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
            </div>
        </div>
    <?php
    } else {
        echo "
            <section class='card video'>
        <div class='video-container'>
        <iframe src='" . $row["Link_Iframe"] . "' frameborder='0' allow='clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share' allowfullscreen></iframe>
          </div>
    </section>";
    }
    ?>

    <div class="horizontal-cards">

        <section class="card">
            <div id="pie" class="chart-container"></div>
        </section>

        <section class="card">
            <div id="stacked" class="chart-container"></div>
        </section>

    </div>

    <div class="horizontal-cards">

        <section class="card">
            <div class="table-scroll">
                <table class="stats-table table-primary">
                    <thead>
                        <tr>
                            <th>Listas de OP</th>
                            <th>Conteo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($mostrar = mysqli_fetch_array($listas_op)) {
                            echo "<tr>";
                            echo "<td>" . $mostrar['ID'] . "</td>";
                            echo "<td>" . $mostrar['MixCount'] . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="table-scroll">
                <table class="stats-table table-secondary">
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
            </div>
        </section>


        <section class="card">
            <div class="table-scroll">
                <table class="stats-table table-tertiary">
                    <thead>
                        <th>Listas de ED</th>
                        <th>Conteo</th>
                    </thead>
                    <tbody>
                        <?php
                        while ($mostrar = mysqli_fetch_array($listas_ed)) {
                            echo "<tr>";
                            echo "<td>" . $mostrar['ID'] . "</td>";
                            echo "<td>" . $mostrar['MixCount'] . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>


    </div>

    <div class="horizontal-cards">

        <section class="card">
            <div id="main" class="chart-container"></div>
        </section>

        <section class="card">
            <div id="pie2" class="chart-container"></div>
        </section>
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

        var option = <?php echo $jsonOption;
                        $color_rgb = "173, 223, 173";
                        ?>;

        myChart.setOption(option);

        //Grafico de Area
        var dom = document.getElementById('stacked');
        var myChart5 = echarts.init(dom, null, {
            renderer: 'canvas',
            useDirtyRect: false
        });
        var app = {};

        var option;

        option = {
            title: {
                text: 'OP y ED por Semana',
                left: 'center'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['Cant. de Openings', 'Cant. de Endings'],
                top: 25
            },
            grid: {
                bottom: '10%',
                containLabel: true
            },
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: [
                    <?php
                    echo implode(", ", $ultimasSemanas);
                    ?>
                ],
                axisLabel: {
                    rotate: 75, // Gira etiquetas si hay muchas semanas
                    interval: 0 // Muestra todas las etiquetas
                }
            },
            yAxis: {
                type: 'value',
                axisLabel: {
                    formatter: '{value}' // Mantiene valores numéricos simples
                }
            },
            series: [{
                    name: 'Cant. de Openings',
                    type: 'line',
                    areaStyle: {
                        color: 'rgba(<?php echo $color_rgb ?>, 0.4)' // Suaviza el fondo del área
                    },
                    lineStyle: {
                        color: 'rgba(<?php echo $color_rgb ?>, 2)',
                        width: 2
                    },
                    itemStyle: {
                        color: 'rgba(<?php echo $color_rgb ?>, 2)' // Cambia el color del triángulo
                    },
                    data: [
                        <?php
                        echo implode(", ", $valoresDataOp);
                        ?>
                    ]
                },
                {
                    name: 'Cant. de Endings',
                    type: 'line',
                    areaStyle: {
                        color: 'rgba(255, 99, 132, 0.4)' // Suaviza el fondo del área
                    },
                    lineStyle: {
                        color: 'rgba(255, 99, 132, 1)',
                        width: 2
                    },
                    itemStyle: {
                        color: 'rgba(255, 99, 132, 1)' // Cambia el color del triángulo
                    },
                    data: [
                        <?php
                        echo implode(", ", $valoresDataEd);
                        ?>
                    ]
                }

            ]
        };

        if (option && typeof option === 'object') {
            myChart5.setOption(option);
        }

        window.addEventListener('resize', myChart5.resize);


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
            ['value' => $sinnombre, 'name' => 'Sin Nombre', 'color' => 'rgba(255, 99, 132, 0.8)'], // Rojo
            ['value' => $sinautor, 'name' => 'Sin Autor', 'color' => 'rgba(54, 162, 235, 0.8)'],   // Azul
            ['value' => $sinlink, 'name' => 'Sin Link', 'color' => 'rgba(75, 192, 192, 0.8)'],    // Verde
            ['value' => $sinifra, 'name' => 'Sin Iframe', 'color' => 'rgba(153, 102, 255, 0.8)'], // Púrpura
            ['value' => $tablas_vacias, 'name' => 'Faltantes', 'color' => 'rgba(255, 206, 86, 0.8)'], // Amarillo
            ['value' => $total_tablas, 'name' => 'Total', 'color' => 'rgba(255, 159, 64, 0.8)'],  // Naranja
        ];

        // Ordena el array $data1 por el valor 'value' de menor a mayor
        usort($data1, function ($a, $b) {
            return $a['value'] <=> $b['value']; // Ordena de menor a mayor
        });

        // Configuración del gráfico
        $option1 = [
            'title' => [
                'text' => 'Casillas Faltantes',
                'left' => 'center',
                'top' => 0
            ],
            'tooltip' => [
                'trigger' => 'axis',
                'formatter' => '{a} <br/>{b}: {c}'
            ],
            'grid' => [
                'bottom' => '20%',
                // Da espacio al gráfico para la leyenda
            ],
            'xAxis' => [
                'type' => 'category',
                'data' => array_column($data1, 'name'),
                'axisLabel' => [
                    'rotate' => 45,
                    'fontSize' => 12
                ]
            ],
            'yAxis' => [
                'type' => 'value'
            ],
            'series' => [
                [
                    'name' => 'Cantidad de Casillas',
                    'type' => 'bar',
                    'data' => array_map(function ($item) {
                        return [
                            'value' => $item['value'],
                            'itemStyle' => ['color' => $item['color']]
                        ];
                    }, $data1),
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