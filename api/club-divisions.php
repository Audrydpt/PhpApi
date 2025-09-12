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

    // ID du club CTT Frameries
    $clubId = 'H442';

    $getClubTeamsResponse = $tabt->club()->listTeamsByClub($clubId);

    $divisions = [];
    $uniqueDivisions = [];

    foreach ($getClubTeamsResponse->getTeamEntries() as $team) {
        $divisionId = $team->getDivisionId();

        // Éviter les doublons
        if (!in_array($divisionId, $uniqueDivisions)) {
            $uniqueDivisions[] = $divisionId;
            $divisions[] = [
                'divisionId' => $divisionId,
                'divisionName' => $team->getDivisionName(),
                'divisionCategory' => $team->getDivisionCategory(),
                'matchType' => $team->getMatchType()
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'clubName' => $getClubTeamsResponse->getClubName(),
        'clubId' => $clubId,
        'count' => count($divisions),
        'data' => $divisions
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
