<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Monolog\Logger;

class EmailService {
    private $mailer;
    private $logger;
    private $databaseService;

    public function __construct(?DatabaseService $databaseService = null) {
        global $log;
        $this->logger = $log;
        $this->databaseService = $databaseService ?? new DatabaseService();
        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }

    private function configureMailer(): void {
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = $_ENV['SMTP_HOST'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $_ENV['SMTP_USERNAME'];
            $this->mailer->Password = $_ENV['SMTP_PASSWORD'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $_ENV['SMTP_PORT'];
            $this->mailer->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
        } catch (PHPMailerException $e) {
            $this->logger->error('Mailer configuration error: ' . $e->getMessage());
        }
    }

    public function sendCandidateEmail(array $candidateData, array $analysisResult): bool {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($candidateData['email']);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Your Application Status';
            $this->mailer->Body = $this->generateCandidateEmailBody($candidateData, $analysisResult);
            return $this->mailer->send();
        } catch (PHPMailerException $e) {
            $this->logger->error('Candidate email error: ' . $e->getMessage());
            return false;
        }
    }

    public function notifyRecruiter(array $candidateData, array $analysisResult, string $resumePath): bool {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($_ENV['RECRUITER_EMAIL']);
            $this->mailer->Subject = sprintf(
                "New Candidate: %s for %s (%d%% match)",
                $candidateData['name'],
                $this->getJobTitle($analysisResult['job_id'] ?? 0),
                $analysisResult['score']
            );
            $this->mailer->addAttachment($resumePath);
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->generateRecruiterEmailBody($candidateData, $analysisResult);
            return $this->mailer->send();
        } catch (PHPMailerException $e) {
            $this->logger->error('Recruiter notification failed: ' . $e->getMessage());
            return false;
        }
    }

    private function generateCandidateEmailBody(array $candidateData, array $analysisResult): string {
        $matchPercentage = $analysisResult['score'];
        $recommendation = strtolower($analysisResult['recommendation'] ?? 'default');
        $jobTitle = $this->getJobTitle($analysisResult['job_id'] ?? 0);

        $statusClass = match($recommendation) {
            'interview' => "status-interview",
            'reject'    => "status-rejected",
            default     => "status-pending"
        };

        $statusHeader = match($recommendation) {
            'interview' => "Congratulations! Application approved for interview",
            'reject'    => "Application status update",
            default     => "Application received pending review."
        };

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        .status-interview { color: #28a745; }  /* Green for interview */
        .status-rejected { color: #dc3545; }   /* Red for rejected */
        .status-pending { color: #ffc107; }    /* Yellow for pending */
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 class="{$statusClass}">{$statusHeader}</h2>
            <p>Position: <strong>{$jobTitle}</strong></p>
        </div>
        
        <div class="results">
            <h3>Your Results</h3>
            <p>Match Score: <span class="{$statusClass}">{$matchPercentage}%</span></p>
            
            {$this->getRecommendationContent($recommendation, $analysisResult)}
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function generateRecruiterEmailBody(array $candidateData, array $analysisResult): string {
        $score = $analysisResult['score'];
        $scoreClass = $score >= 80 ? 'match-good' : ($score >= 50 ? 'match-average' : 'match-poor');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { background-color: #f0f0f0; padding: 15px; }
        .match-good { color: #28a745; }
        .match-average { color: #ffc107; }
        .match-poor { color: #dc3545; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>New Candidate Analysis</h2>
        </div>
        
        <h3>Candidate Details:</h3>
        <ul>
            <li><strong>Name:</strong> {$candidateData['name']}</li>
            <li><strong>Email:</strong> {$candidateData['email']}</li>
            <li><strong>Phone:</strong> {$candidateData['phone'] ?? 'Not provided'}</li>
            <li><strong>Match Score:</strong> <span class="{$scoreClass}">{$score}%</span></li>
        </ul>
        
        <h3>Skills Analysis:</h3>
        <table>
            <tr>
                <th>Identified Skills</th>
                <th>Missing Skills</th>
            </tr>
            <tr>
                <td>{$this->formatSkillsList($analysisResult['identified_skills'])}</td>
                <td>{$this->formatSkillsList($analysisResult['missing_skills'])}</td>
            </tr>
        </table>
        
        <h3>AI Recommendation:</h3>
        <p><strong>{$analysisResult['recommendation']}</strong> - {$analysisResult['explanation']}</p>
    </div>
</body>
</html>
HTML;
    }

    private function getJobTitle(int $jobId): string {
        // Delegate to DatabaseService
        return $this->databaseService->getJobTitleById($jobId); 
    }

    private function getRecommendationContent(string $recommendation, array $analysisResult): string {
        return match($recommendation) {
            'interview' => <<<HTML
                <div class="interview-notice">
                    <h4>Next Steps:</h4>
                    <p>Our hiring team will contact you within 48 hours to schedule an interview.</p>
                    <p>Key strengths we noticed:</p>
                    <ul>
                        <li>{$this->formatSkillsList($analysisResult['strengths'])}</li>
                    </ul>
                </div>
                HTML,
            
            'reject' => <<<HTML
                <div class="rejection-notice">
                    <p>After careful consideration, we've decided not to move forward with your application.</p>
                    <p>Feedback from our system:</p>
                    <ul>
                        <li>{$analysisResult['explanation']}</li>
                    </ul>
                </div>
                HTML,
            
            default => <<<HTML
                <div class="pending-notice">
                    <p>Your application is under review. We'll notify you once a decision is made.</p>
                    <p>Current assessment:</p>
                    <ul>
                        <li>Match Score: {$analysisResult['score']}%</li>
                        <li>Identified Skills: {$this->formatSkillsList($analysisResult['identified_skills'])}</li>
                    </ul>
                </div>
                HTML
        };
    }

    private function formatSkillsList(array $skills): string {
        if (empty($skills)) {
            return '<em>None identified</em>';
        }
        return '<ul><li>' . implode('</li><li>', array_map('htmlspecialchars', $skills)) . '</li></ul>';
    }
}
