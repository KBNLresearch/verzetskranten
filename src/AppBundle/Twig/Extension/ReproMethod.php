<?php

namespace AppBundle\Twig\Extension;

class ReproMethod extends \Twig_Extension
{
    private $map = [];

    /**
     * ReproMethod constructor.
     * @param string $dataDir
     */
    public function __construct($dataDir)
    {
        $dataPath  = $dataDir . DIRECTORY_SEPARATOR . 'reproductiemethoden.json';
        $this->map = (file_exists($dataPath) && is_file($dataPath) && is_readable($dataPath))
            ? json_decode(file_get_contents($dataPath), true)
            : [];
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'kb/repro_method';
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            'repro_method' => new \Twig_SimpleFilter('repro_method', [$this, 'reproMethod']),
        ];
    }

    /**
     * @param  string $text
     * @return string
     */
    public function reproMethod($text)
    {
        $key = strval($text);

        return (array_key_exists($key, $this->map))
            ? $this->map[$key]
            : $text;
    }
}
