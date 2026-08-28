<?php
// Un avertissement PHP affiche du HTML avant le JSON et rend la reponse
// impossible a parser cote client. Les erreurs restent journalisees.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

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

    // Club interroge : ?club=XNNN, avec CTT Frameries par defaut.
    $clubId = isset($_GET['club']) && $_GET['club'] !== ''
        ? $_GET['club']
        : 'H442';

    // Tabt n'expose pas club() mais clubs() : cet appel levait un
    // "Call to undefined method" a chaque requete.
    $getClubTeamsResponse = $tabt->clubs()->listTeamsByClub($clubId);

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

    // L'API SOAP de l'AFTT met une a quatre secondes a repondre. Le CDN
    // garde donc la reponse une heure, puis sert la version perimee
    // pendant 24 h en la rafraichissant en arriere-plan.
    // Pose ici et non en tete de fichier : une erreur 500 ne doit
    // jamais etre mise en cache.
    header('Cache-Control: public, s-maxage=3600, stale-while-revalidate=604800');
    echo json_encode([
        'success' => true,
        'clubId' => $clubId,
        'clubName' => $getClubTeamsResponse->getClubName(),
        'count' => $getClubTeamsResponse->getTeamCount(),
        'data' => $teams
    ]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
