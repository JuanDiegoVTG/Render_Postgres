<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Doble Persistencia - SENA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<?php
// 1. Requerir el Autoloader de Composer para que PHP reconozca a MongoDB
require_once __DIR__ . '/../vendor/autoload.php';

// Variables de estado
$tabla_pg = [];
$estado_pg = false;
$estado_mg = false;
$error_detalle = "";

// Credenciales 
$uri_mongo = "mongodb+srv://juandiegoguasca0_db_user:200997@cluster0.8ntt77g.mongodb.net/?retryWrites=true&w=majority";

try {
    //FASE 1: INSERCIÓN EN POSTGRESQL (RENDER)
       
    $conexion_pg = new PDO('pgsql:host=dpg-d8f3edeq1p3s73dgojkg-a.oregon-postgres.render.com;dbname=sena_6eak','sena_6eak_user','DlJt2UPz4jFSE9Uk6gmz7vS27rva6yGG');
    $conexion_pg->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql_pg = "INSERT INTO aprendices (
                tipo_documento, numero_documento, nombre, correo_electronico, telefono, 
                numero_ficha, programa_formacion, jornada, estado, detalles, nota1, nota2, nota3
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $registrar_pg = $conexion_pg->prepare($sql_pg);
    $registrar_pg->execute([
        $_POST["tipo_doc"], $_POST["num_doc"], $_POST["nom"], $_POST["correo"], 
        $_POST["tel"], $_POST["ficha"], $_POST["programa"], $_POST["jornada"], 
        $_POST["estado"], $_POST["det"], $_POST["nota1"], $_POST["nota2"], $_POST["nota3"]
    ]);
    
    $estado_pg = true; // Si llegó hasta aquí sin fallar, Postgres fue un éxito

    //FASE 2: INSERCIÓN DE RESPALDO EN MONGODB ATLAS
       
    try {
        $cliente_mg = new MongoDB\Client($uri_mongo);
        $coleccion_mg = $cliente_mg->sena_db->respaldo_aprendices; // Crea la DB y Colección automáticamente

        // Creamos un arreglo asociativo (Documento BSON) con los mismos datos
        $documento = [
            "identificacion" => [
                "tipo" => $_POST["tipo_doc"],
                "numero" => $_POST["num_doc"]
            ],
            "datos_personales" => [
                "nombre" => $_POST["nom"],
                "contacto" => [
                    "telefono" => $_POST["tel"],
                    "correo" => $_POST["correo"]
                ]
            ],
            "academico" => [
                "ficha" => $_POST["ficha"],
                "programa" => $_POST["programa"],
                "jornada" => $_POST["jornada"],
                "estado" => $_POST["estado"]
            ],
            "calificaciones" => [
                "nota1" => (float)$_POST["nota1"],
                "nota2" => (float)$_POST["nota2"],
                "nota3" => (float)$_POST["nota3"]
            ],
            "observaciones" => $_POST["det"],
            "fecha_respaldo" => new MongoDB\BSON\UTCDateTime()
        ];

        $resultado_mg = $coleccion_mg->insertOne($documento);
        if ($resultado_mg->getInsertedCount() == 1) {
            $estado_mg = true; // Si insertó 1 documento, Mongo fue un éxito
        }
    } catch (Exception $e) {
        $error_detalle .= " Error en MongoDB: " . $e->getMessage();
    }

    //FASE 3: VALIDACIÓN Y ALERTAS

    if ($estado_pg && $estado_mg) {
        echo "<div class='alert alert-success shadow-sm' role='alert'>
                <h4 class='alert-heading'>¡Doble Inserción Exitosa!</h4>
                <p class='mb-0'>El aprendiz fue guardado en <strong>PostgreSQL</strong> y respaldado en <strong>MongoDB Atlas</strong> correctamente.</p>
              </div>";
    } elseif ($estado_pg && !$estado_mg) {
        echo "<div class='alert alert-warning shadow-sm' role='alert'>
                <h4 class='alert-heading'>Inserción Parcial</h4>
                <p class='mb-0'>Guardado en PostgreSQL correctamente, pero <strong>falló el respaldo en MongoDB</strong>. $error_detalle</p>
              </div>";
    }

    //FASE 4: CONSULTAS DE VERIFICACIÓN
     
    
    // Consultar PostgreSQL
    $consulta_pg = $conexion_pg->prepare("SELECT * FROM aprendices ORDER BY id DESC");
    $consulta_pg->execute();
    $tabla_pg = $consulta_pg->fetchAll(PDO::FETCH_ASSOC);

    // Consultar MongoDB
    $documentos_mg = $coleccion_mg->find([], ['sort' => ['_id' => -1]]);

} catch (PDOException $e) {
    echo "<div class='alert alert-danger shadow-sm'><strong>Error Crítico Postgres:</strong> " . $e->getMessage() . "</div>";
}
?>

<div class="row mt-4">
    
    <div class="col-md-6">
        <div class="card border-primary shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">🐘 Servidor PostgreSQL (Render)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm text-center mb-0" style="font-size: 0.85rem;">
                        <thead class="table-primary">
                            <tr>
                                <th>Ficha</th>
                                <th>Nombre</th>
                                <th>Promedio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tabla_pg as $fila): ?>
                                <?php $prom = number_format(($fila['nota1'] + $fila['nota2'] + $fila['nota3'])/3, 1); ?>
                                <tr>
                                    <td><?php echo $fila['numero_ficha']; ?></td>
                                    <td class="text-start"><?php echo $fila['nombre']; ?></td>
                                    <td class="fw-bold text-primary"><?php echo $prom; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-success shadow">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">🍃 MongoDB Atlas (Cloud)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm text-center mb-0" style="font-size: 0.85rem;">
                        <thead class="table-success">
                            <tr>
                                <th>Object_ID BSON</th>
                                <th>Nombre</th>
                                <th>Ficha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($documentos_mg as $doc): ?>
                                <tr>
                                    <td class="font-monospace text-success"><?php echo (string)$doc['_id']; ?></td>
                                    <td class="text-start"><?php echo $doc['datos_personales']['nombre']; ?></td>
                                    <td><?php echo $doc['academico']['ficha']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="text-center mt-4 mb-5">
    <a href="../index.html" class="btn btn-dark px-5 py-2">Volver al Formulario</a>
</div>

</body>
</html>