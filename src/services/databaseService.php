<?php
namespace App\Services;

use PDO;
use PDOException;
use Monolog\Logger;

class DatabaseService {
    private $pdo;
    private $logger;

    public function __construct() {
        global $log;
	$this->logger = $log;
	$this->connect();
    }

    private function connect(): void {
        try {
	    $host = $_ENV['DB_HOST'] ?? 'localhost';
	    $dbname = $_ENV['DB_NAME'] ?? 'recruitment_db';
	    $user = $_ENV['DB_USER'] ?? 'root';
	    $passwd = $_ENV['DB_PASSWD'] ?? '';

	    $this->pdo = new PDO (
	        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
		$user,
		$passwd,
		[
		    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		    PDO::ATTR_EMULATE_PREPARES => false
		]
	    );
	} catch (PDOException $e) {
	    $this->logger->error('Database connection failed: ' . $e->getMessage());
	    throw new \RuntimeException('Database connection failed');
	}
    }

    /**
     * Fetch job Description by ID
     */
    public function fetchJobDescription (int $jobId): ?array {
        try {
	    $stmt = $this->pdo->prepare ("
	        SELECT
		    id,
		    title,
		    description,
		    required_skills,
		    preferred_skills,
		    min_experience_years
		FROM job_descriptions
		WHERE id = :id
	    ");
	    $stmt->execute([':id' => $jobId]);
	    $job = $stmt->fetch();

	    if ($job) {
	        // Convert comma-separated skills into arrays
		$job['required_skills'] = $job['required_skills'] ?
		    array_map('trim', explode(',', $job['required_skills'])) : [];
		$job['preferred_skills'] = $job['preferred_skills'] ?
		    array_map('trim', explode(',', $job['preferred_skills'])) : [];
	    }

	    return $job ?: null;
	} catch (PDOException $e) {
	    $this->logger->error('Failed to fetch job description: ' . $e->getMessage());
	    throw new \RuntimeException('Failed to fetch job description');
	}
    }

    /**
     * Save resume metadata to database
     */
    public function saveResumeMetadata(array $resumeData): int {
        try {
	    $stmt = $this->pdo->prepare ("
	        INSERT INTO resumes
		(candidate_email, file_name, file_path, file_size, upload_date)
		VALUES
		(:email, :name, :path, :size, NOW())
	    ");

	    $stmt->execute([
	        ':email' => $resumeData['email'],
	        ':name' => $resumeData['file_name'],
		':path' => $resumeData['file_path'],
		':size' => $resumeData['file_size']
	    ]);

	    return $this->pdo->lastInsertId();
	} catch (PDOException $e) {
	    $this->logger->error('Failed to save resume metadata: ' . $e->getMessage());
	    throw new \RuntimeException('Failed to save resume metadata');
	}
    }

    /**
     * Get resume path by ID
     */
    public function getResumePath(int $resumeId): ?string {
        try {
	    $stmt = $this->pdo->prepare ("
	        SELECT file_path FROM resumes WHERE id = :id
       	    ");
	    $stmt->execute([':id' => $resumeId]);
	    $result = $stmt->fetch();

	    return $result['file_path'] ?? null;
	} catch (PDOException $e) {
	    $this->logger->error('Failed to fetch resume path: ' . $e->getMessage());
	    throw new \RuntimeException('Failed to fetch resume path');
	}
    }

    /**
     * Mark resume as processed in database
     */
    public function markResumeProcessed(string $filePath): bool {
        try{
	    $stmt = $this->pdo->prepare("
		UPDATE resumes
		SET processed = 1, processed_at = NOW()
		WHERE file_path = :path
	    ");
	    return $stmt->execute([':path' => $filePath]);
	} catch (PDOException $e) {
	    $this->logger->error('Failed to mark resume as processed: ' . $e->getMessage());
	    return false;
	}
    }

    /**
     * Get unprocessed resume
     */
    public function getUprocessedResumes(int $limit = 10): array {
        try {
	    $stmt =$this->pdo->prepare("
	        SELECT * FROM resumes
		WHERE Processed = 0
		ORDER BY uploaded_date ASC
		LIMIT :limit
	    ");
	    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
	    $stmt->execute();

	    return $stmt->fetchAll();
	} catch (PDOException $e) {
	    $this->logger->error('Failed to fetch unprocessed resume: ' . $e->getMessage());
	    return [];
	}
    }

    /**
     * Save candidate analysis results
     */
    public function saveAnalysisResults(array $candidateData, array $analysisResults): bool {
        try {
	    $stmt =$this->pdo->prepare("
		INSERT INTO candidate_analysis
		(resume_id, candidate_email, job_id, match_score, is_match,
		identified_skills, missing_skills, recommendation, analyzed_at)
                VALUES
		(:resume_id, :email, :job_id, :score, :is_match, :identified_skills,
		:missing_skills, :recommendation, NOW())
	    ");

	    return $stmt->execute([
	        ':resume_id' => $candidateData['resume_id'] ?? null,
		':email' => $candidateData['email'],
		':job_id' => $candidateData['job_id'],
		':score' => $analysisResults['score'],
		':is_match' => $analysisResults['is_match'] ? 1 : 0,
		':identified_skills' => implode(',', $analysisResults['identified_skills']),
		':missing_skills' => implode(',', $analysisResults['missing_skills']),
		':recommendation' => $analysisResults['recommendation'],
	    ]);
	} catch (PDOException $e) {
            $this->logger->error('Failed to save analysis results: ' . $e->getMessage());
	    return false;
	}
    }
}
