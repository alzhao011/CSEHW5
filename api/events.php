<?php
header("Content-Type: application/json");

$mysqli = new mysqli("localhost", "hw3_user", "Pokemon2002412!", "hw3_analytics");

if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed"]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

switch ($method) {

    // GET all OR GET by id
    case "GET":
        if ($id) {
            $stmt = $mysqli->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            echo json_encode($result->fetch_assoc());
        } else {
            $result = $mysqli->query("SELECT * FROM events ORDER BY id DESC LIMIT 50");
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            echo json_encode($rows);
        }
        break;

    // POST create new event
    case "POST":
        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $mysqli->prepare("
            INSERT INTO events (session_id, event_type, page_url, data)
            VALUES (?, ?, ?, ?)
        ");

        $jsonData = json_encode($data['data']);

        $stmt->bind_param(
            "ssss",
            $data['session_id'],
            $data['event_type'],
            $data['page_url'],
            $jsonData
        );

        $stmt->execute();
        echo json_encode(["status" => "created", "id" => $stmt->insert_id]);
        break;

    // PUT update event_type only (simple version)
    case "PUT":
        parse_str(file_get_contents("php://input"), $putData);

        $stmt = $mysqli->prepare("UPDATE events SET event_type = ? WHERE id = ?");
        $stmt->bind_param("si", $putData['event_type'], $putData['id']);
        $stmt->execute();

        echo json_encode(["status" => "updated"]);
        break;

    // DELETE
    case "DELETE":
        parse_str(file_get_contents("php://input"), $deleteData);

        $stmt = $mysqli->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $deleteData['id']);
        $stmt->execute();

        echo json_encode(["status" => "deleted"]);
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
}
