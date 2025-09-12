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
    $getMatchesResponse = $tabt->match()->listMatchesByClub('H442');

    $matches = [];
    foreach ($getMatchesResponse->getTeamMatchesEntries() as $match) {
        $matchData = [];
        $reflection = new ReflectionClass($match);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if (strpos($method->getName(), 'get') === 0 && $method->getNumberOfParameters() === 0) {
                try {
                    $value = $method->invoke($match);
                    if ($value instanceof DateTime) {
                        $value = $value->format('Y-m-d H:i:s');
                    }
                    if ($value === null || $value === '') {
                        $value = null;
                    }
                    $matchData[lcfirst(substr($method->getName(), 3))] = $value;
                } catch (Error $e) {
                    if (strpos($e->getMessage(), 'must not be accessed before initialization') !== false) {
                        $matchData[lcfirst(substr($method->getName(), 3))] = null;
                    }
                }
            }
        }
        $matches[] = $matchData;
    }

    echo json_encode([
        'success' => true,
        'count' => $getMatchesResponse->getMatchCount(),
        'data' => $matches
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
