<?php
/**
 * Simple interface to GitHub API
 *
 * @license MIT License
 * @author Tatsuya Tsuruoka <http://github.com/ttsuruoka>
 */

require_once __DIR__ . '/GitHubException.php';

class GitHub
{
    protected $base_url = 'https://api.github.com';
    protected $user_agent = 'PHP::GitHub/0.1';
    protected $method;
    protected $token;

    public $url;
    public $raw_body;
    public $status_code;
    public $total_time;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function call($method, $api, $params = array())
    {
        $this->method = $method;

        $this->url = $this->base_url . $api;
        if ($params) {
            $this->url .= '?' . http_build_query($params);
        }

        $headers = array(
            "Authorization: token {$this->token}",
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        switch ($this->method) {
        case 'GET':
            break;
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            break;
        case 'PATCH':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            $headers[] = 'Content-Length: 0';
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
        default:
            throw new InvalidArgumentException("unknown method: {$this->method}");
            break;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $this->raw_body = curl_exec($ch);
        $this->status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->total_time = round(curl_getinfo($ch, CURLINFO_TOTAL_TIME), 3);
        $r = json_decode($this->raw_body, true);

        if (in_array($this->status_code, array(400, 422))) {
            throw new GitHubException($r['message']);
        }

        return $r;
    }
}
