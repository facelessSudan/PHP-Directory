<?php
namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Monolog\Logger;

class GoogleSheetsService {
    private $client;
    private $service;
    private $spreadsheetId;
    private $logger;

    public function __construct() {
        global $log;
	$this->logger = $log;
	$this->spreadsheetId = $_ENV['GOOGLE_SHEETS_SPREADSHEET_ID'];

	$this->client = new Client();
	$this->client->setAuthConfig($_ENV['GOOGLE_APPLICATION_CREDENTIALS']);
	$this->client->addScope(Sheets::SPREADSHEETS);
	$this->service = new Sheets($this->client);
    }

    public function saveCandidateData(array $candidateData, array $analysisResult): bool {
        $range = 'Candidate!A1';
	$values = [
	    [
	        date('Y-m-d H:i:s'),
		$candidateData['name'],
		$candidateData['email'],
		$candidateData['phone'],
		$analysisResult['score'],
		$analysisResult['is_match'] ? 'Yes' : 'No',
		implode(', ', $analysisResult['identified_skills']),
		implode(', ', $analysisResult['missing_skills']),
		$analysisResult['recommendation']
	    ]
	];

	$body = new ValueRange(['values' => $values]);
	$params = ['valueInputOption' => 'RAW'];

	try {
	    $this->service->spreadsheets_values->append(
	        $this->spreadsheetId,
		$range,
		$body,
		$params
	    );
	    return true;
	} catch (\Exception $e) {
	    $this->logger->error('Google Sheets error: ' . $e->getMessage());
	    return false;
	}
    }
}
