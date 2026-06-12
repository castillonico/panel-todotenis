<?php

require_once __DIR__ . "/../core/db.php";

header("Content-Type: application/json; charset=utf-8");

$torneo_id = isset($_GET["torneo_id"])
    ? (int)$_GET["torneo_id"]
    : 0;

$sql = "

SELECT

    p.id,

    p.fecha,
    p.hora,
    p.sede,
    p.estado,
    p.modalidad,

    c.nombre AS categoria,

    p.sets_json,

    rt.nombre as ronda,
    rt.tipo as tipo_ronda,
    rt.orden_visual as orden_ronda

FROM partidos p

INNER JOIN categorias c
ON c.id = p.categoria_id

LEFT JOIN rondas_torneo rt
ON rt.id = p.ronda_id

WHERE p.estado != 'Borrador'

";

$params = [];

if($torneo_id){

    $sql .= "

    AND p.torneo_id = :torneo_id

    ";

    $params[":torneo_id"] = $torneo_id;

}

$sql .= "

ORDER BY

    rt.orden_visual ASC,

    CASE p.estado
        WHEN 'En juego' THEN 1
        WHEN 'Programado' THEN 2
        WHEN 'Finalizado' THEN 3
        ELSE 4
    END,

    CASE
        WHEN p.estado = 'Finalizado'
        THEN p.fecha
    END DESC,

    CASE
        WHEN p.estado != 'Finalizado'
        THEN p.fecha
    END ASC,

    CASE
        WHEN p.estado = 'Finalizado'
        THEN p.hora
    END DESC,

    CASE
        WHEN p.estado != 'Finalizado'
        THEN p.hora
    END ASC

";

if($torneo_id){

    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

}else{

    $stmt = $pdo->query($sql);

}

$partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

function obtenerJugadores($pdo, $partido_id, $equipo){

    $sql = "

    SELECT j.nombre

    FROM partido_jugadores pj

    INNER JOIN jugadores j
    ON j.id = pj.jugador_id

    WHERE
        pj.partido_id = :partido_id
        AND pj.equipo = :equipo

    ORDER BY j.nombre

    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":partido_id" => $partido_id,
        ":equipo" => $equipo
    ]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$resultado = [];

foreach($partidos as $p){

    $equipoA = obtenerJugadores($pdo, $p["id"], "A");
    $equipoB = obtenerJugadores($pdo, $p["id"], "B");

    $sets = json_decode($p["sets_json"], true) ?: [];
$resultado[] = [

"fecha" => date("d-m-Y", strtotime($p["fecha"])),

"hora" => substr($p["hora"],0,5) . " hs",

"sede" => $p["sede"],

"jugador1" => implode(" / ", $equipoA),

"jugador2" => implode(" / ", $equipoB),

"set1_j1" => $sets[0][0] ?? "",
"set1_j2" => $sets[0][1] ?? "",

"set2_j1" => $sets[1][0] ?? "",
"set2_j2" => $sets[1][1] ?? "",

"set3_j1" => $sets[2][0] ?? "",
"set3_j2" => $sets[2][1] ?? "",

"estado" => $p["estado"],

"modalidad" => $p["modalidad"],

"categoria" => $p["categoria"],

"ronda" => $p["ronda"],

"tipo_ronda" => $p["tipo_ronda"],

"orden_ronda" => (int)$p["orden_ronda"]

];

    
}

echo json_encode(
    $resultado,
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
