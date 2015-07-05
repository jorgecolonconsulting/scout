<?php
namespace _2UpMedia\Scout;

abstract class Document
{
    protected $rawData;

    public static function parseDocument($rawData)
    {
        // make document from raw data
        $class = get_called_class();

        return new $class($rawData);
    }

    public function __construct($rawData)
    {
        $this->setRawData($rawData);
    }

    public function getRawData()
    {
        return $this->rawData;
    }

    public function setRawData($rawData)
    {
        $this->rawData = $rawData;
    }
}
