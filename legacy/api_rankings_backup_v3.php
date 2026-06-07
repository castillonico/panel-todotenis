<?php

require_once "db.php";

header("Content-Type: application/json; charset=utf-8");

$sql = "

SELECT
    j.nombre,
    j.club,
    c.nombre as categoria,
    COALESCE(SUM(rm.puntos),0) as puntos

FROM jugadores j

INNER JOIN ranking_movimientos rm
ON rm.jugador_id = j.id

INNER JOIN categorias c
ON c.id = rm.categoria_id

GROUP BY
    j.id,
    c.id

ORDER BY
    c.nombre ASC,
    puntos DESC

";

$stmt = $pdo->query($sql);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultado = [];

function slugCategoria($nombre){

    $slug = mb_strtolower($nombre);

    $slug = str_replace(
        [" ", "ª", "°"],
        ["_", "", ""],
        $slug
    );

    return $slug;
}

foreach($rows as $r){

    $slug = slugCategoria($r["categoria"]);

    if(!isset($resultado[$slug])){

        $resultado[$slug] = [
            "nombre" => $r["categoria"],
            "jugadores" => []
        ];
    }

    $resultado[$slug]["jugadores"][] = [

        "nombre" => $r["nombre"],
        "club" => $r["club"],
        "puntos" => (int)$r["puntos"]

    ];
}

echo json_encode(
    $resultado,
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);<?php

require_once "db.php";

header("Content-Type: application/json; charset=utf-8");

$sql = "

SELECT
    j.nombre,
    j.club,
    c.nombre as categoria,
    COALESCE(SUM(rm.puntos),0) as puntos

FROM jugadores j

INNER JOIN ranking_movimientos rm
ON rm.jugador_id = j.id

INNER JOIN categorias c
ON c.id = rm.categoria_id

GROUP BY
    j.id,
    c.id

ORDER BY
    c.nombre ASC,
    puntos DESC

";

$stmt = $pdo->query($sql);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultado = [];

function slugCategoria($nombre){

    $slug = mb_strtolower($nombre);

    $slug = str_replace(
        [" ", "ª", "°"],
        ["_", "", ""],
        $slug
    );

    return $slug;
}

foreach($rows as $r){

    $slug = slugCategoria($r["categoria"]);

    if(!isset($resultado[$slug])){

        $resultado[$slug] = [
            "nombre" => $r["categoria"],
            "jugadores" => []
        ];
    }

    $resultado[$slug]["jugadores"][] = [

        "nombre" => $r["nombre"],
        "club" => $r["club"],
        "puntos" => (int)$r["puntos"]

    ];
}

echo json_encode(
    $resultado,
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
