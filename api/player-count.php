<?php
error_reporting(E_ERROR | E_PARSE);
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require __DIR__ . '/../vendor/autoload.php';

use Yoerioptr\TabtApiClient\Client\Client;
use Yoerioptr\TabtApiClient\Entries\CredentialsType;
use Yoerioptr\TabtApiClient\Tabt;

try {
    $client = new Client();
    $credentials = new CredentialsType('username', 'password');
    $client->setCredentials($credentials);

    $tabt = new Tabt($client);

    $params = [];
    $club = $_GET['club'] ?? null;
    if ($club) {
        $params['Club'] = $club;
    }

    $getMembersResponse = $tabt->member()->listMembersBy($params);

    $count = count($getMembersResponse->getMemberEntries());

    echo json_encode([
        'success' => true,
        'club' => $club ?? 'all',
        'totalPlayers' => $count
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
