<?php

require_once __DIR__ . "/../core/db.php";

header("Content-Type: application/json; charset=utf-8");

$jugador_id = isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;

if(!$jugador_id){

    echo json_encode([
        "error" => "Falta id"
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| DATOS DEL JUGADOR
|--------------------------------------------------------------------------
*/

$sql = "

SELECT
    id,
    nombre,
    club

FROM jugadores

WHERE id = :id

";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":id" => $jugador_id
]);

$jugador = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$jugador){

    echo json_encode([
        "error" => "Jugador inexistente"
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| PARTIDOS DEL JUGADOR
|--------------------------------------------------------------------------
*/

$sql = "

SELECT DISTINCT
    p.id,
    p.estado,
    p.sets_json

FROM partidos p

INNER JOIN partido_jugadores pj
ON pj.partido_id = p.id

WHERE
    pj.jugador_id = :jugador_id
    AND p.estado = 'Finalizado'

";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":jugador_id" => $jugador_id
]);

$partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultado = [

    "id" => (int)$jugador["id"],
    "nombre" => $jugador["nombre"],
    "club" => $jugador["club"],

    "partidos_jugados" => count($partidos)

];

echo json_encode(
    $resultado,
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
