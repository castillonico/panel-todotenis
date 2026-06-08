<?php

require_once __DIR__ . "/../core/auth.php";

require_once __DIR__ . "/../core/db.php";

$id = (int)($_GET["id"] ?? 0);

$sql = "

SELECT *

FROM torneos

WHERE id = :id

LIMIT 1

";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$torneo = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$torneo){

    die("Torneo no encontrado");

}

/*
|--------------------------------------------------------------------------
| GUARDAR
|--------------------------------------------------------------------------
*/

if($_SERVER["REQUEST_METHOD"] === "POST"){

    $nombre = trim($_POST["nombre"] ?? "");

    $tipo = $_POST["tipo"] ?? "Superliga";

    $estado = $_POST["estado"] ?? "Borrador";

    $fecha_inicio = $_POST["fecha_inicio"] ?: null;

    $fecha_fin = $_POST["fecha_fin"] ?: null;

    $suma_global = isset($_POST["suma_global"])
        ? 1
        : 0;

    $activo = isset($_POST["activo"])
        ? 1
        : 0;

    $sqlUpdate = "

    UPDATE torneos

    SET

        nombre = :nombre,
        tipo = :tipo,
        estado = :estado,
        fecha_inicio = :fecha_inicio,
        fecha_fin = :fecha_fin,
        suma_global = :suma_global,
        activo = :activo

    WHERE id = :id

    ";

    $stmtUpdate = $pdo->prepare($sqlUpdate);

    $stmtUpdate->execute([

        ":nombre" => $nombre,

        ":tipo" => $tipo,

        ":estado" => $estado,

        ":fecha_inicio" => $fecha_inicio,

        ":fecha_fin" => $fecha_fin,

        ":suma_global" => $suma_global,

        ":activo" => $activo,

        ":id" => $id

    ]);

    header("Location: torneos.php");

    exit;

}

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Editar torneo</title>

<style>

body{

    margin:0;
    background:#0f1223;
    color:#fefefe;
    font-family:Arial,sans-serif;
    padding:20px;

}

.container{

    max-width:700px;
    margin:auto;

}

.card{

    background:#151935;
    border-radius:22px;
    padding:24px;

}

.grid{

    display:grid;
    gap:18px;

}

input,
select{

    width:100%;
    box-sizing:border-box;

    padding:14px;

    border:none;

    border-radius:14px;

    background:#0f1223;

    color:white;

}

button{

    background:#067ec9;

    color:white;

    border:none;

    border-radius:14px;

    padding:14px 18px;

    font-weight:bold;

    cursor:pointer;

}

.link{

    color:#67b7ff;

    text-decoration:none;

    font-weight:bold;

}

</style>

</head>

<body>

<div class="container">

<div style="margin-bottom:24px;">

    <a
        href="torneos.php"
        class="link"
    >
        ← Volver a torneos
    </a>

</div>

<div class="card">

    <h1>
        Editar torneo
    </h1>

    <form method="POST">

        <div class="grid">

            <input
                type="text"
                name="nombre"
                value="<?= htmlspecialchars($torneo["nombre"]) ?>"
                required
            >

            <select name="tipo">

                <option value="Superliga" <?= $torneo["tipo"] === "Superliga" ? "selected" : "" ?>>
                    Superliga
                </option>

                <option value="Open" <?= $torneo["tipo"] === "Open" ? "selected" : "" ?>>
                    Open
                </option>

                <option value="Master" <?= $torneo["tipo"] === "Master" ? "selected" : "" ?>>
                    Master
                </option>

                <option value="Copa" <?= $torneo["tipo"] === "Copa" ? "selected" : "" ?>>
                    Copa
                </option>

            </select>

            <select name="estado">

                <?php

                $estados = [
                    "Borrador",
                    "Inscripciones",
                    "Activo",
                    "Finalizado",
                    "Archivado"
                ];

                foreach($estados as $estado):

                ?>

                    <option
                        value="<?= $estado ?>"
                        <?= $torneo["estado"] === $estado ? "selected" : "" ?>
                    >

                        <?= $estado ?>

                    </option>

                <?php endforeach; ?>

            </select>

            <input
                type="date"
                name="fecha_inicio"
                value="<?= $torneo["fecha_inicio"] ?>"
            >

            <input
                type="date"
                name="fecha_fin"
                value="<?= $torneo["fecha_fin"] ?>"
            >

            <label>

                <input
                    type="checkbox"
                    name="suma_global"
                    <?= $torneo["suma_global"] ? "checked" : "" ?>
                >

                Suma ranking global

            </label>

            <label>

                <input
                    type="checkbox"
                    name="activo"
                    <?= $torneo["activo"] ? "checked" : "" ?>
                >

                Torneo activo

            </label>

            <button type="submit">
                Guardar cambios
            </button>

        </div>

    </form>

</div>

</div>

</body>
</html>
