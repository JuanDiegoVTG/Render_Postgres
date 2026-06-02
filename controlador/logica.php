<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Base de Datos de Aprendices</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<?php
try {
    // 1. Conexión a PostgreSQL en Render
    $conexion = new PDO('pgsql:host=dpg-d8f3edeq1p3s73dgojkg-a.oregon-postgres.render.com;dbname=sena_6eak','sena_6eak_user','DlJt2UPz4jFSE9Uk6gmz7vS27rva6yGG');
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Preparar inserción con TODOS los campos nuevos
    $sql = "INSERT INTO aprendices (
                tipo_documento, numero_documento, nombre, correo_electronico, telefono, 
                numero_ficha, programa_formacion, jornada, estado, detalles, nota1, nota2, nota3
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $registrar = $conexion->prepare($sql);
    
    // 3. Ejecutar inyectando los datos del POST
    $registrar->execute([
        $_POST["tipo_doc"], 
        $_POST["num_doc"], 
        $_POST["nom"], 
        $_POST["correo"], 
        $_POST["tel"], 
        $_POST["ficha"], 
        $_POST["programa"], 
        $_POST["jornada"], 
        $_POST["estado"], 
        $_POST["det"], 
        $_POST["nota1"], 
        $_POST["nota2"], 
        $_POST["nota3"]
    ]);

    echo "<div class='alert alert-success text-center shadow-sm' role='alert'>
            <h5 class='alert-heading mb-0'>¡Registro Exitoso en Render!</h5>
          </div>";

    // 4. Consultar todos los registros
    $consulta = $conexion->prepare("SELECT * FROM aprendices ORDER BY id DESC");
    $consulta->execute();
    $tabla = $consulta->fetchAll(PDO::FETCH_ASSOC);

    $conexion = null;

    // 5. Imprimir tabla responsiva
    echo "<div class='card shadow mt-4 border-0'>
            <div class='card-header bg-dark text-white'>
                <h5 class='mb-0'>Listado General de Aprendices</h5>
            </div>
            <div class='card-body p-0'>
                <div class='table-responsive'>
                    <table class='table table-striped table-hover align-middle text-center mb-0' style='font-size: 0.9rem;'>
                        <thead class='table-dark'>
                            <tr>
                                <th>ID</th>
                                <th>Documento</th>
                                <th>Nombre</th>
                                <th>Contacto</th>
                                <th>Académico</th>
                                <th>Estado</th>
                                <th>Notas (1|2|3)</th>
                                <th>Promedio</th>
                                <th>Registro</th>
                            </tr>
                        </thead>
                        <tbody>";

    foreach($tabla as $fila) {
        // Validación de notas y promedio
        $n1 = $fila['nota1'] ?? 0;
        $n2 = $fila['nota2'] ?? 0;
        $n3 = $fila['nota3'] ?? 0;
        $promedio = number_format(($n1 + $n2 + $n3) / 3, 1);
        $colorPromedio = ($promedio >= 3.0) ? 'text-success fw-bold' : 'text-danger fw-bold';

        // Colores para el estado
        $badgeColor = 'bg-secondary';
        if ($fila['estado'] == 'Activo') $badgeColor = 'bg-success';
        if ($fila['estado'] == 'Productiva') $badgeColor = 'bg-primary';
        if ($fila['estado'] == 'Aplazado') $badgeColor = 'bg-warning text-dark';
        if ($fila['estado'] == 'Desertado') $badgeColor = 'bg-danger';

        // Formatear la fecha si existe
        $fecha = isset($fila['fecha_registro']) ? date('d/m/Y H:i', strtotime($fila['fecha_registro'])) : 'N/A';

        echo "<tr>
                <td class='fw-bold'>{$fila['id']}</td>
                <td><span class='text-muted small'>{$fila['tipo_documento']}</span><br>{$fila['numero_documento']}</td>
                <td class='text-start fw-medium'>{$fila['nombre']}</td>
                <td class='text-start'>
                    <small>📞 {$fila['telefono']}</small><br>
                    <small>✉️ {$fila['correo_electronico']}</small>
                </td>
                <td class='text-start'>
                    <strong>Ficha:</strong> {$fila['numero_ficha']}<br>
                    <small>{$fila['programa_formacion']} ({$fila['jornada']})</small>
                </td>
                <td><span class='badge {$badgeColor}'>{$fila['estado']}</span></td>
                <td><small>{$n1} | {$n2} | {$n3}</small></td>
                <td class='{$colorPromedio}'>{$promedio}</td>
                <td><small class='text-muted'>{$fecha}</small></td>
              </tr>";
    }

    echo "              </tbody>
                    </table>
                </div>
            </div>
            <div class='card-footer'>
                <a href='../index.html' class='btn btn-dark'>+ Registrar Nuevo Aprendiz</a>
            </div>
          </div>";

} catch (PDOException $e) {
    echo "<div class='alert alert-danger shadow-sm'>
            <strong>Error Crítico DB:</strong> " . $e->getMessage() . "
          </div>";
}
?>

</body>
</html>