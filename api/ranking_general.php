<?php

require_once __DIR__ . "/../core/db.php";

header("Content-Type: application/json; charset=utf-8");

$categoria_id = isset($_GET["categoria_id"])
    ? (int)$_GET["categoria_id"]
    : 0;

function slugCategoriaGeneral($nombre){

    $slug = mb_strtolower($nombre);

    return str_replace(
        [" ", "ª", "°"],
        ["_", "", ""],
        $slug
    );
}

try {

    $sqlCategorias = "

    SELECT
        id,
        nombre

    FROM categorias

    WHERE activo = 1

    ";

    $paramsCategorias = [];

    if($categoria_id){

        $sqlCategorias .= "

        AND id = :categoria_id

        ";

        $paramsCategorias[":categoria_id"] = $categoria_id;
    }

    $sqlCategorias .= "

    ORDER BY nombre ASC, id ASC

    ";

    $stmtCategorias = $pdo->prepare($sqlCategorias);
    $stmtCategorias->execute($paramsCategorias);

    $categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

    $stmtTorneos = $pdo->query("

        SELECT
            id,
            nombre,
            fecha_inicio,
            fecha_fin

        FROM torneos

        WHERE suma_global = 1

        ORDER BY
            fecha_inicio IS NULL ASC,
            fecha_inicio ASC,
            id ASC

    ");

    $torneos = $stmtTorneos->fetchAll(PDO::FETCH_ASSOC);

    $sqlMovimientos = "

    SELECT
        rm.categoria_id,
        rm.jugador_id,
        j.nombre,
        j.club,
        rm.torneo_id,
        t.nombre AS torneo,
        t.fecha_inicio,
        t.fecha_fin,
        SUM(rm.puntos) AS puntos

    FROM ranking_movimientos rm

    INNER JOIN jugadores j
    ON j.id = rm.jugador_id

    INNER JOIN categorias c
    ON c.id = rm.categoria_id

    INNER JOIN torneos t
    ON t.id = rm.torneo_id

    WHERE
        c.activo = 1
        AND t.suma_global = 1

    ";

    $paramsMovimientos = [];

    if($categoria_id){

        $sqlMovimientos .= "

        AND rm.categoria_id = :categoria_id

        ";

        $paramsMovimientos[":categoria_id"] = $categoria_id;
    }

    $sqlMovimientos .= "

    GROUP BY
        rm.categoria_id,
        rm.jugador_id,
        j.nombre,
        j.club,
        rm.torneo_id,
        t.nombre,
        t.fecha_inicio,
        t.fecha_fin

    ORDER BY
        rm.categoria_id ASC,
        t.fecha_inicio IS NULL ASC,
        t.fecha_inicio ASC,
        rm.torneo_id ASC,
        rm.jugador_id ASC

    ";

    $stmtMovimientos = $pdo->prepare($sqlMovimientos);
    $stmtMovimientos->execute($paramsMovimientos);

    $movimientos = $stmtMovimientos->fetchAll(PDO::FETCH_ASSOC);

    $resultadoCategorias = [];

    foreach($categorias as $categoria){

        $id = (int)$categoria["id"];

        $resultadoCategorias[$id] = [
            "id" => $id,
            "nombre" => $categoria["nombre"],
            "slug" => slugCategoriaGeneral($categoria["nombre"]),
            "torneos" => [],
            "jugadores" => []
        ];
    }

    foreach($movimientos as $movimiento){

        $categoriaId = (int)$movimiento["categoria_id"];

        if(!isset($resultadoCategorias[$categoriaId])){
            continue;
        }

        $jugadorId = (int)$movimiento["jugador_id"];
        $torneoId = (int)$movimiento["torneo_id"];
        $puntos = (int)$movimiento["puntos"];

        if(!isset($resultadoCategorias[$categoriaId]["torneos"][$torneoId])){

            $resultadoCategorias[$categoriaId]["torneos"][$torneoId] = [
                "id" => $torneoId,
                "nombre" => $movimiento["torneo"],
                "fecha_inicio" => $movimiento["fecha_inicio"],
                "fecha_fin" => $movimiento["fecha_fin"]
            ];
        }

        if(!isset($resultadoCategorias[$categoriaId]["jugadores"][$jugadorId])){

            $resultadoCategorias[$categoriaId]["jugadores"][$jugadorId] = [
                "posicion" => 0,
                "id" => $jugadorId,
                "categoria_id" => $categoriaId,
                "nombre" => $movimiento["nombre"],
                "club" => $movimiento["club"],
                "puntos" => 0,
                "torneos" => []
            ];
        }

        $resultadoCategorias[$categoriaId]["jugadores"][$jugadorId]["puntos"] += $puntos;

        $resultadoCategorias[$categoriaId]["jugadores"][$jugadorId]["torneos"][] = [
            "id" => $torneoId,
            "nombre" => $movimiento["torneo"],
            "puntos" => $puntos
        ];
    }

    foreach($resultadoCategorias as &$categoria){

        $categoria["torneos"] = array_values($categoria["torneos"]);
        $categoria["jugadores"] = array_values($categoria["jugadores"]);

        usort(
            $categoria["jugadores"],
            function($a, $b){

                if($a["puntos"] !== $b["puntos"]){
                    return $b["puntos"] <=> $a["puntos"];
                }

                $nombre = strcasecmp($a["nombre"], $b["nombre"]);

                if($nombre !== 0){
                    return $nombre;
                }

                return $a["id"] <=> $b["id"];
            }
        );

        foreach($categoria["jugadores"] as $indice => &$jugador){
            $jugador["posicion"] = $indice + 1;
        }

        unset($jugador);
    }

    unset($categoria);

    $resultadoTorneos = [];

    foreach($torneos as $torneo){

        $resultadoTorneos[] = [
            "id" => (int)$torneo["id"],
            "nombre" => $torneo["nombre"],
            "fecha_inicio" => $torneo["fecha_inicio"],
            "fecha_fin" => $torneo["fecha_fin"]
        ];
    }

    echo json_encode(
        [
            "torneos" => $resultadoTorneos,
            "categorias" => array_values($resultadoCategorias)
        ],
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    );

} catch(Throwable $e){

    http_response_code(500);

    echo json_encode(
        [
            "error" => "No se pudo obtener el ranking general"
        ],
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    );
}