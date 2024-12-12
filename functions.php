<?php

function uploadFile($file)
{
    $uploadDir = __DIR__ . "/uploads/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = uniqid() . "_" . basename($file["name"]);
    $targetFile = $uploadDir . $fileName;

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        $fileSize = filesize($targetFile);
        $fileType = mime_content_type($targetFile);

        $pdo = getPDO();

        // Generate a unique 6-digit alphanumeric access code
        do {
            $accessCode = generateAccessCode();
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM files WHERE access_code = ?"
            );
            $stmt->execute([$accessCode]);
            $exists = $stmt->fetchColumn() > 0;
        } while ($exists);

        // Insert file details along with the access code
        $stmt = $pdo->prepare(
            "INSERT INTO files (filename, original_filename, file_size, file_type, access_code) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $fileName,
            $file["name"],
            $fileSize,
            $fileType,
            $accessCode,
        ]);

        $fileLink = "/uploads/" . $fileName;

        // Return both the file link and access code
        return json_encode([
            "file_link" => $fileLink,
            "access_code" => $accessCode,
        ]);
    }

    return false;
}

function generateAccessCode()
{
    return substr(
        str_shuffle(
            "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"
        ),
        0,
        6
    );
}

function getFile($fileId)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        "SELECT id, filename, original_filename, file_size, file_type, view_count, download_count,uploaded_at,access_code  FROM files WHERE access_code = ?"
    );
    $stmt->execute([$fileId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($file) {
        $file["link"] = "/uploads/" . $file["filename"];
    }

    return $file;
}

function incrementViewCount($fileId)
{
    $pdo = getPDO();
    // Increment the view count in the database
    $stmt = $pdo->prepare("UPDATE files SET view_count = view_count + 1 WHERE id = ?");
    $stmt->execute([$fileId]);

    // You could also return some value or log a message if needed
    return true;
}

function incrementDownloadCount($fileId)
{
    $pdo = getPDO();
    // Increment the download count in the database
    $stmt = $pdo->prepare("UPDATE files SET download_count = download_count + 1 WHERE access_code = ?");
    $stmt->execute([$fileId]);

    // You can return some value or log a message if needed
    return true;
}

function FetchStats()
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as total_files,
                SUM(view_count) as total_views,
                SUM(download_count) as total_downloads
         FROM files"
    );
    $stmt->execute();
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    $response = [
        "total_files" => $file["total_files"],
        "total_views" => $file["total_views"],
        "total_downloads" => $file["total_downloads"],
    ];

    return json_encode($response);
}

function getPDO()
{
    static $pdo;

    if (!$pdo) {
        $dsn = "mysql:host={$_ENV["DB_HOST"]};dbname={$_ENV["DB_NAME"]};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, $_ENV["DB_USER"], $_ENV["DB_PASS"], $options);
    }

    return $pdo;
}

?>
