<?php
namespace _2UpMedia\Scout\QueryHandler;

use _2UpMedia\Scout\Document;

interface QueryHandlerInterface
{
    public function setRoot($selector);
    public function setSelector($selector, $context = null);
    public function setDocument(Document $document);
    public function setContext($context);
    public function getContext();
    public function getData();
}
