<?php
namespace _2UpMedia\Scout;

interface CollectionInterface extends DataPointInterface, ComposableInterface
{
    public function setRoot($xpath);
}
