<?php
namespace HBC_POD_LEDGER\Services;

class GitHubService {
    
    private static $token = null;
    private static $owner = null;
    private static $repo = null;
    private static $branch = null;
    private static $logs_path = null;
    
    public static function init() {
        self::$token = get_option('hbc_github_token', '');
        self::$owner = get_option('hbc_github_owner', '');
        self::$repo = get_option('hbc_github_repo', 'SmallStreetApplied-Atlanta');
        self::$branch = get_option('hbc_github_branch', 'main');
        self::$logs_path = get_option('hbc_github_logs_path', 'append-only-ledger/');
    }
    
    /**
     * Append a line to the daily log file in GitHub
     * 
     * @param string $dateYmd Date in YYYY-MM-DD format
     * @param string $line Single-line JSON (NDJSON format)
     * @return array Result with 'success' and optional 'error'
     */
    public static function appendLedgerLine($dateYmd, $line) {
        self::init();
        
        if (empty(self::$token) || empty(self::$owner)) {
            return array(
                'success' => false,
                'error' => 'GitHub token or owner not configured'
            );
        }
        
        // Parse date
        $date_parts = explode('-', $dateYmd);
        if (count($date_parts) !== 3) {
            return array(
                'success' => false,
                'error' => 'Invalid date format. Expected YYYY-MM-DD'
            );
        }
        
        $year = $date_parts[0];
        $month = $date_parts[1];
        
        // Build file path: append-only-ledger/licensing/YYYY/MM/YYYY-MM-DD.log
        $file_path = rtrim(self::$logs_path, '/') . '/licensing/' . $year . '/' . $month . '/' . $dateYmd . '.log';
        
        // Get existing file content (if exists)
        $existing_content = self::getFileContent($file_path);
        
        if ($existing_content === false) {
            // File doesn't exist, create new
            $new_content = $line;
            $sha = null;
        } else {
            // Append to existing content
            $new_content = $existing_content['content'] . "\n" . $line;
            $sha = $existing_content['sha'];
        }
        
        // Extract acceptance_hash from line for commit message
        $line_data = json_decode($line, true);
        $acceptance_hash = isset($line_data['acceptance_hash']) ? substr($line_data['acceptance_hash'], 0, 16) : 'unknown';
        
        // Put updated content
        $result = self::putFileContent($file_path, $new_content, $sha, "Append licensing entry {$acceptance_hash}");
        
        return $result;
    }
    
    /**
     * Get file content from GitHub
     */
    private static function getFileContent($file_path) {
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/contents/%s?ref=%s',
            rawurlencode(self::$owner),
            rawurlencode(self::$repo),
            rawurlencode($file_path),
            rawurlencode(self::$branch)
        );
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'token ' . self::$token,
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress-HBC-PoD-Ledger'
            ),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 404) {
            // File doesn't exist
            return false;
        }
        
        if ($status_code !== 200) {
            error_log('GitHub API error: ' . wp_remote_retrieve_body($response));
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['content']) || !isset($body['sha'])) {
            return false;
        }
        
        // Decode base64 content
        $content = base64_decode($body['content']);
        
        return array(
            'content' => $content,
            'sha' => $body['sha']
        );
    }
    
    /**
     * Put file content to GitHub
     */
    private static function putFileContent($file_path, $content, $sha, $commit_message) {
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/contents/%s',
            rawurlencode(self::$owner),
            rawurlencode(self::$repo),
            rawurlencode($file_path)
        );
        
        $data = array(
            'message' => $commit_message,
            'content' => base64_encode($content),
            'branch' => self::$branch
        );
        
        // If updating existing file, include sha
        if ($sha) {
            $data['sha'] = $sha;
        }
        
        $response = wp_remote_request($url, array(
            'method' => 'PUT',
            'headers' => array(
                'Authorization' => 'token ' . self::$token,
                'Accept' => 'application/vnd.github.v3+json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'WordPress-HBC-PoD-Ledger'
            ),
            'body' => json_encode($data),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200 && $status_code !== 201) {
            $error_body = wp_remote_retrieve_body($response);
            error_log('GitHub API PUT error: ' . $error_body);
            return array(
                'success' => false,
                'error' => 'GitHub API returned status ' . $status_code
            );
        }
        
        return array(
            'success' => true
        );
    }
    
    /**
     * Grant repository access to GitHub user (stub - kept for interface compatibility)
     */
    public static function grantRepoAccess($github_username, $repo) {
        self::init();
        
        error_log(sprintf(
            'GitHubService::grantRepoAccess called for user %s to repo %s',
            $github_username,
            $repo
        ));
        
        return true;
    }
}
