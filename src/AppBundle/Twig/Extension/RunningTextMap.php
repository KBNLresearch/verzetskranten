<?php

namespace AppBundle\Twig\Extension;

class RunningTextMap extends \Twig_Extension
{
    private $mapRepro = [];

    private $mapContent = [];

    private $mapFreq = [];

    /**
     * RunningTextMap constructor.
     * @param string $dataDir
     */
    public function __construct($dataDir)
    {
        $reproPath = $dataDir . DIRECTORY_SEPARATOR . 'reproductiemethoden.json';
        $this->mapRepro = (file_exists($reproPath) && is_file($reproPath) && is_readable($reproPath))
            ? json_decode(file_get_contents($reproPath), true)
            : [];

        $contentPath = $dataDir . DIRECTORY_SEPARATOR . 'inhoudsoorten.json';
        $this->mapContent = (file_exists($contentPath) && is_file($contentPath) && is_readable($contentPath))
            ? json_decode(file_get_contents($contentPath), true)
            : [];

        $freqPath = $dataDir . DIRECTORY_SEPARATOR . 'verschijningsfrequenties.json';
        $this->mapFreq = (file_exists($freqPath) && is_file($freqPath) && is_readable($freqPath))
            ? json_decode(file_get_contents($freqPath), true)
            : [];
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'kb/running_text_map';
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            'repro_method' => new \Twig_SimpleFilter('repro_method', [$this, 'reproMethod']),
            'main_content' => new \Twig_SimpleFilter('main_content', [$this, 'mainContent']),
            'frequency'    => new \Twig_SimpleFilter('frequency', [$this, 'frequency']),
        ];
    }

    /**
     * @param  string $text
     * @return string
     */
    public function reproMethod($text)
    {
        $key = strval($text);

        return (array_key_exists($key, $this->mapRepro))
            ? $this->mapRepro[$key]
            : $text;
    }

    /**
     * @param  string $text
     * @return string
     */
    public function mainContent($text)
    {
        $key = strval($text);

        return (array_key_exists($key, $this->mapContent))
            ? $this->mapContent[$key]
            : $text;
    }

    /**
     * @param  string $text
     * @return string
     */
    public function frequency($text)
    {
        $key = strval($text);

        return (array_key_exists($key, $this->mapFreq))
            ? $this->mapFreq[$key]
            : $text;
    }
}
