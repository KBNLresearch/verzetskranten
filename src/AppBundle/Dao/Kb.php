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
     * Check if virtual property exists
     *
     * @param  $name Name of the property
     * @return bool
     */
    public function __isset($name)
    {
        return $this->has($name);
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
        return $this->get($name);
    }

    /**
     * @param  string $name
     * @param  array $arguments
     * @return \EasyRdf\Graph|\EasyRdf\Sparql\Result|mixed|null
     */
    public function __call($name, array $arguments)
    {
        return $this->get($name, $arguments);
    }

    /**
     * Check if virtual property exists
     *
     * @param  $item
     * @return bool
     */
    public function has($item)
    {
        return array_key_exists($item, $this->queries);
    }

    /**
     * Getter for virtual properties
     *
     * @param  string $item
     * @param  array  $arguments
     * @return \EasyRdf\Graph|\EasyRdf\Sparql\Result|mixed|null
     * @throws \Exception
     */
    public function get($item, array $arguments = [])
    {
        if (!$this->has($item)) {
            $tpl = "Property '%s' does not exist";
            $msg = sprintf($tpl, $item);
            throw new \Exception($msg);
        }

        $data = null;
        $key  = sprintf('item_%s__args_%s', $item, implode('-', $arguments));

        if (null == $this->cache) {
            $data = $this->query($key, $arguments);
        } elseif (!$this->cache->hasItem($key)) {
            $data = $this->query($item, $arguments);
            $this->cache->setItem($key, serialize($data));
        } else {
            $data = unserialize($this->cache->getItem($key));
        }

        return $data;
    }

    /**
     * Perform query
     *
     * @param  string $item
     * @param  array $arguments
     * @return \EasyRdf\Graph|\EasyRdf\Sparql\Result
     */
    private function query($item, array $arguments = [])
    {
        $sparql = preg_replace_callback('/%(\\d+)%/', function (array $matches) use ($arguments) {
            $position = intval($matches[1], 10) - 1;
            $value    = $arguments[$position];

            return is_numeric($value) ? $value : "'" . urlencode(addslashes($value)) . "'";
        }, $this->queries[$item]);

        return $this->sparqlClient->query($sparql);
    }
}
