<?php

require_once __DIR__ . "/../core/auth.php";

require_once __DIR__ . "/../core/db.php";

$torneos = $pdo->query("SELECT id,nombre FROM torneos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$categorias = $pdo->query("SELECT id,nombre FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$jugadores = $pdo->query("SELECT id,nombre FROM jugadores WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $sets_json = json_encode([
        [$_POST["set1_a"] ?? 0, $_POST["set1_b"] ?? 0],
        [$_POST["set2_a"] ?? 0, $_POST["set2_b"] ?? 0],
        [$_POST["set3_a"] ?? 0, $_POST["set3_b"] ?? 0]
    ]);

    // =========================
    // INSERT PARTIDO
    // =========================
    $stmt = $pdo->prepare("
        INSERT INTO partidos (
            torneo_id,
            categoria_id,
            modalidad,
            fecha,
            hora,
            sede,
            estado,
            sets_json
        )
        VALUES (
            :torneo,
            :categoria,
            :modalidad,
            :fecha,
            :hora,
            :sede,
            :estado,
            :sets
        )
    ");

    $stmt->execute([
        ":torneo" => $_POST["torneo_id"],
        ":categoria" => $_POST["categoria_id"],
        ":modalidad" => $_POST["modalidad"],
        ":fecha" => $_POST["fecha"],
        ":hora" => $_POST["hora"],
        ":sede" => $_POST["sede"],
        ":estado" => $_POST["estado"],
        ":sets" => $sets_json
    ]);

    $partido_id = $pdo->lastInsertId();

    // =========================
    // INSERT JUGADORES (FIX REAL)
    // =========================
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

foreach ($mapa as $equipo => $campos) {

    foreach ($campos as $campo) {

        if (!isset($_POST[$campo])) {
            continue;
        }

        $jugador_id = $_POST[$campo];

        // 🔥 CLAVE: evitar valores vacíos
        if ($jugador_id === "" || $jugador_id === null) {
            continue;
        }

        $insertJugador->execute([
            ":partido_id" => $partido_id,
            ":jugador_id" => (int)$jugador_id,
            ":equipo" => $equipo
        ]);
    }
}

    header("Location: listar_partidos.php");
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

<form method="POST">

<!-- CONTEXTO -->
<div class="card">
    <h3>Contexto del partido</h3>

    <select name="torneo_id" required>
        <option value="">Torneo</option>
        <?php foreach($torneos as $t): ?>
            <option value="<?= $t["id"] ?>"><?= $t["nombre"] ?></option>
        <?php endforeach; ?>
    </select>

    <select name="categoria_id" required>
        <option value="">Categoría</option>
        <?php foreach($categorias as $c): ?>
            <option value="<?= $c["id"] ?>"><?= $c["nombre"] ?></option>
        <?php endforeach; ?>
    </select>

    <select name="modalidad">
        <option>Singles</option>
        <option>Dobles</option>
    </select>
</div>

<!-- PROGRAMACIÓN -->
<div class="card">
    <h3>Programación</h3>

    <div class="grid">
        <input type="date" name="fecha">
        <input type="time" name="hora">
    </div>

    <select name="sede">
        <option>CTC</option>
        <option>Olimpia</option>
        <option>Independiente</option>
    </select>

    <select name="estado">
        <option>Borrador</option>
        <option>Programado</option>
        <option>En juego</option>
        <option>Finalizado</option>
    </select>
</div>

<!-- EQUIPOS -->
<div class="card">
    <h3>Equipos</h3>

    <div class="teams">

        <div class="team">
            <div class="team-title">Equipo A</div>

            <select name="jugador_a1" required>
                <option value="">Jugador A1</option>
                <?php foreach($jugadores as $j): ?>
                    <option value="<?= $j["id"] ?>"><?= $j["nombre"] ?></option>
                <?php endforeach; ?>
            </select>

            <select name="jugador_a2">
                <option value="">Jugador A2 (opcional)</option>
                <?php foreach($jugadores as $j): ?>
                    <option value="<?= $j["id"] ?>"><?= $j["nombre"] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="team">
            <div class="team-title">Equipo B</div>

            <select name="jugador_b1" required>
                <option value="">Jugador B1</option>
                <?php foreach($jugadores as $j): ?>
                    <option value="<?= $j["id"] ?>"><?= $j["nombre"] ?></option>
                <?php endforeach; ?>
            </select>

            <select name="jugador_b2">
                <option value="">Jugador B2 (opcional)</option>
                <?php foreach($jugadores as $j): ?>
                    <option value="<?= $j["id"] ?>"><?= $j["nombre"] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

    </div>
</div>

<!-- SETS -->
<div class="card">
    <h3>Sets</h3>

    <div class="set-row">
        <div class="set-label">Set 1</div>
        <input name="set1_a" placeholder="A">
        <input name="set1_b" placeholder="B">
    </div>

    <div class="set-row">
        <div class="set-label">Set 2</div>
        <input name="set2_a" placeholder="A">
        <input name="set2_b" placeholder="B">
    </div>

    <div class="set-row">
        <div class="set-label">Set 3</div>
        <input name="set3_a" placeholder="A">
        <input name="set3_b" placeholder="B">
    </div>
</div>

<button type="submit">Crear partido</button>

</form>

</div>

</body>
</html>
