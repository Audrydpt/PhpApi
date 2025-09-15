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

    // Paramètres de filtrage
    $club = $_GET['club'] ?? 'H442'; // défaut: club H442
    $divisionId = isset($_GET['divisionId']) ? (int) $_GET['divisionId'] : null;
    $season = $_GET['season'] ?? null;
    $showDivisionName = $_GET['showDivisionName'] ?? null; // yes|no|short
    $team = $_GET['team'] ?? null; // ex: "A" (ou libellé exact de l'équipe)
    $divisionCategory = isset($_GET['divisionCategory']) ? (int) $_GET['divisionCategory'] : null; // ex: 3, 37, ...

    // Construire la requête générique GetMatches
    $params = [];
    if ($club) $params['Club'] = $club;
    if ($divisionId) $params['DivisionId'] = $divisionId;
    if ($season) $params['Season'] = $season;
    if ($showDivisionName) $params['ShowDivisionName'] = $showDivisionName; // la lib accepte yes|no|short

    // Sécurité: éviter une requête sans aucun filtre (trop volumineuse)
    if (empty($params)) {
        $params['Club'] = 'H442';
    }

    $getMatchesResponse = $tabt->matches()->listMatchesBy($params);

    $matches = [];
    foreach ($getMatchesResponse->getTeamMatchesEntries() as $match) {
        // Filtrage optionnel par divisionCategory (permet de différencier Hommes/Vétérans, etc.)
        if (!is_null($divisionCategory) && $match->getDivisionCategory() !== $divisionCategory) {
            continue;
        }

        // Filtrage optionnel par équipe
        if ($team) {
            $teamNorm = strtolower(trim($team));
            $isLetter = (strlen($teamNorm) === 1 && ctype_alpha($teamNorm));

            $homeClub = $match->getHomeClub();
            $awayClub = $match->getAwayClub();
            $homeTeamNorm = strtolower(trim($match->getHomeTeam() ?? ''));
            $awayTeamNorm = strtolower(trim($match->getAwayTeam() ?? ''));

            $homeClubMatches = ($homeClub === $club);
            $awayClubMatches = ($awayClub === $club);

            $endsWithLetter = static function (string $name, string $letter): bool {
                // correspond si le nom se termine par " A" ou juste "A"
                if ($name === $letter) return true;
                return (bool) preg_match('/(?:^|\s)'.preg_quote($letter, '/').'$/', $name);
            };

            $nameMatchesHome = $isLetter
                ? $endsWithLetter($homeTeamNorm, $teamNorm)
                : ($homeTeamNorm === $teamNorm || str_contains($homeTeamNorm, $teamNorm));

            $nameMatchesAway = $isLetter
                ? $endsWithLetter($awayTeamNorm, $teamNorm)
                : ($awayTeamNorm === $teamNorm || str_contains($awayTeamNorm, $teamNorm));

            $isTeamMatch = ($homeClubMatches && $nameMatchesHome) || ($awayClubMatches && $nameMatchesAway);
            if (!$isTeamMatch) {
                continue;
            }
        }

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
                    // Eviter d'inclure des objets complexes (ex: VenueEntry) qui cassent le JSON
                    if (is_object($value)) {
                        continue;
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
        'filters' => $params + ['Team' => $team, 'DivisionCategory' => $divisionCategory],
        'count' => $getMatchesResponse->getMatchCount(),
        'returned' => count($matches),
        'data' => $matches
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
