<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
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

    $province = $_GET['province'] ?? null;

    if (!$province) {
        throw new Exception("Paramètre 'province' requis (H, Lx, L, BBW, N)");
    }

    $validProvinces = ['H', 'Lx', 'L', 'BBW', 'N'];
    if (!in_array($province, $validProvinces)) {
        throw new Exception("Province invalide. Utilisez: " . implode(', ', $validProvinces));
    }

    $params = [];
    $request = new GetClubsRequest($params);
    $getClubsResponse = $client->handleRequest($request);

    $clubsWithVenues = [];
    $totalClubsFound = 0;
    $debugInfo = [];

    foreach ($getClubsResponse->getClubEntries() as $club) {
        $clubId = $club->getUniqueIndex();

        // Debug: vérifier les méthodes disponibles sur le premier club
        if (count($debugInfo) === 0) {
            $debugInfo = [
                'clubClass' => get_class($club),
                'availableMethods' => get_class_methods($club),
                'hasGetVenueEntries' => method_exists($club, 'getVenueEntries')
            ];
        }

        if (strpos($clubId, $province) === 0) {
            $totalClubsFound++;

            // Vérification explicite de l'existence de la méthode
            if (method_exists($club, 'getVenueEntries')) {
                $venueEntries = $club->getVenueEntries();

                if (!empty($venueEntries)) {
                    $venues = [];
                    foreach ($venueEntries as $venue) {
                        // Fonction helper pour récupérer une propriété de manière sécurisée
                        $getProperty = function($getter, $fallback = '') use ($venue) {
                            try {
                                return $venue->$getter();
                            } catch (Error $e) {
                                return $fallback;
                            }
                        };

                        $name = $getProperty('getName');
                        $street = $getProperty('getStreet');
                        $town = $getProperty('getTown');
                        $phone = $getProperty('getPhone');
                        $comment = $getProperty('getComment');

                        $venues[] = [
                            'name' => $name,
                            'street' => $street,
                            'town' => $town,
                            'phone' => $phone,
                            'comment' => $comment,
                            'fullAddress' => trim($street . ', ' . $town, ', ')
                        ];
                    }

                    $clubsWithVenues[] = [
                        'clubId' => $clubId,
                        'clubName' => $club->getName(),
                        'clubLongName' => $club->getLongName(),
                        'venueCount' => $club->getVenueCount(),
                        'venues' => $venues
                    ];
                }
            } else {
                // La méthode n'existe pas encore
                $debugInfo['methodMissing'] = true;
            }
        }
    }

    // L'API SOAP de l'AFTT met une a quatre secondes a repondre. Le CDN
    // garde donc la reponse un jour, puis sert la version perimee
    // pendant une semaine en la rafraichissant en arriere-plan.
    // Pose ici et non en tete de fichier : une erreur 500 ne doit
    // jamais etre mise en cache.
    header('Cache-Control: public, s-maxage=86400, stale-while-revalidate=604800');
    echo json_encode([
        'success' => true,
        'province' => $province,
        'totalClubsInProvince' => $totalClubsFound,
        'clubsWithVenues' => count($clubsWithVenues),
        'debug' => $debugInfo,
        'clubs' => $clubsWithVenues
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ], JSON_UNESCAPED_UNICODE);
}
