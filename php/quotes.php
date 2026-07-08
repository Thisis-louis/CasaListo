<?php
require_once 'config/db.php'; 
require_once 'lib/functions_quotes.php'; 

$post = json_decode(file_get_contents("php://input"), true);
$action = $post['action'] ?? '';

switch ($action) {
    case "getAll":
        $data = getAllQuotes(); 
        if ($data) {
            echo json_encode(["status" => "success", "data" => $data]);
        } else {
            echo json_encode(["status" => "error", "message" => "No hay datos para mostrar"]);
        }
        break;

    case "process_quote":
        $resultado = processQuote($post);
        echo json_encode($resultado);
        break;

    case "update_quote":
        $resultado = updateQuote($post); 
        echo json_encode($resultado);
        break;

    case "delete_quote":
        $id_quote = $post['id_quote'] ?? 0;
        $resultado = deleteQuote($id_quote); 
        echo json_encode($resultado);
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Acción inválida"]);
        break;
}
?>