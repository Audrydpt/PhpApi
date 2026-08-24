<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Empêcher tout bruit dans la sortie JSON
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

require __DIR__ . '/../vendor/autoload.php';

use Yoerioptr\TabtApiClient\Client\Client;
use Yoerioptr\TabtApiClient\Entries\CredentialsType;
use Yoerioptr\TabtApiClient\Tabt;

try {
    $client = new Client();
    $credentials = new CredentialsType('username', 'password');
    $client->setCredentials($credentials);

    $tabt = new Tabt($client);

    // Club interroge : ?club=XNNN, avec CTT Frameries par defaut.
    // Etait ecrit en dur, ce qui rendait le parametre sans effet.
    $clubId = isset($_GET['club']) && $_GET['club'] !== ''
        ? $_GET['club']
        : 'H442';

    // Correction: utiliser clubs() (et non club())
    $getClubTeamsResponse = $tabt->clubs()->listTeamsByClub($clubId);

    $divisions = [];
    $uniqueDivisions = [];
    $teams = [];

    foreach ($getClubTeamsResponse->getTeamEntries() as $team) {
        $divisionId = $team->getDivisionId();

        // Une entree par equipe, sans deduplication : deux equipes du meme
        // club peuvent partager une division (Frameries I et M sont toutes
        // deux en 9661). Dedupliquer par divisionId en fait disparaitre une.
        $teams[] = [
            'teamId' => $team->getTeamId(),
            'team' => $team->getTeam(),
            'divisionId' => $divisionId,
            'divisionName' => $team->getDivisionName(),
            'divisionCategory' => $team->getDivisionCategory(),
            'matchType' => $team->getMatchType()
        ];

        // Éviter les doublons
        if (!in_array($divisionId, $uniqueDivisions, true)) {
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
        'data' => $divisions,
        // Ajout additif : `data` garde sa forme historique, `teams` porte
        // la lettre d'equipe, seule facon de relier une equipe a sa division.
        'teamCount' => count($teams),
        'teams' => $teams
    ]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
