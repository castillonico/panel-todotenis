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
| TOGGLE ACTIVO
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
    padding:14px;

}

.container{

    max-width:900px;
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
    padding:20px;
    margin-bottom:18px;

}

.form-grid{

    display:grid;
    gap:14px;

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

.submit{

    background:#067ec9;
    color:white;
    border:none;
    border-radius:14px;
    padding:14px;
    font-weight:bold;
    cursor:pointer;

}

.torneo-card{

    background:#151935;
    border-radius:22px;
    padding:20px;
    margin-bottom:16px;

}

.torneo-top{

    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
    margin-bottom:16px;

}

.torneo-name{

    font-size:22px;
    font-weight:bold;

}

.torneo-type{

    opacity:.7;
    margin-top:6px;

}

.badge{

    padding:8px 12px;
    border-radius:999px;
    font-size:13px;
    font-weight:bold;
    white-space:nowrap;

}

.badge-activo{

    background:#1f7a42;

}

.badge-borrador{

    background:#7c5d20;

}

.badge-finalizado{

    background:#444;

}

.badge-inscripciones{

    background:#6d2f91;

}

.badge-archivado{

    background:#555;

}

.torneo-meta{

    display:grid;
    gap:10px;
    margin-bottom:18px;
    opacity:.8;

}

.actions{

    display:flex;
    flex-wrap:wrap;
    gap:10px;

}

.action{

    background:#0f1223;
    color:white;
    text-decoration:none;
    padding:12px 14px;
    border-radius:12px;
    font-size:14px;
    font-weight:bold;

}

.action-disabled{

    opacity:.45;
    pointer-events:none;

}

@media(min-width:768px){

    .form-grid{

        grid-template-columns:repeat(2,1fr);

    }

}

</style>

</head>

<body>

<div class="container">

<div class="topbar">
    <div class="title">Torneos </div>
    <a href="index.php" class="button"> ← Panel </a>
</div>

<div class="card">

    <form method="POST">

        <div class="form-grid">

            <input
                type="text"
                name="nombre"
                placeholder="Nombre torneo"
                required
            >

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

            <input
                type="date"
                name="fecha_inicio"
            >

            <input
                type="date"
                name="fecha_fin"
            >

        </div>

        <br>

        <label style="display:block;margin-bottom:18px;">

            <input
                type="checkbox"
                name="suma_global"
                checked
            >

            Suma ranking global

        </label>

        <button
            type="submit"
            class="submit"
        >
            Crear torneo
        </button>

    </form>

</div>

<?php foreach($torneos as $torneo): ?>

    <?php

    $badgeClass = "badge-borrador";

    if($torneo["estado"] === "Activo"){
        $badgeClass = "badge-activo";
    }

    if($torneo["estado"] === "Finalizado"){
        $badgeClass = "badge-finalizado";
    }

    if($torneo["estado"] === "Inscripciones"){
        $badgeClass = "badge-inscripciones";
    }

    if($torneo["estado"] === "Archivado"){
        $badgeClass = "badge-archivado";
    }

    ?>

    <div class="torneo-card">

        <div class="torneo-top">

            <div>

                <div class="torneo-name">
                    <?= htmlspecialchars($torneo["nombre"]) ?>
                </div>

                <div class="torneo-type">
                    <?= htmlspecialchars($torneo["tipo"]) ?>
                </div>

            </div>

            <div class="badge <?= $badgeClass ?>">

                <?= htmlspecialchars($torneo["estado"]) ?>

            </div>

        </div>

        <div class="torneo-meta">

            <div>

                Inicio:
                <?= $torneo["fecha_inicio"] ?: "-" ?>

            </div>

            <div>

                Fin:
                <?= $torneo["fecha_fin"] ?: "-" ?>

            </div>

            <div>

                Ranking global:
                <?= $torneo["suma_global"] ? "Sí" : "No" ?>

            </div>

            <div>

                Estado sistema:
                <?= $torneo["activo"] ? "Activo" : "Desactivado" ?>

            </div>

        </div>

        <div class="actions">

            <a
                href="editar_torneo.php?id=<?= $torneo["id"] ?>"
                class="action"
            >
                Editar
            </a>

            <a
                href="?toggle=<?= $torneo["id"] ?>"
                class="action"
            >

                <?= $torneo["activo"]
                    ? "Desactivar"
                    : "Activar" ?>

            </a>

            <div class="action action-disabled">
                Partidos
            </div>

            <div class="action action-disabled">
                Ranking
            </div>

        </div>

    </div>

<?php endforeach; ?>


</div>

</body>
</html>
