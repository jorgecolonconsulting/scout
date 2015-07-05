<?php
namespace _2UpMedia\Scout;

interface DataPointInterface extends DataRetrievalInterface
{
    public function set($xpath, callable $callable = null);
    public function __invoke($dataPointName);
}
