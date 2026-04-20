<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require __DIR__ . '/../vendor/autoload.php';

use Yoerioptr\TabtApiClient\Client\Client;
use Yoerioptr\TabtApiClient\Entries\CredentialsType;
use Yoerioptr\TabtApiClient\Request\GetMembersRequest;

try {
    $client = new Client();
    $credentials = new CredentialsType('username', 'password');
    $client->setCredentials($credentials);

    $club = $_GET['club'] ?? null;

    $params = $club ? ['Club' => $club] : [];
    $request = new GetMembersRequest($params);
    $response = $client->handleRequest($request);

    echo json_encode([
        'success' => true,
        'club' => $club ?? 'all',
        'totalPlayers' => $response->getMemberCount()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
