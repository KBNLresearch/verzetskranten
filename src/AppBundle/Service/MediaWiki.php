<?php

namespace AppBundle\Service;

use AppBundle\Enum\EditResult;
use AppBundle\Enum\LoginResult;
use GuzzleHttp\Client as HttpClient;
use Zend\Cache\Storage\StorageInterface;

class MediaWiki
{
    const BASE_URI = 'https://nl.wikipedia.org/';
    
    const API_PATH = 'w/api.php';
    
    const USER_AGENT = 'KoninklijkeBibliotheekVerzetskranten/1.0';
    
    const WIKI_NAMESPACE = 'Wikipedia:Wikiproject/Verzetskranten/Beginnetjes/';

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
     * Preview constructor.
     * @param string           $certsPath
     * @param StorageInterface $cache
     */
    public function __construct($certsPath, StorageInterface $cache = null)
    {
        $this->cache = $cache;
        $this->certsPath = $certsPath;
    }

    /**
     * @param  string $username
     * @param  string $password
     * @param  string $token
     * @return null|\StdClass
     * @throws \Exception
     */
    public function login($username, $password, $token = null)
    {
        $httpClient = $this->getHttpClient();
        $response   = $httpClient->post(self::API_PATH, [
            'query' => [
                'action'     => 'login',
                'lgname'     => $username,
                'lgpassword' => $password,
                'lgtoken'    => $token,
                'format'     => 'json',
            ]
        ]);

        if (200 != $response->getStatusCode()) {
            throw new \Exception("Requested failed");
        }

        $body  = json_decode($response->getBody());
        $login = $body->login;

        switch ($login->result) {
            case LoginResult::NEED_TOKEN:
                return $this->login($username, $password, $login->token);
                break;

            case LoginResult::SUCCESS:
                return [
                    'success'  => true,
                    'userid'   => $login->lguserid,
                    'username' => $login->lgusername,
                ];
                break;

            default:
                return [
                    'success' => false,
                    'message' => LoginResult::messageFor($login->result),
                ];
                break;
        }
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
     * @param  $type
     * @return mixed
     * @throws \Exception
     */
    public function token($type)
    {
        $httpClient = $this->getHttpClient();
        $response   = $httpClient->get(self::API_PATH, [
            'query' => [
                'action' => 'query',
                'prop'   => 'info',
                'meta'   => 'tokens',
                'format' => 'json',
            ]
        ]);

        if (200 != $response->getStatusCode()) {
            throw new \Exception("Requested failed");
        }

        $body   = json_decode($response->getBody());
        $tokens = $body->query->tokens;

        if (!property_exists($tokens, $type)) {
            $msg = sprintf("Requested token type '%s' was not present in the response", $type);
            throw new \Exception($msg);
        }

        return $tokens->{$type};
    }

    /**
     * @param  $token
     * @param  $title
     * @param  $wikiText
     * @throws \Exception
     */
    public function edit($token, $title, $wikiText)
    {
        $httpClient = $this->getHttpClient();
        $response   = $httpClient->post(self::API_PATH, [
            'form_params' => [
                'action'       => 'edit',
                'format'       => 'json',
                'prop'         => 'text',
                'contentmodel' => 'wikitext',
                'token'        => $token,
                'title'        => self::WIKI_NAMESPACE . $title,
                'text'         => $wikiText,
            ]
        ]);

        if (200 != $response->getStatusCode()) {
            throw new \Exception("Requested failed");
        }

        $body = json_decode($response->getBody());
        if (EditResult::SUCCESS != $body->edit->result) {
            $error = $body->error;
            throw new \Exception($error->info, $error->code);
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
                'text'         => $wikiText
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
                'cookies'  => true,
                'verify'   => $this->certsPath,
                'headers'  => [
                    'User-Agent' => self::USER_AGENT,
                ]
            ]);
        }
        
        return $this->httpClient;
    }
}
