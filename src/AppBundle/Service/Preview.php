<?php

namespace AppBundle\Service;

use GuzzleHttp\Client as HttpClient;
use Zend\Cache\Storage\StorageInterface;

class Preview
{
    const BASE_URI = 'http://nl.wikipedia.org/';
    
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
                'headers'  => [
                    'User-Agent' => self::USER_AGENT,
                ]
            ]);
        }
        
        return $this->httpClient;
    }
}
