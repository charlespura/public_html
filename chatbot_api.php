<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header("Content-Type: application/json");

    // Load .env
    function loadEnv($path) {
        if (!file_exists($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode("=", $line, 2);
            putenv(trim($name) . "=" . trim($value));
        }
    }
    loadEnv(__DIR__ . '/.env');

    // Get Gemini API key
    $apiKey = getenv("GEMINI_API_KEY");
    if (!$apiKey) {
        echo json_encode(["error" => "API key not found"]);
        exit;
    }

    // Handle input
    $input = json_decode(file_get_contents("php://input"), true);
    $userMessage = $input["message"] ?? "Hello";

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent";

    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $userMessage]
                ]
            ]
        ]
    ];

    $options = [
        "http" => [
            "header"  => "Content-Type: application/json\r\n" .
                         "X-goog-api-key: $apiKey\r\n",
            "method"  => "POST",
            "content" => json_encode($data),
        ]
    ];

    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === FALSE) {
        echo json_encode(["error" => "API request failed"]);
        exit;
    }

    echo $response;
    exit;
}
?>