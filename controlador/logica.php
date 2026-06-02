<?php
// 1. Carga de dependencias
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Inicialización de variables para evitar errores "Undefined"
$tabla_pg = [];
$documentos_mg = [];
$error_pg = "";
$error_mg = "";
$estado_pg = false;
$estado_mg = false;

// Credenciales (Asegúrate de que la URI tenga tu contraseña real)
$uri_mongo = "mongodb+srv://juandiegoguasca0_db_user:200997@cluster0.8ntt77g.mongodb.net/?retryWrites=true&w=majority";

try {
    // FASE 1: INSERCIÓN EN POSTGRESQL
    $conexion_pg = new PDO('pgsql:host=dpg-d8f3edeq1p3s73dgojkg-a.oregon-postgres.render.com;dbname=sena_6eak','sena_6eak_user','DlJt2UPz4jFSE9Uk6gmz7vS27rva6yGG');
    $conexion_pg->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql_pg = "INSERT INTO aprendices (tipo_documento, numero_documento, nombre, correo_electronico, telefono, numero_ficha, programa_formacion, jornada, estado, detalles, nota1, nota2, nota3) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $registrar_pg = $conexion_pg->prepare($sql_pg);
    $registrar_pg->execute([
        $_POST["tipo_doc"], $_POST["num_doc"], $_POST["nom"], $_POST["correo"], 
        $_POST["tel"], $_POST["ficha"], $_POST["programa"], $_POST["jornada"], 
        $_POST["estado"], $_POST["det"], $_POST["nota1"], $_POST["nota2"], $_POST["nota3"]
    ]);
    $estado_pg = true;

    // FASE 2: RESPALDO EN MONGODB (Solo si existe la extensión)
    if (extension_loaded('mongodb')) {
        try {
            $cliente_mg = new MongoDB\Client($uri_mongo);
            $coleccion_mg = $cliente_mg->sena_db->respaldo_aprendices;

            $documento = [
                "datos_personales" => ["nombre" => $_POST["nom"], "contacto" => ["tel" => $_POST["tel"], "mail" => $_POST["correo"]]],
                "academico" => ["ficha" => $_POST["ficha"], "prog" => $_POST["programa"], "jornada" => $_POST["jornada"]],
                "calificaciones" => ["n1" => (float)$_POST["nota1"], "n2" => (float)$_POST["nota2"], "n3" => (float)$_POST["nota3"]],
                "fecha_respaldo" => new MongoDB\BSON\UTCDateTime()
            ];

            if ($coleccion_mg->insertOne($documento)->getInsertedCount() == 1) {
                $estado_mg = true;
            }
        } catch (Exception $e) {
            $error_mg = $e->getMessage();
        }
    }

    // FASE 3: CONSULTAS DE VERIFICACIÓN
    $consulta_pg = $conexion_pg->query("SELECT * FROM aprendices ORDER BY id DESC");
    $tabla_pg = $consulta_pg->fetchAll(PDO::FETCH_ASSOC);

    if (isset($coleccion_mg)) {
        $documentos_mg = $coleccion_mg->find([], ['sort' => ['_id' => -1]]);
    }

} catch (Exception $e) {
    $error_pg = $e->getMessage();
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
    <div class="alert alert-success"><strong>¡Éxito!</strong> Datos guardados en Postgres y respaldados en MongoDB.</div>
<?php elseif ($estado_pg): ?>
    <div class="alert alert-warning">Guardado en Postgres, pero falló MongoDB: <?php echo $error_mg; ?></div>
<?php else: ?>
    <div class="alert alert-danger">Error: <?php echo $error_pg; ?></div>
<?php endif; ?>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card border-primary shadow">
            <div class="card-header bg-primary text-white">🐘 PostgreSQL (Relacional)</div>
            <table class="table table-sm mb-0">
                <thead class="table-primary"><tr><th>Ficha</th><th>Nombre</th><th>Promedio</th></tr></thead>
                <tbody>
                    <?php foreach($tabla_pg as $f): ?>
                    <tr><td><?= $f['numero_ficha'] ?></td><td><?= $f['nombre'] ?></td><td><?= number_format(($f['nota1']+$f['nota2']+$f['nota3'])/3, 1) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-success shadow">
            <div class="card-header bg-success text-white">🍃 MongoDB (Documental)</div>
            <table class="table table-sm mb-0">
                <thead class="table-success"><tr><th>ID BSON</th><th>Nombre</th><th>Ficha</th></tr></thead>
                <tbody>
                    <?php foreach($documentos_mg as $d): ?>
                    <tr><td><?= substr((string)$d['_id'], -8) ?></td><td><?= $d['datos_personales']['nombre'] ?></td><td><?= $d['academico']['ficha'] ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="text-center mt-4"><a href="../index.html" class="btn btn-dark">Volver</a></div>
</body>
</html>