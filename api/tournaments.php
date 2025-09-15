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

    $tournamentId = $_GET['tournamentId'] ?? null;
    $season = $_GET['season'] ?? null;

    $params = [];
    if ($tournamentId) $params['TournamentUniqueIndex'] = $tournamentId;
    if ($season) $params['Season'] = $season;

    $getTournamentsResponse = $tabt->tournaments()->listTournamentsBy($params);

    $tournaments = [];
    foreach ($getTournamentsResponse->getTournamentEntries() as $tournament) {
        $tournaments[] = [
            'uniqueIndex' => $tournament->getUniqueIndex(),
            'name' => $tournament->getName(),
            'dateFrom' => $tournament->getDateFrom()?->format('Y-m-d'),
            'dateTo' => $tournament->getDateTo()?->format('Y-m-d'),
            'registrationDate' => $tournament->getRegistrationDate()?->format('Y-m-d'),
            'venue' => $tournament->getVenue(),
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($tournaments),
        'data' => $tournaments
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
