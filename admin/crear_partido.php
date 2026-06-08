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

$sqlTorneo = "

SELECT *

FROM torneos

WHERE id = :id

LIMIT 1

";

$stmtTorneo = $pdo->prepare($sqlTorneo);

$stmtTorneo->execute([
    ":id" => $torneo_id
]);

$torneo = $stmtTorneo->fetch(PDO::FETCH_ASSOC);

if(!$torneo){

    die("Torneo inexistente");

}

/*
|--------------------------------------------------------------------------
| DATOS
|--------------------------------------------------------------------------
*/

$categorias = $pdo->query("

    SELECT
        id,
        nombre

    FROM categorias

    ORDER BY nombre

")->fetchAll(PDO::FETCH_ASSOC);

$jugadores = $pdo->query("

    SELECT
        id,
        nombre

    FROM jugadores

    WHERE activo = 1

    ORDER BY nombre

")->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| CREAR PARTIDO
|--------------------------------------------------------------------------
*/

if($_SERVER["REQUEST_METHOD"] === "POST"){

    $stmt = $pdo->prepare("

        INSERT INTO partidos (

            torneo_id,
            categoria_id,
            ronda,
            modalidad,
            fecha,
            hora,
            sede,
            estado

        )

        VALUES (

            :torneo,
            :categoria,
            :ronda,
            :modalidad,
            :fecha,
            :hora,
            :sede,
            :estado

        )

    ");

    $stmt->execute([

        ":torneo" => $torneo_id,

        ":categoria" => $_POST["categoria_id"],

        ":ronda" => $_POST["ronda"],

        ":modalidad" => $_POST["modalidad"],

        ":fecha" => $_POST["fecha"],

        ":hora" => $_POST["hora"],

        ":sede" => $_POST["sede"],

        ":estado" => $_POST["estado"]

    ]);

    $partido_id = $pdo->lastInsertId();

    /*
    |--------------------------------------------------------------------------
    | JUGADORES
    |--------------------------------------------------------------------------
    */

    $insertJugador = $pdo->prepare("

        INSERT INTO partido_jugadores (

            partido_id,
            jugador_id,
            equipo

        )

        VALUES (

            :partido_id,
            :jugador_id,
            :equipo

        )

    ");

    $mapa = [

        "A" => ["jugador_a1", "jugador_a2"],

        "B" => ["jugador_b1", "jugador_b2"]

    ];

    foreach($mapa as $equipo => $campos){

        foreach($campos as $campo){

            if(!isset($_POST[$campo])){
                continue;
            }

            $jugador_id = $_POST[$campo];

            if($jugador_id === "" || $jugador_id === null){
                continue;
            }

            $insertJugador->execute([

                ":partido_id" => $partido_id,

                ":jugador_id" => (int)$jugador_id,

                ":equipo" => $equipo

            ]);

        }

    }

    header(
        "Location: torneo_partidos.php?torneo_id=" . $torneo_id
    );

    exit;

}

?>




<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crear Partido</title>

<style>

body{
    margin:0;
    background:#0a0f1e;
    color:#fff;
    font-family:Arial;
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
.container{
    max-width:900px;
    margin:auto;
    padding:16px;
}

.card{
    background:linear-gradient(180deg,#151935,#101427);
    border:1px solid rgba(255,255,255,.06);
    border-radius:18px;
    padding:18px;
    margin-bottom:14px;
}

h3{
    margin:0 0 12px 0;
}

input,select,button{
    width:100%;
    padding:12px;
    border-radius:12px;
    border:none;
    margin-bottom:10px;
    box-sizing:border-box;
}

input,select{
    background:#0a0f1e;
    color:#fff;
}

.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
}

.teams{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
}

.team{
    background:#0a0f1e;
    padding:12px;
    border-radius:14px;
    border:1px solid rgba(255,255,255,.05);
}

.team-title{
    font-weight:bold;
    margin-bottom:10px;
    opacity:.8;
}

.set-row{
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    gap:10px;
    align-items:center;
    margin-bottom:10px;
}

.set-label{
    font-weight:bold;
    opacity:.7;
}

button{
    background:#067ec9;
    color:white;
    font-weight:bold;
    cursor:pointer;
}

@media(max-width:768px){

    .grid{
        grid-template-columns:1fr;
    }

    .teams{
        grid-template-columns:1fr;
    }

}

</style>

</head>

<body>

<div class="container">

<body>

<div class="container">

```
<div class="topbar">

    <div class="title">
        Crear partido
    </div>

    <a
        href="torneo_partidos.php?torneo_id=<?= $torneo_id ?>"
        class="button"
    >
        ← Torneo
    </a>

</div>

<form method="POST">

    <!-- TORNEO -->

    <div class="card">

        <h3>
            Torneo
        </h3>

        <input
            type="text"
            value="<?= htmlspecialchars($torneo["nombre"]) ?>"
            disabled
        >

    </div>

    <!-- CONTEXTO -->

    <div class="card">

        <h3>
            Contexto del partido
        </h3>

        <select
            name="categoria_id"
            required
        >

            <option value="">
                Categoría
            </option>

            <?php foreach($categorias as $c): ?>

                <option value="<?= $c["id"] ?>">

                    <?= $c["nombre"] ?>

                </option>

            <?php endforeach; ?>

        </select>

        <input
            type="text"
            name="ronda"
            placeholder="Ronda (Ej: Grupo A / Semifinal)"
            required
        >

        <select name="modalidad">

            <option>
                Singles
            </option>

            <option>
                Dobles
            </option>

        </select>

    </div>

    <!-- PROGRAMACIÓN -->

    <div class="card">

        <h3>
            Programación
        </h3>

        <div class="grid">

            <input
                type="date"
                name="fecha"
            >

            <input
                type="time"
                name="hora"
            >

        </div>

        <select name="sede">

            <option>
                CTC
            </option>

            <option>
                Olimpia
            </option>

            <option>
                Independiente
            </option>

        </select>

        <select name="estado">

            <option>
                Borrador
            </option>

            <option>
                Programado
            </option>

            <option>
                En juego
            </option>

            <option>
                Finalizado
            </option>

        </select>

    </div>

    <!-- EQUIPOS -->

    <div class="card">

        <h3>
            Equipos
        </h3>

        <div class="teams">

            <div class="team">

                <div class="team-title">
                    Equipo A
                </div>

                <select
                    name="jugador_a1"
                    required
                >

                    <option value="">
                        Jugador A1
                    </option>

                    <?php foreach($jugadores as $j): ?>

                        <option value="<?= $j["id"] ?>">

                            <?= $j["nombre"] ?>

                        </option>

                    <?php endforeach; ?>

                </select>

                <select name="jugador_a2">

                    <option value="">
                        Jugador A2 (opcional)
                    </option>

                    <?php foreach($jugadores as $j): ?>

                        <option value="<?= $j["id"] ?>">

                            <?= $j["nombre"] ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="team">

                <div class="team-title">
                    Equipo B
                </div>

                <select
                    name="jugador_b1"
                    required
                >

                    <option value="">
                        Jugador B1
                    </option>

                    <?php foreach($jugadores as $j): ?>

                        <option value="<?= $j["id"] ?>">

                            <?= $j["nombre"] ?>

                        </option>

                    <?php endforeach; ?>

                </select>

                <select name="jugador_b2">

                    <option value="">
                        Jugador B2 (opcional)
                    </option>

                    <?php foreach($jugadores as $j): ?>

                        <option value="<?= $j["id"] ?>">

                            <?= $j["nombre"] ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

        </div>

    </div>

    <button type="submit">

        Crear partido

    </button>

</form>
```

</div>

</body>


</div>

</body>
</html>
