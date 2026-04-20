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

    $clubId = 'H442';

    $getMembersResponse = $tabt->member()->listMembersBy(['Club' => $clubId]);

    $players = [];
    foreach ($getMembersResponse->getMemberEntries() as $member) {
        $players[] = [
            'uniqueIndex' => $member->getUniqueIndex(),
            'rankingIndex' => $member->getRankingIndex(),
            'firstName' => $member->getFirstName(),
            'lastName' => $member->getLastName(),
        ];
    }

    echo json_encode([
        'success' => true,
        'clubId' => $clubId,
        'count' => count($players),
        'data' => $players
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
