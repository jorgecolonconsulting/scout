<?php
/**
 * Created by PhpStorm.
 * User: x2UP_Media
 * Date: 7/2/15
 * Time: 5:41 PM
 */

namespace _2UpMedia\Scout\QueryHandler;

use _2UpMedia\Scout\Document;

abstract class Base
{
    /**
     * @var Document
     */
    protected $document;

    public function __construct($document)
    {
        $this->setDocument($document);
    }

    public function setDocument(Document $document)
    {
        $this->document = $document;
    }
}
