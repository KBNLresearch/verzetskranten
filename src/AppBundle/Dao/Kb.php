<?php

namespace AppBundle\Dao;

use EasyRdf\Sparql\Client as SparqlClient,
    Zend\Cache\Storage\StorageInterface as Cache,
    Zend\Filter\Callback as CallbackFilter,
    Zend\Filter\StringToLower as StringToLowerFilter,
    Zend\Filter\Word as WordFilter,
    Zend\Filter\FilterChain;

class Kb
{
    /**
     * SparQL client
     * @var \EasyRdf\Sparql\Client
     */
    private $sparqlClient;

    /**
     * Map of virtual properties to sparQL queries
     * @var array
     */
    private $queries = array();

    /**
     * [$cache description]
     * @var \Zend\Cache\Storage\StorageInterface
     */
    private $cache;

    /**
     * Public constructor
     *
     * @param  SparqlClient $sparqlClient A SparQL client
     * @param  String       $query_path   Path to sparQL queries
     * @param  Cache        $cache        Cache
     * @throws \Exception
     */
    public function __construct(SparqlClient $sparqlClient, $query_path, Cache $cache = null)
    {
        $this->sparqlClient = $sparqlClient;

        if (!file_exists($query_path) || !is_dir($query_path)) {
            $tpl = "Query path '%s' does not exist or is not a directory";
            $msg = sprintf($tpl, $query_path);
            throw new \Exception($msg);
        }

        $filter = new FilterChain();
        $filter->attach(new StringToLowerFilter())
            ->attach(new WordFilter\SeparatorToCamelCase())
            ->attach(new WordFilter\DashToCamelCase())
            ->attach(new WordFilter\UnderscoreToCamelCase())
            ->attach(new CallbackFilter('lcfirst'));

        $flags = \FilesystemIterator::KEY_AS_FILENAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS;
        $fsi   = new \FilesystemIterator($query_path, $flags);
        $rei   = new \RegexIterator($fsi, '/\.rq$/');

        foreach ($rei as $fileName => $fileInfo) {
            $path     = $fileInfo->getPathname();
            $baseName = $fileInfo->getBasename('.rq');
            $propName = $filter->filter($baseName);

            $this->queries[$propName] = file_get_contents($path);
        }

        if (null != $cache) {
            $this->cache = $cache;
        }
    }

    /**
     * Getter for virtual properties
     *
     * @param  String $name Name of the property
     * @return \EasyRdf\Sparql\Result
     * @throws \Exception
     */
    public function __get($name)
    {
        if (!array_key_exists($name, $this->queries)) {
            $tpl = "Property '%s' does not exist";
            $msg = sprintf($tpl, $name);
            throw new \Exception($msg);
        }

        return $this->fromCache($name);
    }

    /**
     * Check if virtual property exists
     *
     * @param  $name Name of the property
     * @return bool
     */
    function __isset($name)
    {
        return array_key_exists($name, $this->queries);
    }


    private function fromCache($key)
    {
        $data = null;
        if (null == $this->cache) {
            $data = $this->getData($key);
        } elseif (!$this->cache->hasItem($key)) {
            $data = $this->getData($key);
            $this->cache->setItem($key, serialize($data));
        } else {
            $data = unserialize($this->cache->getItem($key));
        }
        return $data;
    }

    private function getData($item)
    {
        $sparql = $this->queries[$item];
        return $this->sparqlClient->query($sparql);
    }
}
