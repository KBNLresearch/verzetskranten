<?php

namespace AppBundle\Service;

use AppBundle\Enum\LoginResult;
use GuzzleHttp\Client as HttpClient;
use Zend\Cache\Storage\StorageInterface;

class MediaWiki
{
    const BASE_URI = 'https://nl.wikipedia.org/';
    
    const API_PATH = 'w/api.php';
    
    const USER_AGENT = 'KoninklijkeBibliotheekVerzetskranten/1.0';

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
     * Preview constructor.
     * @param StorageInterface $cache
     */
    public function __construct(StorageInterface $cache = null)
    {
        $this->cache = $cache;
    }

    /**
     * @param  string $username
     * @param  string $password
     * @param  string $token
     * @return \StdClass|null
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
            return false;
        }

        $body  = json_decode($response->getBody());
        $login = $body->login;

        switch ($login->result) {
            case LoginResult::NEED_TOKEN:
                return $this->login($username, $password, $login->token);
                break;

            case LoginResult::SUCCESS:
                return $login;
                break;
        }
        
        return null;
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

        return  (200 == $response->getStatusCode());
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
            $data = $this->getData($wikiText);
        } elseif ($this->cache->hasItem($key)) {
            $data = $this->cache->getItem($key);
        } else {
            $data = $this->getData($wikiText);
            $this->cache->setItem($key, $data);
        }
        
        return $data;
    }

    /**
     * @param  $wikiText
     * @return string|null
     */
    private function getData($wikiText)
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
                'headers'  => [
                    'User-Agent' => self::USER_AGENT,
                ]
            ]);
        }
        
        return $this->httpClient;
    }
}
