<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require __DIR__ . '/../vendor/autoload.php';

use Yoerioptr\TabtApiClient\Client\Client;
use Yoerioptr\TabtApiClient\Entries\CredentialsType;
use Yoerioptr\TabtApiClient\Request\GetDivisionRankingRequest;

function getClubDivisions() {
    // URL absolue vers club-divisions.php dans le même dossier
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $basePath = rtrim(dirname($_SERVER['REQUEST_URI'] ?? '/api'), '/\\');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $clubDivisionsUrl = $scheme . '://' . $host . $basePath . '/club-divisions.php';

    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'ignore_errors' => true,
            'header' => [
                'Accept: application/json'
            ]
        ]
    ]);

    $response = @file_get_contents($clubDivisionsUrl, false, $context);
    $statusLine = isset($http_response_header[0]) ? $http_response_header[0] : '';
    $statusCode = null;
    if ($statusLine && preg_match('#HTTP/\S+\s(\d{3})#', $statusLine, $m)) {
        $statusCode = (int)$m[1];
    }

    if ($response === false) {
        throw new Exception('Impossible de récupérer les divisions du club' . ($statusCode ? " (HTTP $statusCode)" : ''));
    }

    // Tenter de décoder le JSON proprement
    $data = null;
    try {
        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    } catch (\Throwable $e) {
        $short = substr(preg_replace('/\s+/', ' ', $response), 0, 160);
        throw new Exception('Réponse non valide de club-divisions' . ($statusCode ? " (HTTP $statusCode)" : '') . ": $short");
    }

    if (!is_array($data)) {
        throw new Exception('Format de réponse club-divisions invalide');
    }

    if (isset($data['success']) && $data['success'] === false) {
        $msg = isset($data['error']) ? (string)$data['error'] : 'Erreur inconnue';
        throw new Exception('Erreur club-divisions' . ($statusCode ? " (HTTP $statusCode)" : '') . ': ' . $msg);
    }

    if (!array_key_exists('data', $data) || !is_array($data['data'])) {
        throw new Exception('Champ data manquant ou invalide dans la réponse club-divisions');
    }

    return $data['data'];
}

try {
    $client = new Client();
    $credentials = new CredentialsType('username', 'password');
    $client->setCredentials($credentials);

    $divisionId = $_GET['divisionId'] ?? null;
    // Normaliser le booléen (?all=true|false|1|0)
    $allDivisions = filter_var($_GET['all'] ?? null, FILTER_VALIDATE_BOOLEAN);

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

        // L'API SOAP de l'AFTT met une a quatre secondes a repondre. Le CDN
        // garde donc la reponse une heure, puis sert la version perimee
        // pendant 24 h en la rafraichissant en arriere-plan.
        // Pose ici et non en tete de fichier : une erreur 500 ne doit
        // jamais etre mise en cache.
        header('Cache-Control: public, s-maxage=3600, stale-while-revalidate=86400');
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

        // L'API SOAP de l'AFTT met une a quatre secondes a repondre. Le CDN
        // garde donc la reponse une heure, puis sert la version perimee
        // pendant 24 h en la rafraichissant en arriere-plan.
        // Pose ici et non en tete de fichier : une erreur 500 ne doit
        // jamais etre mise en cache.
        header('Cache-Control: public, s-maxage=3600, stale-while-revalidate=86400');
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
