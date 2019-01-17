<?php

namespace Classes;

class LogParser
{
    const REMOTE_HOST_PATTERN = '([^ ]+)';
    const LOG_NAME_PATTERN = '([^ ]+)';
    const USER_PATTERN = '([^ ]+)';
    const DATE_TIME_PATTERN = '(\[[^\]]+\])';
    const METHOD_PATTERN = '(.*)';
    const REQUEST_PATTERN = '(.*)';
    const PROTOCOL_PATTERN = '(.*)';
    const STATUS_PATTERN = '([0-9\-]+)';
    const BYTES_PATTERN = '([0-9\-]+)';
    const REFERER_PATTERN = '(.*)';
    const USER_AGENT_PATTERN = '(.*)';

    const SEPARATOR_PATTERN = ' ';
    const QUOTE_PATTERN = '"';
    const START_LINE_PATTERN = '/^';
    const END_LINE_PATTERN = '$/';

    const PATTERN =
        self::START_LINE_PATTERN .
        self::REMOTE_HOST_PATTERN .
        self::SEPARATOR_PATTERN .
        self::LOG_NAME_PATTERN .
        self::SEPARATOR_PATTERN .
        self::USER_PATTERN .
        self::SEPARATOR_PATTERN .
        self::DATE_TIME_PATTERN .
        self::SEPARATOR_PATTERN .
        self::QUOTE_PATTERN .
        self::METHOD_PATTERN .
        self::SEPARATOR_PATTERN .
        self::REQUEST_PATTERN .
        self::SEPARATOR_PATTERN .
        self::PROTOCOL_PATTERN .
        self::QUOTE_PATTERN .
        self::SEPARATOR_PATTERN .
        self::STATUS_PATTERN .
        self::SEPARATOR_PATTERN .
        self::BYTES_PATTERN .
        self::SEPARATOR_PATTERN .
        self::QUOTE_PATTERN .
        self::REFERER_PATTERN .
        self::QUOTE_PATTERN .
        self::SEPARATOR_PATTERN .
        self::QUOTE_PATTERN .
        self::USER_AGENT_PATTERN .
        self::QUOTE_PATTERN .
        self::END_LINE_PATTERN;

    protected $path;

    protected $line;
    protected $remoteHost;
    protected $logName;
    protected $user;
    protected $dateTime;
    protected $method;
    protected $request;
    protected $protocol;
    protected $status;
    protected $bytes;
    protected $referer;
    protected $userAgent;

    protected $errors = 0;

    protected $views = 0;
    protected $traffic = 0;
    protected $urls = [];
    protected $crawlers = [];
    protected $statusCodes = [];

    protected $crawlerNames = [
        'Google' => [
            'Googlebot',
            'AdsBot-Google',
        ],
        'Bing' => [
            'Bingbot',
        ],
        'Baidu' => [
            'Baiduspider',
        ],
        'Yandex' => [
            'YandexBot',
        ],
    ];

    protected $output = [];

    public function setPath($path)
    {
        $this->path = $path;
    }

    protected function parseLine($line)
    {
        preg_match_all(self::PATTERN, $line, $matches);

        $matches = array_map(
            function ($a) {
                return $a[0];
            },
            $matches
        );

        list(
            $this->line,
            $this->remoteHost,
            $this->logName,
            $this->user,
            $this->dateTime,
            $this->method,
            $this->request,
            $this->protocol,
            $this->status,
            $this->bytes,
            $this->referer,
            $this->userAgent
        ) = $matches;
    }

    protected function lineHandler()
    {
        if (!$this->line) {
            $this->errors++;
        }

        $this->views++;
        $this->traffic += (int)$this->bytes;

        if ($this->request) {
            if (array_key_exists($this->request, $this->urls)) {
                $this->urls[$this->request]++;
            } else {
                $this->urls[$this->request] = 1;
            }
        }

        foreach ($this->crawlerNames as $crawler => $names) {
            foreach ($names as $name) {
                if (strpos($this->userAgent, $name) !== false) {
                    $this->crawlers[$crawler]++;
                }
            }
            if (!$this->crawlers[$crawler]) {
                $this->crawlers[$crawler] = 0;
            }
        }

        if ($this->status) {
            $this->statusCodes[$this->status]++;
        }
    }

    protected function finalHandler()
    {
        arsort($this->urls);

        $this->output = [
            'views' => $this->views,
            'urls' => count($this->urls),
            'topUrls' => $this->urls,
            'traffic' => $this->traffic,
            'crawlers' => $this->crawlers,
            'statusCodes' => $this->statusCodes,
            'errors' => $this->errors
        ];
    }

    protected function getResult()
    {
        $handle = fopen($this->path, "r");
        while (!feof($handle)) {
            $this->parseLine(fgets($handle));
            $this->lineHandler();
        }
        fclose($handle);

        $this->finalHandler();

        return $this->output;
    }

    public function getJson()
    {
        return json_encode($this->getResult());
    }
}
