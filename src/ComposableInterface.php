<?php
namespace _2UpMedia\Scout;

interface ComposableInterface extends DataRetrievalInterface
{
    public function add(DataPoint $dataPoint);
}
