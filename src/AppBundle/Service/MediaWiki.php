<?php

namespace AppBundle\Service;

use AppBundle\Enum\EditResult;
use AppBundle\Enum\LoginResult;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Cookie\FileCookieJar;
use Zend\Cache\Storage\StorageInterface;

class MediaWiki
{
    const BASE_URI = 'https://nl.wikipedia.org/';

    const API_PATH = 'w/api.php';

    const USER_AGENT = 'KoninklijkeBibliotheekVerzetskranten/1.0';

    const NS_MAIN  = '';

    const NS_STUBS = 'Wikipedia:Wikiproject/Verzetskranten/Beginnetjes/';

    const DEFAULT_NS = 'stubs';

    const TALK_PREFIX = 'Overleg ';

    const TALK_PRELUDE = 'Dit artikel is geschreven in het kader van het [[Wikipedia:Wikiproject/Verzetskranten|Wikiproject Verzetskranten]]';

    /**
     * HTTP Client
     * @var HttpClient
     */
    private $httpClient;

    /**
     * Cache
     * @var StorageInterface
     */
    private $cache;

    /**
     * Path to certificates
     * @var string
     */
    private $certsPath;

    /**
     * Path to cookie jar
     * @var string
     */
    private $cookiePath;

    /**
     * Available namespaces
     * @var array
     */
    private $namespaces = [
        'main'  => self::NS_MAIN,
        'stubs' => self::NS_STUBS,
    ];

    /**
     * Preview constructor.
     * @param string $certsPath
     * @param string $cookiePath
     * @param StorageInterface $cache
     */
    public function __construct($certsPath, $cookiePath, StorageInterface $cache = null)
    {
        $this->cache = $cache;
        $this->certsPath = $certsPath;
        $this->cookiePath = $cookiePath;
    }

    /**
     * @param  string $username
     * @param  string $password
     * @return null|\array
     * @throws \Exception
     */
    public function login($username, $password)
    {
        $httpClient = $this->getHttpClient();
        $response   = $httpClient->post(self::API_PATH, [
            'query' => [
                'action'     => 'login',
                'lgname'     => $username,
                'lgpassword' => $password,
                'lgtoken'    => $this->token('login'),
                'format'     => 'json',
            ]
        ]);

        if (200 != $response->getStatusCode()) {
            throw new \Exception("Requested failed");
        }

        $body  = json_decode($response->getBody());
        $login = $body->login;

        return (LoginResult::SUCCESS == $login->result)
            ? [
                'success'  => true,
                'userid'   => $login->lguserid,
                'username' => $login->lgusername,
            ] : [
                'success' => false,
                'message' => LoginResult::messageFor($login->result),
            ];
    }

    /**
     * @return bool
     */
    public function logout()
    {
        $httpClient = $this->getHttpClient();
        $response   = $httpClient->post(self::API_PATH, [
            'query' => [
                'action' => 'logout',
            ]
        ]);

        return (200 == $response->getStatusCode());
    }

    /**
     * @param  string   $type   [optional] Token type, defaults to 'csrf'
     * @return string
     * @throws \Exception
     */
    public function token($type = 'csrf')
    {
        $httpClient = $this->getHttpClient();
        $response   = $httpClient->get(self::API_PATH, [
            'query' => [
                'action' => 'query',
                'prop'   => 'info',
                'meta'   => 'tokens',
                'format' => 'json',
                'type'   => $type,
            ]
        ]);

        if (200 != $response->getStatusCode()) {
            throw new \Exception("Requested failed");
        }

        $body   = json_decode($response->getBody());
        $tokens = $body->query->tokens;
        $tname  = sprintf('%stoken', $type);

        if (!property_exists($tokens, $tname)) {
            $msg = sprintf("Requested token type '%s' was not present in the response", $type);
            throw new \Exception($msg);
        }

        return $tokens->{$tname};
    }

