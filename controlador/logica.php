<?php

var_dump($_POST);

$conexion = new PDO('pgsql:host=dpg-d8f3edeq1p3s73dgojkg-a.oregon-postgres.render.com;dbname=sena_6eak','sena_6eak_user','DlJt2UPz4jFSE9Uk6gmz7vS27rva6yGG');
$registrar = $conexion->prepare("INSERT INTO aprendices (nombre,telefono) VALUES (?, ?)");
$registrar->execute([$_POST["nom"], $_POST["tel"]]);
echo "<p style='color:white;background-color:green;font-family:calibri,arial;font-size:24px;text-align:center'>Registro exitoso</p>";

$consulta = $conexion->prepare("SELECT * FROM aprendices order by id");
$consulta->execute();
$tabla = $consulta->fetchAll(PDO::FETCH_ASSOC);	      //PDO::FETCH_NUM
$conexion = null;

echo "<table><tr><th>Codigo</th>
                 <th>Nombre completo</th>
                 <th>Contacto</th>
					</tr>";
foreach($tabla as $fila){		//Recorre el arreglo $tabla como FETCH_NUM
    echo "<tr>		<td>$fila[id]</td>
            		<td>$fila[nombre]</td>
            		<td>$fila[telefono]</td>
            				</tr>";
}
echo "</table>";



?>
