<?php
// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
// // Handle preflight requests
if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    exit(0);
}

require_once "vendor/autoload.php"; // Autoload Composer dependencies
require_once "functions.php";

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load(); // Load environment variables

// Routes
$requestUri = $_SERVER["REQUEST_URI"];

switch ($requestUri) {
    case "/clanshare_api/upload":
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["file"])) {
            $fileLink = uploadFile($_FILES["file"]);
            if ($fileLink) {
                echo $fileLink;
            } else {
                echo "File upload failed.";
            }
        }
        break;

    case "/clanshare_api/fetch":
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $inputData = json_decode(file_get_contents("php://input"), true);

            if (isset($inputData["file_id"])) {
                $file = getFile($inputData["file_id"]);
                if ($file) {
                    incrementViewCount($file["id"]);
                    echo json_encode($file);
                } else {
                    echo json_encode(["error" => "File not found - $file"]);
                }
            } else {
                echo json_encode(["error" => "File ID not provided."]);
            }
        } else {
            echo json_encode(["error" => "Invalid request method."]);
        }
        break;

    case "/clanshare_api/addViewCount":
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["file_id"])) {
            incrementViewCount($_POST["file_id"]);
            echo "View count updated!";
        }
        break;

        case "/clanshare_api/addDownloadCount":
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $inputData = json_decode(file_get_contents("php://input"), true);
    
                if (isset($inputData["file_id"])) {
                    $file = incrementDownloadCount($inputData["file_id"]);
                    if ($file) {
                        echo json_encode($file);
                    } else {
                        echo json_encode(["error" => "File not found - $file"]);
                    }
                } else {
                    echo json_encode(["error" => "File ID not provided."]);
                }
            } else {
                echo json_encode(["error" => "Invalid request method."]);
            }
            break;

    case "/clanshare_api/fetchStats":
        if ($_SERVER["REQUEST_METHOD"] === "GET") {
            $stats = FetchStats();
            echo $stats;
        }
        break;
    default:
        echo "route not found";
}
?>
