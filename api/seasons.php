<?php
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
    $getSeasonsResponse = $tabt->seasons()->listSeasons();

    $seasons = [];
    foreach ($getSeasonsResponse->getSeasonEntries() as $season) {
        $seasons[] = [
            'season' => $season->getSeason(),
            'name' => $season->getName(),
            'isCurrent' => $season->isCurrent(),
        ];
    }

    echo json_encode([
        'success' => true,
        'currentSeason' => $getSeasonsResponse->getCurrentSeason(),
        'currentSeasonName' => $getSeasonsResponse->getCurrentSeasonName(),
        'count' => count($seasons),
        'data' => $seasons,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
