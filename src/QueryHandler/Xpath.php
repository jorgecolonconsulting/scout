<?php
namespace _2UpMedia\Scout\QueryHandler;

use _2UpMedia\Scout\Document\Html;
use _2UpMedia\Scout\Document\Xml;

class Xpath extends Base implements QueryHandlerInterface
{
    /**
     * @var \DOMDocument
     */
    protected $domDocument;

    /**
     * @var string valid xpath
     */
    protected $selector;

    /**
     * @var \DOMXPath
     */
    protected $domXpath;

    /**
     * @var string valid xpath to the root element
     */
    protected $rootPath;

    /**
     * @var \DOMNode context node
     */
    protected $context;

    /**
     * @param $document
     * @throws \Exception
     */
    public function __construct($document)
    {
        parent::__construct($document);

        $this->parseDocument();
    }

    /**
     * @throws \Exception
     */
    protected function parseDocument()
    {
        $this->domDocument = new \DOMDocument;

        libxml_use_internal_errors(true);

        $loadedSource = false;

        if ($this->document instanceof Xml) {
            $loadedSource = $this->domDocument->loadXML($this->document->getRawData());
        } elseif ($this->document instanceof Html) {
            $loadedSource = $this->domDocument->loadHTML($this->document->getRawData());
        }

        if (! $loadedSource) {
            $errors = "";
            foreach (libxml_get_errors() as $error) {
                $errors .= $error->message."\n";
            }
            libxml_clear_errors();

            throw new \Exception("libxml errors:\n$errors");
        }

        $this->domXpath = new \DOMXPath($this->domDocument);
    }

    /**
     * @param $selectorPath
     * @return self
     */
    public function setRoot($selectorPath)
    {
        $this->rootPath = $this->selector = $selectorPath;

        return $this;
    }

    /**
     * @param string $selector xpath selector
     * @param \DOMNode $context (optional), defaults to null
     * @return self
     */
    public function setSelector($selector, $context = null)
    {
        $this->selector = $selector;

        $this->setContext($context);

        return $this;
    }

    /**
     * @param null|\DOMNode $context
     * @return self
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @return null|\DOMNode
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return false|int|string|float|\DOMNodeList
     */
    public function getData()
    {
        if ($this->context) {
            $return = $this->domXpath->evaluate($this->selector, $this->context);
        } else {
            $return = $this->domXpath->evaluate($this->selector);
        }

        if ($return === false) {
            throw new \DomainException("xpath selector incorrect: {$this->selector}");
        }

        return $return;
    }
}
