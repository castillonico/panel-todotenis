<?php

require_once __DIR__ . "/../core/auth.php";
require_once __DIR__ . "/../core/db.php";

$torneo_id = (int)($_GET["torneo_id"] ?? 0);

if(!$torneo_id){

    die("Torneo inválido");

}

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

    die("Torneo no encontrado");

}

if($_SERVER["REQUEST_METHOD"] === "POST"){

    $nombre = trim($_POST["nombre"] ?? "");
    $orden = (int)($_POST["orden_visual"] ?? 0);

    if($nombre !== ""){

        $stmtInsert = $pdo->prepare("

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

        $stmtInsert->execute([

            ":torneo_id" => $torneo_id,
            ":nombre" => $nombre,
            ":orden_visual" => $orden

        ]);

    }

    header("Location: rondas_torneo.php?torneo_id=" . $torneo_id);
    exit;

}

if(isset($_GET["eliminar"])){

    $ronda_id = (int)$_GET["eliminar"];

    $stmtCheck = $pdo->prepare("

    SELECT COUNT(*) 

    FROM partidos

    WHERE ronda_id = :ronda_id

    ");

    $stmtCheck->execute([
        ":ronda_id" => $ronda_id
    ]);

    $cantidad = (int)$stmtCheck->fetchColumn();

    if($cantidad === 0){

        $stmtDelete = $pdo->prepare("

        DELETE FROM rondas_torneo

        WHERE
            id = :id
            AND torneo_id = :torneo_id

        ");

        $stmtDelete->execute([
            ":id" => $ronda_id,
            ":torneo_id" => $torneo_id
        ]);

    }

    header("Location: rondas_torneo.php?torneo_id=" . $torneo_id);
    exit;

}

$stmtRondas = $pdo->prepare("

SELECT *

FROM rondas_torneo

WHERE torneo_id = :torneo_id

ORDER BY
    orden_visual ASC,
    id ASC

");

$stmtRondas->execute([
    ":torneo_id" => $torneo_id
]);

$rondas = $stmtRondas->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Rondas</title>

<style>

body{
    margin:0;
    background:#0f1223;
    color:#fff;
    font-family:Arial,sans-serif;
}

.container{
    max-width:900px;
    margin:auto;
    padding:16px;
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
    border-radius:18px;
    padding:18px;
    margin-bottom:14px;
}

input{
    width:100%;
    padding:12px;
    border:none;
    border-radius:12px;
    box-sizing:border-box;
    margin-bottom:10px;
    background:#0f1223;
    color:white;
}

.submit{
    background:#067ec9;
    color:white;
    border:none;
    border-radius:12px;
    padding:12px;
    width:100%;
    font-weight:bold;
    cursor:pointer;
}

.ronda-card{
    background:#151935;
    border-radius:18px;
    padding:18px;
    margin-bottom:12px;
}

.ronda-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
}

.ronda-nombre{
    font-size:20px;
    font-weight:bold;
}

.ronda-orden{
    opacity:.7;
    margin-top:8px;
}

.delete{
    background:#b42323;
    color:white;
    text-decoration:none;
    padding:10px 14px;
    border-radius:12px;
    font-size:14px;
}

.empty{
    opacity:.6;
    text-align:center;
    padding:24px;
}

</style>

</head>

<body>

<div class="container">

    <div class="topbar">

        <div class="title">
            <?= htmlspecialchars($torneo["nombre"]) ?>
        </div>

        <a
            href="torneos.php"
            class="button"
        >
            ← Torneos
        </a>

    </div>

    <div class="card">

        <form method="POST">

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

        <?php foreach($rondas as $ronda): ?>

            <div class="ronda-card">

                <div class="ronda-header">

                    <div>

                        <div class="ronda-nombre">
                            <?= htmlspecialchars($ronda["nombre"]) ?>
                        </div>

                        <div class="ronda-orden">
                            Orden:
                            <?= (int)$ronda["orden_visual"] ?>
                        </div>

                    </div>

                    <a
                        href="?torneo_id=<?= $torneo_id ?>&eliminar=<?= $ronda["id"] ?>"
                        class="delete"
                    >
                        Eliminar
                    </a>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="empty">
            No hay rondas creadas
        </div>

    <?php endif; ?>

</div>

</body>
</html>