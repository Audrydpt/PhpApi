<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require __DIR__ . '/../vendor/autoload.php';

use Yoerioptr\TabtApiClient\Client\Client;
use Yoerioptr\TabtApiClient\Entries\CredentialsType;
use Yoerioptr\TabtApiClient\Tabt;

try {
    $client = new Client();
    $credentials = new CredentialsType('username', 'password');
    $client->setCredentials($credentials);

    $tabt = new Tabt($client);
    $getClubTeamsResponse = $tabt->club()->listTeamsByClub('H442');

    $teams = [];
    foreach ($getClubTeamsResponse->getTeamEntries() as $team) {
        $teams[] = [
            'teamId' => $team->getTeamId(),
            'team' => $team->getTeam(),
            'divisionId' => $team->getDivisionId(),
            'divisionName' => $team->getDivisionName(),
            'divisionCategory' => $team->getDivisionCategory(),
            'matchType' => $team->getMatchType()
        ];
    }

    echo json_encode([
        'success' => true,
        'clubName' => $getClubTeamsResponse->getClubName(),
        'count' => $getClubTeamsResponse->getTeamCount(),
        'data' => $teams
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
