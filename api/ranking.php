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
use Yoerioptr\TabtApiClient\Request\GetDivisionRankingRequest;

function getClubDivisions() {
    // Simuler l'appel à club-divisions.php
    $clubDivisionsUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/club-divisions.php';

    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'ignore_errors' => true
        ]
    ]);

    $response = file_get_contents($clubDivisionsUrl, false, $context);

    if ($response === false) {
        throw new Exception('Impossible de récupérer les divisions du club');
    }

    $data = json_decode($response, true);

    if (!$data || !$data['success']) {
        throw new Exception('Erreur lors de la récupération des divisions: ' . ($data['error'] ?? 'Erreur inconnue'));
    }

    return $data['data'];
}

try {
    $client = new Client();
    $credentials = new CredentialsType('username', 'password');
    $client->setCredentials($credentials);

    $divisionId = $_GET['divisionId'] ?? null;
    $allDivisions = $_GET['all'] ?? false;

    if ($allDivisions) {
        // Récupérer les divisions via l'API club-divisions
        $clubDivisions = getClubDivisions();

        $allRankings = [];
        $errors = [];

        foreach ($clubDivisions as $division) {
            $divId = $division['divisionId'];

            try {
                $request = new GetDivisionRankingRequest(['DivisionId' => $divId]);
                $getDivisionRankingResponse = $client->handleRequest($request);

                $ranking = [];
                foreach ($getDivisionRankingResponse->getRankingEntries() as $rank) {
                    $ranking[] = [
                        'position' => $rank->getPosition(),
                        'team' => $rank->getTeam(),
                        'teamClub' => $rank->getTeamClub(),
                        'gamesPlayed' => $rank->getGamesPlayed(),
                        'gamesWon' => $rank->getGamesWon(),
                        'gamesLost' => $rank->getGamesLost(),
                        'gamesDraw' => $rank->getGamesDraw(),
                        'individualMatchesWon' => $rank->getIndividualMatchesWon(),
                        'individualMatchesLost' => $rank->getIndividualMatchesLost(),
                        'individualSetsWon' => $rank->getIndividualSetsWon(),
                        'individualSetsLost' => $rank->getIndividualSetsLost(),
                        'points' => $rank->getPoints()
                    ];
                }

                $allRankings[] = [
                    'divisionId' => $divId,
                    'divisionName' => $division['divisionName'],
                    'divisionCategory' => $division['divisionCategory'],
                    'matchType' => $division['matchType'],
                    'ranking' => $ranking
                ];

            } catch (Exception $e) {
                $errors[] = [
                    'divisionId' => $divId,
                    'divisionName' => $division['divisionName'],
                    'error' => $e->getMessage()
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'clubId' => 'H442',
            'count' => count($allRankings),
            'data' => $allRankings,
            'errors' => $errors
        ]);

    } elseif ($divisionId) {
        // Récupérer le classement d'une division spécifique
        $request = new GetDivisionRankingRequest(['DivisionId' => $divisionId]);
        $getDivisionRankingResponse = $client->handleRequest($request);

        $ranking = [];
        foreach ($getDivisionRankingResponse->getRankingEntries() as $rank) {
            $ranking[] = [
                'position' => $rank->getPosition(),
                'team' => $rank->getTeam(),
                'teamClub' => $rank->getTeamClub(),
                'gamesPlayed' => $rank->getGamesPlayed(),
                'gamesWon' => $rank->getGamesWon(),
                'gamesLost' => $rank->getGamesLost(),
                'gamesDraw' => $rank->getGamesDraw(),
                'individualMatchesWon' => $rank->getIndividualMatchesWon(),
                'individualMatchesLost' => $rank->getIndividualMatchesLost(),
                'individualSetsWon' => $rank->getIndividualSetsWon(),
                'individualSetsLost' => $rank->getIndividualSetsLost(),
                'points' => $rank->getPoints()
            ];
        }

        echo json_encode([
            'success' => true,
            'divisionName' => $getDivisionRankingResponse->getDivisionName(),
            'divisionId' => $divisionId,
            'count' => count($ranking),
            'data' => $ranking
        ]);

    } else {
        throw new Exception('Paramètre divisionId requis ou utilisez ?all=true pour toutes les divisions');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
