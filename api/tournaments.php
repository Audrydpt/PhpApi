<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require __DIR__ . '/../vendor/autoload.php';

use Yoerioptr\TabtApiClient\Client\Client;
use Yoerioptr\TabtApiClient\Entries\CredentialsType;
use Yoerioptr\TabtApiClient\Request\GetTournamentsRequest;

try {
    $client = new Client();
    $credentials = new CredentialsType('username', 'password');
    $client->setCredentials($credentials);

    $tournamentId = $_GET['tournamentId'] ?? null;
    $season = $_GET['season'] ?? null;

    $params = [];
    if ($tournamentId) $params['TournamentUniqueIndex'] = $tournamentId;
    if ($season) $params['Season'] = $season;

    $request = new GetTournamentsRequest($params);
    $getTournamentsResponse = $client->handleRequest($request);

    $tournaments = [];
    foreach ($getTournamentsResponse->getTournamentEntries() as $tournament) {
        $tournaments[] = [
            'uniqueIndex' => $tournament->getUniqueIndex(),
            'name' => $tournament->getName(),
            'dateFrom' => $tournament->getDateFrom()?->format('Y-m-d'),
            'dateTo' => $tournament->getDateTo()?->format('Y-m-d'),
            'registrationDate' => $tournament->getRegistrationDate()?->format('Y-m-d'),
            'venue' => $tournament->getVenue(),
            'address' => $tournament->getAddress()
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
