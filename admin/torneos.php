<?php

require_once __DIR__ . "/../core/auth.php";

require_once __DIR__ . "/../core/db.php";

/*
|--------------------------------------------------------------------------
| CREAR TORNEO
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

    if($nombre !== ""){

        $sqlInsert = "

        INSERT INTO torneos (

            nombre,
            tipo,
            estado,
            fecha_inicio,
            fecha_fin,
            suma_global

        ) VALUES (

            :nombre,
            :tipo,
            :estado,
            :fecha_inicio,
            :fecha_fin,
            :suma_global

        )

        ";

        $stmtInsert = $pdo->prepare($sqlInsert);

        $stmtInsert->execute([

            ":nombre" => $nombre,

            ":tipo" => $tipo,

            ":estado" => $estado,

            ":fecha_inicio" => $fecha_inicio,

            ":fecha_fin" => $fecha_fin,

            ":suma_global" => $suma_global

        ]);

    }

    header("Location: torneos.php");

    exit;

}

/*
|--------------------------------------------------------------------------
| CAMBIAR ACTIVO
|--------------------------------------------------------------------------
*/

if(isset($_GET["toggle"])){

    $id = (int)$_GET["toggle"];

    $sqlToggle = "

    UPDATE torneos

    SET activo = IF(activo = 1, 0, 1)

    WHERE id = :id

    ";

    $stmtToggle = $pdo->prepare($sqlToggle);

    $stmtToggle->execute([
        ":id" => $id
    ]);

    header("Location: torneos.php");

    exit;

}

/*
|--------------------------------------------------------------------------
| LISTADO
|--------------------------------------------------------------------------
*/

$sql = "

SELECT *

FROM torneos

ORDER BY created_at DESC

";

$stmt = $pdo->query($sql);

$torneos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Torneos</title>

<style>

body{

    margin:0;
    background:#0f1223;
    color:#fefefe;
    font-family:Arial,sans-serif;
    padding:20px;

}

.container{

    max-width:1100px;
    margin:auto;

}

h1{

    margin-top:0;
    margin-bottom:24px;

}

.card{

    background:#151935;
    border-radius:22px;
    padding:24px;
    margin-bottom:24px;

}

.grid{

    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:16px;

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

.table{

    width:100%;
    border-collapse:collapse;

}

.table th,
.table td{

    padding:16px;

    text-align:left;

    border-bottom:1px solid rgba(255,255,255,.08);

}

.badge{

    display:inline-block;

    padding:6px 12px;

    border-radius:999px;

    font-size:13px;

    font-weight:bold;

}

.estado-activo{

    background:#1d7f42;

}

.estado-borrador{

    background:#7f5d1d;

}

.estado-finalizado{

    background:#444;

}

.link{

    color:#67b7ff;

    text-decoration:none;

    font-weight:bold;

}

.switch{

    color:#ffffff90;

}

</style>

</head>

<body>

<div class="container">

```
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;gap:16px;flex-wrap:wrap;">

```
<h1 style="margin:0;">
    Torneos
</h1>

<a
    href="index.php"
    class="link"
    style="
        background:#067ec9;
        color:white;
        padding:12px 18px;
        border-radius:14px;
        text-decoration:none;
        font-weight:bold;
    "
>
    ← Volver al panel
</a>

</div>


<div class="card">

    <form method="POST">

        <div class="grid">

            <div>

                <input
                    type="text"
                    name="nombre"
                    placeholder="Nombre del torneo"
                    required
                >

            </div>

            <div>

                <select name="tipo">

                    <option value="Superliga">
                        Superliga
                    </option>

                    <option value="Open">
                        Open
                    </option>

                    <option value="Master">
                        Master
                    </option>

                    <option value="Copa">
                        Copa
                    </option>

                </select>

            </div>

            <div>

                <select name="estado">

                    <option value="Borrador">
                        Borrador
                    </option>

                    <option value="Inscripciones">
                        Inscripciones
                    </option>

                    <option value="Activo">
                        Activo
                    </option>

                    <option value="Finalizado">
                        Finalizado
                    </option>

                    <option value="Archivado">
                        Archivado
                    </option>

                </select>

            </div>

            <div>

                <input
                    type="date"
                    name="fecha_inicio"
                >

            </div>

            <div>

                <input
                    type="date"
                    name="fecha_fin"
                >

            </div>

            <div>

                <label class="switch">

                    <input
                        type="checkbox"
                        name="suma_global"
                        checked
                    >

                    Suma ranking global

                </label>

            </div>

        </div>

        <br>

        <button type="submit">
            Crear torneo
        </button>

    </form>

</div>

<div class="card">

    <table class="table">

        <thead>

            <tr>

                <th>
                    Nombre
                </th>

                <th>
                    Tipo
                </th>

                <th>
                    Estado
                </th>

                <th>
                    Inicio
                </th>

                <th>
                    Fin
                </th>

                <th>
                    Global
                </th>

                <th>
                    Activo
                </th>

            </tr>

        </thead>

        <tbody>

            <?php foreach($torneos as $torneo): ?>

                <tr>

                    <td>

                        <?= htmlspecialchars($torneo["nombre"]) ?>

                    </td>

                    <td>

                        <?= htmlspecialchars($torneo["tipo"]) ?>

                    </td>

                    <td>

                        <span class="badge">

                            <?= htmlspecialchars($torneo["estado"]) ?>

                        </span>

                    </td>

                    <td>

                        <?= $torneo["fecha_inicio"] ?: "-" ?>

                    </td>

                    <td>

                        <?= $torneo["fecha_fin"] ?: "-" ?>

                    </td>

                    <td>

                        <?= $torneo["suma_global"]
                            ? "Sí"
                            : "No" ?>

                    </td>

                    <td>

                        <td>

```
<a
    href="?toggle=<?= $torneo["id"] ?>"
    class="link"
>

    <?= $torneo["activo"]
        ? "Desactivar"
        : "Activar" ?>

</a>
```

</td>

<td>

```
<div style="display:flex;gap:12px;flex-wrap:wrap;">

    <a
        href="editar_torneo.php?id=<?= $torneo["id"] ?>"
        class="link"
    >
        Editar
    </a>

    <a
        href="#"
        class="link"
        style="opacity:.5;"
    >
        Partidos
    </a>

    <a
        href="#"
        class="link"
        style="opacity:.5;"
    >
        Ranking
    </a>

</div>
```

</td>


                    </td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

</div>
```

</div>

</body>
</html>
