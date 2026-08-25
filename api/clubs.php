<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require __DIR__ . '/../vendor/autoload.php';

use Yoerioptr\TabtApiClient\Client\Client;
use Yoerioptr\TabtApiClient\Entries\CredentialsType;
use Yoerioptr\TabtApiClient\Request\GetClubsRequest;

try {
    $client = new Client();
    $credentials = new CredentialsType('username', 'password');
    $client->setCredentials($credentials);

    $category = $_GET['category'] ?? null;
    $season = $_GET['season'] ?? null;

    $params = [];
    if ($category) $params['Category'] = $category;
    if ($season) $params['Season'] = $season;

    $request = new GetClubsRequest($params);
    $getClubsResponse = $client->handleRequest($request);

    $clubs = [];
    foreach ($getClubsResponse->getClubEntries() as $club) {
        $clubs[] = [
            'uniqueIndex' => $club->getUniqueIndex(),
            'name' => $club->getName(),
            'longName' => $club->getLongName(),
            'category' => $club->getCategory(),
            'categoryName' => $club->getCategoryName(),
            'venueCount' => $club->getVenueCount()
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
        'count' => count($clubs),
        'data' => $clubs
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
