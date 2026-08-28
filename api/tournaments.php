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
            // TabT renvoie le lieu en JSON encode dans une chaine : sans ce
            // decodage, tout client doit parser deux fois. Repli sur la valeur
            // brute si ce n'est pas du JSON valide.
            'venue' => (static function ($raw) {
                if (!is_string($raw) || $raw === '') {
                    return $raw;
                }
                $decoded = json_decode($raw, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
            })($tournament->getVenue()),
        ];
    }

    // L'API SOAP de l'AFTT met une a quatre secondes a repondre. Le CDN
    // garde donc la reponse une heure, puis sert la version perimee
    // pendant 24 h en la rafraichissant en arriere-plan.
    // Pose ici et non en tete de fichier : une erreur 500 ne doit
    // jamais etre mise en cache.
    header('Cache-Control: public, s-maxage=3600, stale-while-revalidate=604800');
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
