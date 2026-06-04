<?php
// 1. Carga de dependencias
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Inicialización de variables de estado y datos
$tabla_pg = [];
$documentos_mg = [];
$error_pg = "";
$error_mg = "";
$estado_pg = false;
$estado_mg = false;

// URI de conexión oficial a MongoDB Atlas
$uri_mongo = 'mongodb+srv://juandiegoguasca0_db_user:0WJGyl5OAMtz33rP@cluster0.8ntt77g.mongodb.net/?appName=Cluster0';

try {
    // ==========================================
    // FASE 1: INSERCIÓN EN POSTGRESQL (RENDER)
    // ==========================================
    $conexion_pg = new PDO('pgsql:host=dpg-d8f3edeq1p3s73dgojkg-a.oregon-postgres.render.com;dbname=sena_6eak','sena_6eak_user','DlJt2UPz4jFSE9Uk6gmz7vS27rva6yGG');
    $conexion_pg->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql_pg = "INSERT INTO aprendices (tipo_documento, numero_documento, nombre, correo_electronico, telefono, numero_ficha, programa_formacion, jornada, estado, detalles, nota1, nota2, nota3) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $registrar_pg = $conexion_pg->prepare($sql_pg);
    $registrar_pg->execute([
        $_POST["tipo_doc"] ?? '', $_POST["num_doc"] ?? '', $_POST["nom"] ?? '', $_POST["correo"] ?? '', 
        $_POST["tel"] ?? '', $_POST["ficha"] ?? '', $_POST["programa"] ?? '', $_POST["jornada"] ?? '', 
        $_POST["estado"] ?? '', $_POST["det"] ?? '', $_POST["nota1"] ?? 0, $_POST["nota2"] ?? 0, $_POST["nota3"] ?? 0
    ]);
    $estado_pg = true;

    // ==========================================
    // FASE 2: RESPALDO EN MONGODB ATLAS
    // ==========================================
    // Validamos que Render tenga la extensión instalada antes de ejecutar
    if (extension_loaded('mongodb')) {
        try {
            $cliente_mg = new MongoDB\Client($uri_mongo);
            $coleccion_mg = $cliente_mg->sena_db->respaldo_aprendices;

            // Estructura anidada aprobada para la base de datos documental
            $documento = [
                "identificacion" => [
                    "tipo" => $_POST["tipo_doc"] ?? '',
                    "numero" => $_POST["num_doc"] ?? ''
                ],
                "datos_personales" => [
                    "nombre" => $_POST["nom"] ?? '',
                    "contacto" => [
                        "telefono" => $_POST["tel"] ?? '',
                        "correo" => $_POST["correo"] ?? ''
                    ]
                ],
                "academico" => [
                    "ficha" => $_POST["ficha"] ?? '',
                    "programa" => $_POST["programa"] ?? '',
                    "jornada" => $_POST["jornada"] ?? '',
                    "estado" => $_POST["estado"] ?? ''
                ],
                "calificaciones" => [
                    "nota1" => (float)($_POST["nota1"] ?? 0),
                    "nota2" => (float)($_POST["nota2"] ?? 0),
                    "nota3" => (float)($_POST["nota3"] ?? 0)
                ],
                "observaciones" => $_POST["det"] ?? '',
                "fecha_respaldo" => new MongoDB\BSON\UTCDateTime()
            ];

            if ($coleccion_mg->insertOne($documento)->getInsertedCount() == 1) {
                $estado_mg = true;
            }
        } catch (Exception $e) {
            $error_mg = "Detalle técnico MongoDB: " . $e->getMessage();
        }
    } else {
        $error_mg = "La extensión de MongoDB no está activa en el servidor de Render.";
    }

    // ==========================================
    // FASE 3: CONSULTAS DE VERIFICACIÓN
    // ==========================================
    
    // Consulta PostgreSQL
    $consulta_pg = $conexion_pg->query("SELECT * FROM aprendices ORDER BY id DESC");
    $tabla_pg = $consulta_pg->fetchAll(PDO::FETCH_ASSOC);

    // Consulta MongoDB (Solo si la conexión fue instanciada)
    if (isset($coleccion_mg)) {
        try {
            $documentos_mg = $coleccion_mg->find([], ['sort' => ['_id' => -1]]);
        } catch (Exception $e) {
            $error_mg = "Error al recuperar documentos: " . $e->getMessage();
        }
    }

} catch (Exception $e) {
    // Captura errores críticos de conexión de Postgres
    $error_pg = "Error crítico Postgres: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen de Persistencia - SENA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<?php if ($estado_pg && $estado_mg): ?>
    <div class="alert alert-success shadow-sm"><strong>¡Doble Inserción Exitosa!</strong> Datos guardados en PostgreSQL y respaldados en MongoDB Atlas.</div>
<?php elseif ($estado_pg && !$estado_mg): ?>
    <div class="alert alert-warning shadow-sm">
        <strong>Inserción Parcial:</strong> Guardado en Postgres, pero falló el respaldo en MongoDB.<br>
        <small><?php echo htmlspecialchars($error_mg ?? 'Error desconocido en MongoDB.'); ?></small>
    </div>
<?php else: ?>
    <div class="alert alert-danger shadow-sm"><strong>Error general:</strong> <?php echo htmlspecialchars($error_pg ?? 'Error desconocido de conexión.'); ?></div>
<?php endif; ?>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card border-primary shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">🐘 PostgreSQL (Relacional)</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-sm text-center mb-0">
                    <thead class="table-primary">
                        <tr><th>Ficha</th><th>Nombre</th><th>Promedio</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($tabla_pg as $f): ?>
                        <tr>
                            <td><?= htmlspecialchars($f['numero_ficha'] ?? '') ?></td>
                            <td class="text-start"><?= htmlspecialchars($f['nombre'] ?? '') ?></td>
                            <td class="fw-bold text-primary">
                                <?php 
                                    $n1 = $f['nota1'] ?? 0;
                                    $n2 = $f['nota2'] ?? 0;
                                    $n3 = $f['nota3'] ?? 0;
                                    echo number_format(($n1 + $n2 + $n3) / 3, 1); 
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-success shadow">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">🍃 MongoDB Atlas (Documental)</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-sm text-center mb-0">
                    <thead class="table-success">
                        <tr><th>ID BSON</th><th>Nombre</th><th>Ficha</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($documentos_mg as $d): ?>
                        <tr>
                            <td class="font-monospace text-success"><?= substr((string)($d['_id'] ?? ''), -8) ?></td>
                            <td class="text-start"><?= htmlspecialchars($d['datos_personales']['nombre'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($d['academico']['ficha'] ?? 'N/A') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="text-center mt-4 mb-5">
    <a href="../index.html" class="btn btn-dark px-5 py-2">Volver al Formulario</a>
</div>

</body>
</html>