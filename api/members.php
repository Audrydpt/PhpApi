<?php
// Un avertissement PHP affiche du HTML avant le JSON et rend la reponse
// impossible a parser cote client. Les erreurs restent journalisees.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

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

    $params = [];
    if (isset($_GET['club']))        $params['Club']        = $_GET['club'];
    if (isset($_GET['season']))      $params['Season']      = (int)$_GET['season'];
    if (isset($_GET['uniqueIndex'])) $params['UniqueIndex'] = (int)$_GET['uniqueIndex'];
    if (isset($_GET['nameSearch']))  $params['NameSearch']  = $_GET['nameSearch'];
    if (isset($_GET['withResults'])) $params['WithResults'] = filter_var($_GET['withResults'], FILTER_VALIDATE_BOOLEAN);

    if (empty($params['Club']) && empty($params['UniqueIndex']) && empty($params['NameSearch'])) {
        $params['Club'] = 'H442';
    }

    $getMembersResponse = $tabt->members()->listMembersBy($params);

    $members = [];
    foreach ($getMembersResponse->getMemberEntries() as $member) {
        $members[] = [
            'position'           => $member->getPosition(),
            'uniqueIndex'        => $member->getUniqueIndex(),
            'rankingIndex'       => $member->getRankingIndex(),
            'firstName'          => $member->getFirstName(),
            'lastName'           => $member->getLastName(),
            'ranking'            => $member->getRanking(),
            'status'             => $member->getStatus(),
            'club'               => $member->getClub(),
            'gender'             => $member->getGender(),
            'category'           => $member->getCategory(),
            'birthDate'          => $member->getBirthDate(),
            'medicalAttestation' => $member->getMedicalAttestation(),
            'email'              => $member->getEmail(),
            'nationalNumber'     => $member->getNationalNumber(),
            // Resultats individuels du joueur, renvoyes par TabT lorsque
            // ?withResults=1 est passe. Ils etaient recuperes par la
            // bibliotheque puis jetes ici avant serialisation.
            'resultCount'        => $member->getResultCount(),
            'resultEntries'      => $member->getResultEntries(),
        ];
    }

    echo json_encode([
        'success' => true,
        'clubId'  => $params['Club'] ?? null,
        'count'   => count($members),
        'data'    => $members,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ]);
}