    /**
     * @param  string $title
     * @return array
     * @throws \Exception
     */
    public function info($title)
    {
        $httpClient = $this->getHttpClient();
        $response   = $httpClient->get(self::API_PATH, [
            'query' => [
                'action' => 'query',
                'format' => 'json',
                'prop'   => 'info',
                'inprop' => 'talkid',
                'titles' => $title
            ]
        ]);

        if (200 != $response->getStatusCode()) {
            throw new \Exception("Requested failed");
        }

        $body = json_decode($response->getBody(), true);
        return array_shift($body['query']['pages']);
    }

    /**
     * @param  string $title
     * @param  string $wikiText
     * @param  string $namespace
     * @throws \Exception
     */
    public function edit($title, $wikiText, $namespace = self::DEFAULT_NS)
    {
        $ns = (array_key_exists($namespace, $this->namespaces))
            ? $this->namespaces[$namespace]
            : $this->namespaces[self::DEFAULT_NS];

        $pageTitle = $ns . $title;
        $talkTitle = self::TALK_PREFIX . $pageTitle;

        $httpClient = $this->getHttpClient();
        $response   = $httpClient->post(self::API_PATH, [
            'form_params' => [
                'action'       => 'edit',
                'format'       => 'json',
                'contentmodel' => 'wikitext',
                'token'        => $this->token('csrf'),
                'title'        => $pageTitle,
                'text'         => $wikiText,
            ]
        ]);

        if (200 != $response->getStatusCode()) {
            throw new \Exception("Requested failed");
        }

        $body = json_decode($response->getBody());
        if (!property_exists($body, 'edit') || EditResult::SUCCESS != $body->edit->result) {
            if (property_exists($body, 'error')) {
                $error = $body->error;
                throw new \Exception($error->info);
            }

            if (property_exists($body, 'captcha')) {
                $msg = sprintf('Edit requires <a href="%s%s">captcha</a>', self::BASE_URI, $body->captcha->url);
                throw new \Exception($msg);
            }
        }

        $info = $this->info($pageTitle);
        if (!array_key_exists('talkid', $info)) {
            $response   = $httpClient->post(self::API_PATH, [
                'form_params' => [
                    'action'       => 'edit',
                    'format'       => 'json',
                    'contentmodel' => 'wikitext',
                    'token'        => $this->token('csrf'),
                    'title'        => $talkTitle,
                    'text'         => self::TALK_PRELUDE,
                ]
            ]);

            if (200 != $response->getStatusCode()) {
                throw new \Exception("Requested failed");
            }
        }
    }

    /**
     * @param  $wikiText
     * @return string|null
     */
    public function preview($wikiText)
    {
        $data = '';
        $key  = 'preview_' . sha1($wikiText);
        
        if (!$this->cache) {
            $data = $this->getPreview($wikiText);
        } elseif ($this->cache->hasItem($key)) {
            $data = $this->cache->getItem($key);
        } else {
            $data = $this->getPreview($wikiText);
            $this->cache->setItem($key, $data);
        }
        
        return $data;
    }

    /**
     * @param  $wikiText
     * @return string|null
     */
    private function getPreview($wikiText)
    {
        $httpClient = $this->getHttpClient();
        $response   = $httpClient->post(self::API_PATH, [
            'form_params' => [
                'action'       => 'parse',
                'format'       => 'json',
                'prop'         => 'text',
                'contentmodel' => 'wikitext',
                'text'         => $wikiText,
            ]
        ]);

        $html = null;
        if (200 == $response->getStatusCode()) {
            $body = json_decode($response->getBody());
            $html = $body->parse->text->{'*'};
        }

        return $html;
    }


    /**
     * @return HttpClient
     */
    private function getHttpClient()
    {
        if (null == $this->httpClient) {
            $this->httpClient = new HttpClient([
                'base_uri' => self::BASE_URI,
                'cookies'  => new FileCookieJar($this->cookiePath, true),
                'verify'   => $this->certsPath,
                'headers'  => [
                    'User-Agent' => self::USER_AGENT,
                ],
            ]);
        }
        
        return $this->httpClient;
    }
}
