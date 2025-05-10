<?php
namespace App;

use App\Services\GoogleSheetsService;
use App\Services\EmailService;
use App\Services\DatabaseService;
use Exception;
use Monolog\Logger;

class AIRecruitmentAgent {
    private $openaiApiKey;
    private $googleSheetsService;
    private $emailService;
    private $databaseService;
    private $logger;

    private $matchThreshold;
    private $waitTimeSecond;

    public function __construct(
        string $openaiApiKey,
	GoogleSheetsService $googleSheetsService,
	EmailService $emailService,
	DatabaseService $databaseService
    ) {
        global $log;
	$this->logger = $log;

	$this->openaiApiKey = $openaiApiKey;
	$this->googleSheetsService = $googleSheetsService;
	$this->emailService = $emailService;
	$this->databaseService = $databaseService;

	$this->matchThreshold = (int)($_ENV['MATCH_THRESHOLD'] ?? 70);
	$this->waitTimeSecond = (int)($_ENV['WAIT_TIME_SECONDS'] ?? 5);
    }

    public function processResumeSubmission($resumeFile, array $candidateData, int $jobId): array {
        try {
	    $this->logger->info('Processing resume submission.', [
	        'candidate' => $candidateData['email'],
		'job_id' => $jobId
	    ]);

	    // 1. store resume and metadata uploaded
	    $resumePath = $this->storeResume($resumeFile, $candidateData['email']);
	    $resumeId = $this->databaseService->saveResumeMetadata([
	        'email' => $candidateData['email'],
		'file_name' => $resumeFile['name'],
		'file_path' => $resumePath,
		'file_size' => $resumeFile['size']
	    ]);

	    // 2. Extract text and analyze
	    $resumeText = $this->extractTextFromPDF($resumePath);
	    $jobDescription= $this->databaseService->fetchJobDescription($jobId);

	    // Simulate processing time
	    if ($this->waitTimeSeconds > 0) {
	        sleep($this->waitTimeSeconds);
	    }

	    $analysisResult = $this->analyzeResume($resumeText, $jobDescription);

	    // 3. save results and notify
	    $this->googleSheetsService->saveCandidateData(
	        array_merge($candidateData, ['resume-id' => $resumeId]),
		$analysisResult
	    );

	    $this->databaseService->saveAnalysisResult(
	        array_merge($candidateData, ['resume-id' => $resumeId]),
		$analysisResult
	    );

	    $this->emailService->sendCandidateData($candidateData, $analysisResult);
	    $this->emailService->notifyRecruiter($candidateData, $analysisResult, $resumePath);

	    $this->databaseService->markResumeProcessed($resumePath);

	    return [
	        'success' => true,
		'resume_id' => $resumeId,
		'analysis' => $analysisResult
	    ];
	} catch (Exception $e) {
	    $this->logger->error('Processing error: ' . $e->getMessage());
	    throw $e;
	}
    }

    private function storeResume($resumeFile, string $email): string {
    
    }
}
