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

    foreach ($getClubTeamsResponse->getTeamEntries() as $team) {
        $divisionId = $team->getDivisionId();


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

    // L'API SOAP de l'AFTT met une a quatre secondes a repondre. Le CDN
    // garde donc la reponse une heure, puis sert la version perimee
    // pendant 24 h en la rafraichissant en arriere-plan.
    // Pose ici et non en tete de fichier : une erreur 500 ne doit
    // jamais etre mise en cache.
    header('Cache-Control: public, s-maxage=3600, stale-while-revalidate=604800');
    echo json_encode([
        'success' => true,
        'clubName' => $getClubTeamsResponse->getClubName(),
        'clubId' => $clubId,
        'count' => count($divisions),
        'data' => $divisions
    ]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
