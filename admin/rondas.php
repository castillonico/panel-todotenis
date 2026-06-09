<?php

require_once __DIR__ . "/../core/auth.php";

require_once __DIR__ . "/../core/db.php";

$torneo_id = (int)($_GET["torneo_id"] ?? 0);

if(!$torneo_id){

    die("Falta torneo_id");

}

/*
|--------------------------------------------------------------------------
| TORNEO
|--------------------------------------------------------------------------
*/

$stmtTorneo = $pdo->prepare("

    SELECT *

    FROM torneos

    WHERE id = :id

    LIMIT 1

");

$stmtTorneo->execute([
    ":id" => $torneo_id
]);

$torneo = $stmtTorneo->fetch(PDO::FETCH_ASSOC);

if(!$torneo){

    die("Torneo inexistente");

}

/*
|--------------------------------------------------------------------------
| CREAR RONDA
|--------------------------------------------------------------------------
*/

if(
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    ($_POST["action"] ?? "") === "crear"
){

    $stmt = $pdo->prepare("

        INSERT INTO rondas_torneo (

            torneo_id,
            nombre,
            orden_visual

        )

        VALUES (

            :torneo_id,
            :nombre,
            :orden_visual

        )

    ");

    $stmt->execute([

        ":torneo_id" => $torneo_id,

        ":nombre" => trim($_POST["nombre"]),

        ":orden_visual" => (int)$_POST["orden_visual"]

    ]);

    header(
        "Location: rondas.php?torneo_id=" . $torneo_id
    );

    exit;

}

/*
|--------------------------------------------------------------------------
| ELIMINAR
|--------------------------------------------------------------------------
*/

if(
    isset($_GET["eliminar"])
){

    $id = (int)$_GET["eliminar"];

    $stmt = $pdo->prepare("

        DELETE FROM rondas_torneo

        WHERE
            id = :id
            AND torneo_id = :torneo_id

    ");

    $stmt->execute([

        ":id" => $id,

        ":torneo_id" => $torneo_id

    ]);

    header(
        "Location: rondas.php?torneo_id=" . $torneo_id
    );

    exit;

}

/*
|--------------------------------------------------------------------------
| LISTADO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("

    SELECT *

    FROM rondas_torneo

    WHERE torneo_id = :torneo_id

    ORDER BY
        orden_visual ASC,
        id ASC

");

$stmt->execute([
    ":torneo_id" => $torneo_id
]);

$rondas = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Rondas
</title>

<style>

body{

    margin:0;
    background:#0f1223;
    color:#fff;
    font-family:Arial,sans-serif;
    padding:14px;

}

.container{

    max-width:800px;
    margin:auto;

}

.topbar{

    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:14px;
    margin-bottom:20px;

}

.title{

    font-size:28px;
    font-weight:bold;

}

.button{

    background:#067ec9;
    color:white;
    text-decoration:none;
    padding:12px 18px;
    border-radius:14px;
    font-weight:bold;

}

.card{

    background:#151935;
    border-radius:22px;
    padding:18px;
    margin-bottom:16px;

}

input{

    width:100%;
    padding:12px;
    border:none;
    border-radius:12px;
    margin-bottom:12px;
    box-sizing:border-box;
    background:#0f1223;
    color:white;

}

.submit{

    width:100%;
    background:#067ec9;
    color:white;
    border:none;
    padding:14px;
    border-radius:14px;
    font-weight:bold;
    cursor:pointer;

}

.ronda{

    background:#0f1223;
    border-radius:16px;
    padding:14px;
    margin-bottom:12px;

}

.ronda-nombre{

    font-weight:bold;
    font-size:18px;
    margin-bottom:6px;

}

.ronda-orden{

    opacity:.7;
    margin-bottom:12px;

}

.delete{

    display:inline-block;
    padding:10px 14px;
    border-radius:12px;
    background:#c0392b;
    color:white;
    text-decoration:none;
    font-size:14px;

}

.empty{

    opacity:.6;

}

</style>

</head>

<body>

<div class="container">

    <div class="topbar">

        <div class="title">
            Rondas
        </div>

        <a
            href="torneo_partidos.php?torneo_id=<?= $torneo_id ?>"
            class="button"
        >
            ← Torneo
        </a>

    </div>

    <div class="card">

        <h3>
            <?= htmlspecialchars($torneo["nombre"]) ?>
        </h3>

        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="crear"
            >

            <input
                type="text"
                name="nombre"
                placeholder="Nombre de ronda"
                required
            >

            <input
                type="number"
                name="orden_visual"
                placeholder="Orden visual"
                required
            >

            <button
                type="submit"
                class="submit"
            >
                Crear ronda
            </button>

        </form>

    </div>

    <?php if($rondas): ?>

        <?php foreach($rondas as $r): ?>

            <div class="ronda">

                <div class="ronda-nombre">

                    <?= htmlspecialchars($r["nombre"]) ?>

                </div>

                <div class="ronda-orden">

                    Orden:
                    <?= $r["orden_visual"] ?>

                </div>

                <a
                    href="?torneo_id=<?= $torneo_id ?>&eliminar=<?= $r["id"] ?>"
                    class="delete"
                    onclick="return confirm('Eliminar ronda?')"
                >
                    Eliminar
                </a>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="empty">

            No hay rondas creadas.

        </div>

    <?php endif; ?>

</div>

</body>

</html>