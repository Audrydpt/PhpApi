<?php
error_reporting(E_ERROR | E_PARSE);
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

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
    $credentials = new CredentialsType('username', 'password'); // Remplace par tes identifiants
    $client->setCredentials($credentials);

    $tabt = new Tabt($client);

    $clubId = 'H442'; // Remplace par l'ID de ton club

    $getMembersResponse = $tabt->members()->listMembersBy(['Club' => $clubId]);

$members = [];
    foreach ($getMembersResponse->getMemberEntries() as $member) {
        $memberData = [
            'position' => $member->getPosition(),
            'uniqueIndex' => $member->getUniqueIndex(),
            'rankingIndex' => $member->getRankingIndex(),
            'firstName' => $member->getFirstName(),
            'lastName' => $member->getLastName(),
            'ranking' => $member->getRanking(),
            'status' => $member->getStatus(),
            'club' => $member->getClub(),
            'gender' => $member->getGender(),
            'category' => $member->getCategory(),
            'birthDate' => $member->getBirthDate(),
            'medicalAttestation' => $member->getMedicalAttestation(),
            'rankingPointsCount' => $member->getRankingPointsCount(),
            'rankingPointsEntries' => $member->getRankingPointsEntries(),
            'email' => $member->getEmail(),
            'phone' => $member->getPhone(),
            'address' => $member->getAddress(),
            'resultCount' => $member->getResultCount(),
            'resultEntries' => $member->getResultEntries(),
            'nationalNumber' => $member->getNationalNumber(),
        ];

        $members[] = $memberData;
    }

    echo json_encode([
        'success' => true,
        'clubId' => $clubId,
        'count' => count($members),
        'data' => $members
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
