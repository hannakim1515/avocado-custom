<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * Pusher PHP Library - Simplified Standalone Version
 * Original: https://github.com/pusher/pusher-http-php
 * 
 * 이 파일은 Pusher의 핵심 기능만 포함한 경량 버전입니다.
 * Composer 없이 k_battle 플러그인에서 직접 사용 가능합니다.
 */

class Pusher {
    private $app_id;
    private $key;
    private $secret;
    private $cluster;
    private $use_tls;
    private $host;
    
    /**
     * Pusher 클라이언트 생성
     * 
     * @param string $key Pusher App Key
     * @param string $secret Pusher App Secret
     * @param string $app_id Pusher App ID
     * @param array $options 옵션 배열 (cluster, useTLS)
     */
    public function __construct($key, $secret, $app_id, $options = array()) {
        $this->key = $key;
        $this->secret = $secret;
        $this->app_id = $app_id;
        $this->cluster = ses($options, 'cluster', 'mt1');
        $this->use_tls = ses($options, 'useTLS', true);
        $this->host = "api-{$this->cluster}.pusher.com";
    }
    
    /**
     * 이벤트 트리거 (채널에 메시지 전송)
     * 
     * @param string|array $channels 채널명 (문자열 또는 배열)
     * @param string $event 이벤트명
     * @param mixed $data 전송 데이터 (배열/객체는 자동 JSON 인코딩)
     * @param array $params 추가 매개변수 (socket_id 등)
     * @return bool|array 성공 시 응답 배열, 실패 시 false
     */
    public function trigger($channels, $event, $data, $params = array()) {
        if (is_string($channels)) {
            $channels = array($channels);
        }
        
        $payload = array(
            'name' => $event,
            'channels' => $channels,
            'data' => is_string($data) ? $data : json_encode($data)
        );
        
        if (isset($params['socket_id'])) {
            $payload['socket_id'] = $params['socket_id'];
        }
        
        return $this->post('/events', $payload);
    }
    
    /**
     * HTTP POST 요청 실행
     * 
     * @param string $path API 경로
     * @param array $payload 요청 데이터
     * @return bool|array 성공 시 응답 배열, 실패 시 false
     */
    private function post($path, $payload) {
        $json_payload = json_encode($payload);
        $query_params = array(
            'auth_key' => $this->key,
            'auth_timestamp' => time(),
            'auth_version' => '1.0',
            'body_md5' => md5($json_payload)
        );
        
        $query_string = http_build_query($query_params);
        $auth_signature = $this->sign_request('POST', $path, $query_string, $json_payload);
        $query_params['auth_signature'] = $auth_signature;
        
        $url = ($this->use_tls ? 'https://' : 'http://') 
             . $this->host 
             . '/apps/' . $this->app_id 
             . $path 
             . '?' . http_build_query($query_params);
        
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json_payload)
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json_payload,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => $this->use_tls
        ));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            if (function_exists('error_log')) {
                error_log("Pusher cURL Error: {$error}");
            }
            return false;
        }
        
        if ($http_code >= 200 && $http_code < 300) {
            return json_decode($response, true);
        }
        
        if (function_exists('error_log')) {
            error_log("Pusher HTTP Error {$http_code}: {$response}");
        }
        return false;
    }
    
    /**
     * 요청 서명 생성 (HMAC-SHA256)
     * 
     * @param string $method HTTP 메서드 (POST)
     * @param string $path API 경로
     * @param string $query_string 쿼리 문자열
     * @param string $body 요청 본문
     * @return string HMAC-SHA256 서명
     */
    private function sign_request($method, $path, $query_string, $body = '') {
        $string_to_sign = implode("\n", array(
            $method,
            '/apps/' . $this->app_id . $path,
            $query_string
        ));
        
        return hash_hmac('sha256', $string_to_sign, $this->secret);
    }
}

/**
 * Pusher 이벤트 브로드캐스트 헬퍼 함수
 * 
 * @param string $channel 채널명 (예: 'raid-123')
 * @param string $event 이벤트명 (예: 'action-update')
 * @param array $data 전송 데이터
 * @return bool 성공 여부
 */
function broadcast_pusher_event($channel, $event, $data) {
    global $kb_cf;
    
    // Pusher 설정 확인
    if(empty($kb_cf['pusher_app_id']) || empty($kb_cf['pusher_key']) || empty($kb_cf['pusher_secret'])) {
        return false;
    }
    
    try {
        $pusher = new Pusher(
            $kb_cf['pusher_key'],
            $kb_cf['pusher_secret'],
            $kb_cf['pusher_app_id'],
            array(
                'cluster' => ses($kb_cf, 'pusher_cluster', 'ap3'),
                'useTLS' => true
            )
        );
        
        $result = $pusher->trigger($channel, $event, $data);
        return $result !== false;
        
    } catch (Exception $e) {
        if (function_exists('error_log')) {
            error_log('Pusher Error: ' . $e->getMessage());
        }
        return false;
    }
}