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
    $getSeasonsResponse = $tabt->seasons()->listSeasons();

    $seasons = [];
    foreach ($getSeasonsResponse->getSeasonEntries() as $season) {
        $seasons[] = [
            'season' => $season->getSeason(),
            'name' => $season->getName(),
            'isCurrent' => $season->isCurrent(),
        ];
    }

    // L'API SOAP de l'AFTT met une a quatre secondes a repondre. Le CDN
    // garde donc la reponse un jour, puis sert la version perimee
    // pendant une semaine en la rafraichissant en arriere-plan.
    // Pose ici et non en tete de fichier : une erreur 500 ne doit
    // jamais etre mise en cache.
    header('Cache-Control: public, s-maxage=86400, stale-while-revalidate=604800');
    echo json_encode([
        'success' => true,
        'currentSeason' => $getSeasonsResponse->getCurrentSeason(),
        'currentSeasonName' => $getSeasonsResponse->getCurrentSeasonName(),
        'count' => count($seasons),
        'data' => $seasons,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
