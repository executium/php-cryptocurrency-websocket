<?php


class InitWs
{
    private $debugLevel = 45;
    public $port;

    public $cookieHeader = '';

    public $side = "bids";
    public $symbol = "btcusdt";
    public $exchange = "binance";
    public $orderbook_level = 1;
    public $useragent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.105 Safari/537.36';

    public function debug($message, $level = 1)
    {
        if ($level > $this->debugLevel) {
            return;
        }
        if (is_array($message)) {
            print_r($message);

            return;
        }
        echo $message . "\n";
    }

    private function requestSession($host, $sid = '', $updateCookie = false)
    {
        if ($updateCookie) {
            $this->cookieHeader = null;
        }
        [$header, $body] = $this->request($this->createUrl($host, $sid));
        $pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m";
        preg_match_all($pattern, $header, $matches);
        $cookie = "Cookie: " . implode("; ", $matches['cookie']);
        if (!$this->cookieHeader) {
            $this->cookieHeader = $cookie;
            $this->debug($cookie);
        }

        return $body;
    }

    public function createUrl($host, $sid = '', $options = [])
    {
        $type = $options['type'] ?? 'https';
        if ($type === 'wss') {
            $transport = 'websocket';
        } else {
            $transport = 'polling';
        }
        $url = $type . "://" . $host . ":" . $this->port . "/socket.io/?EIO=3&transport=" . $transport;
        if ($sid) {
            $url .= '&sid=' . $sid;
        }

        return $url;
    }

    private function request($url, $method = 'GET', $options = [])
    {
        $this->debug($method . ' request to ' . $url);
        $postData = $options['data'] ?? '';
        $curlOptions = [
            CURLOPT_URL            => $url,
            CURLOPT_HEADER         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_USERAGENT,
            $this->useragent,
            CURLOPT_HTTPHEADER     => [
                $this->cookieHeader,
            ],
        ];
        if ($postData) {
            $curlOptions[CURLOPT_POSTFIELDS] = $postData;
        }
        $curl = curl_init();
        curl_setopt_array($curl, $curlOptions);
        $response = curl_exec($curl);
        curl_close($curl);

        return explode("\r\n\r\n", $response, 2);
    }

    private function requestPayload($host, $sid, $options)
    {
        $offset = $options['offset'];
        $addr = $options['address'];
        $data = $options['payload'];

        $payload = ['message', $data];
        $payloadStr = $this->encode(json_encode($payload), $offset, $addr);
        [$header, $body] = $this->request($this->createUrl($host, $sid), "POST", ['data' => $payloadStr]);

        return $body;
    }

    public function encode($data, $offset, $address)
    {
        $this->debug('trying encode: ' . $data);
        $data = ($address + $offset) . $data;
        $length = strlen($data);

        return $length . ':' . $data;
    }

    public function decode($data)
    {
        $this->debug('trying decode: ' . $data);
        if (preg_match("|(\d+):(\d+)(.*)|", $data, $match)) {
            $length = $match[1] - 1;
            $str = substr($match[3], 0, $length);
            $offset = 0;
            $address = 0;
            if (preg_match("|(\d+):(\d+)|", substr($match[3], $length), $match)) {
                $offset = $match[1];
                $address = $match[2];
            }

            return [$str, $offset, $address];
        }

        return false;
    }

    public function prepareConnect($host)
    {
        $sid = $this->requestSession($host);

        [$sidStr, $offset, $address] = $this->decode($sid);
        try {
            $decodedSid = json_decode($sidStr, true);
        } catch (Exception $ex) {
            $decodedSid = false;
        }
        $output = "{$this->side}-{$this->exchange}-{$this->symbol}";
        $payload = [
            'req' => $this->exchange,
            's'   => $this->symbol,
            'o'   => $output,
        ];
        if ($decodedSid) {
            $result = $this->requestPayload($host, $decodedSid['sid'], compact('offset', 'address', 'payload'));
            if ($result == 'ok') {
                return $decodedSid['sid'];
            }
        }

        return false;
    }

    public function getSubscribeServer($host, $sid)
    {
        $sid = $this->requestSession($host, $sid);
        [$sidStr] = $this->decode($sid);
        try {
            return json_decode($sidStr, true);
        } catch (Exception $ex) {
        }

        return [];
    }

    public function prepareSubscribe($host)
    {
        $sid = $this->requestSession($host, '');
        [$sidStr, $offset, $address] = $this->decode($sid);
        try {
            $decodedSid = json_decode($sidStr, true);
        } catch (Exception $ex) {
            $decodedSid = false;
        }
        $payload = ["subscribe" => "{$this->side}/{$this->exchange}-{$this->symbol}-{$this->orderbook_level}"];
        if ($decodedSid) {
            $result = $this->requestPayload($host, $decodedSid['sid'], compact('offset', 'address', 'payload'));
            if ($result == 'ok') {
                return $decodedSid['sid'];
            }

            return $decodedSid['sid'];
        }

        return false;
    }

    public function __construct($port,$side,$symbol,$exchange,$orderbook_level)
    {
        $this->port = $port;
        $this->side = $side;
        $this->symbol = $symbol;
        $this->exchange = $exchange;
        $this->orderbook_level = $orderbook_level;
    }
}
