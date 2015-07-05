<?php
namespace _2UpMedia\Scout;

use _2UpMedia\Scout\Exception\StopException;
use _2UpMedia\Scout\Exception\CancelException;
use _2UpMedia\Scout\Exception\SkipException;
use _2UpMedia\Scout\QueryHandler\QueryHandlerInterface;

class DataPoint implements DataPointInterface, ComposableInterface
{
    /**
     * @var array
     */
    protected $compositeData = [];

    /**
     * @var array
     */
    protected $simpleData = [];

    /**
     * @var string
     */
    protected $propertyName;

    /**
     * @var string
     */
    protected $rootSelectorPath;

    /**
     * @var string
     */
    protected $groupName;

    /**
     * @var DataPoint
     */
    protected $parentDataPoint;

    /**
     * @var DataPoint[]
     */
    protected $dataPoints = array();

    /**
     * @var QueryHandlerInterface
     */
    protected $queryHandler;

    /**
     * @param null $groupName
     */
    public function __construct($groupName = null)
    {
        if ($groupName) {
            $this->setGroupName($groupName);
        }
    }

    /**
     * @param DataPoint $dataPoint
     */
    public function setParentDataPoint(DataPoint $dataPoint)
    {
        $this->parentDataPoint = $dataPoint;
    }

    /**
     * @return DataPoint
     */
    public function getParentDataPoint()
    {
        return $this->parentDataPoint;
    }

    /**
     * @param $queryHandler
     */
    public function setQueryHandler(QueryHandlerInterface $queryHandler)
    {
        $this->queryHandler = $queryHandler;
    }

    /**
     * @return QueryHandlerInterface
     * @throws \Exception
     */
    public function getQueryHandler()
    {
        if (!($this->queryHandler) && $this->getParentDataPoint()) {
            $parentDataPoint = $this->getParentDataPoint();
            $queryHandler = $parentDataPoint->getQueryHandler();

            if ($queryHandler) {
                $this->setQueryHandler($queryHandler);

                return $queryHandler;
            }
        } elseif (!$this->queryHandler && !$this->getParentDataPoint()) {
            throw new \Exception('setQueryHandler needs to be called');
        }

        return $this->queryHandler;
    }

    /**
     * @param string $name
     */
    public function setGroupName($name)
    {
        $this->groupName = $name;
    }

    /**
     * @return null|string
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

    /**
     * @param string $selector
     */
    public function setRoot($selector)
    {
        $this->rootSelectorPath = $selector;
    }

    public function configureRoot()
    {
        if ($this->rootSelectorPath) {
            $queryHandler = $this->getQueryHandler();

            $queryHandler->setRoot($this->rootSelectorPath);
        }
    }

    /**
     * @param DataPoint $dataPoint
     */
    public function add(DataPoint $dataPoint)
    {
        $dataPoint->setParentDataPoint($this);

        $this->dataPoints[] = $dataPoint;
    }

    /**
     * @param $xpath
     * @param callable $callable
     * @param bool $representsCollection
     */
    public function set($xpath, callable $callable = null, $representsCollection = false)
    {
        if (!isset($this->propertyName)) {
            $this->simpleData = [
                'xpath' => $xpath,
                'callable' => $callable,
                'representsCollection' => $representsCollection
            ];
        } else {
            $this->compositeData[$this->propertyName] = [
                'xpath' => $xpath,
                'callable' => $callable,
            ];
        }
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    public function getData()
    {
        $return = null;

        $queryHandler = $this->getQueryHandler();

        $this->configureRoot();

        foreach ($this->dataPoints as $dataPoint) {
            $dataPoint->configureRoot();
        }

        if (!empty($this->simpleData)) {
            $queryHandler->setSelector($this->simpleData['xpath'], $queryHandler->getContext());

            $nodeList = $queryHandler->getData();

            if ($nodeList->length) {
                if ($this->simpleData['representsCollection']) {
                    $buffer = [];

                    foreach ($nodeList as $index => $node) {
                        $value = $node->nodeValue;

                        if ($this->simpleData['callable']) {
                            try {
                                $newValue = $this->runCallable(
                                    $this->simpleData['callable'],
                                    $node,
                                    $queryHandler,
                                    $node,
                                    $index
                                );
                            } catch (SkipException $e) {
                                continue;
                            } catch (StopException $e) {
                                $buffer[] = $value;

                                break;
                            } catch (CancelException $e) {
                                $buffer = [];

                                break;
                            }

                            $value = $newValue;
                        }

                        $buffer[] = $value;
                    }

                    return $buffer;
                }

                $item = $nodeList->item(0);
                $return = $item->nodeValue;

                if ($this->simpleData['callable']) {
                    $callableReturn = null;

                    try {
                        $callableReturn = $this->runCallable(
                            $this->simpleData['callable'],
                            $item,
                            $queryHandler,
                            $item,
                            null
                        );
                    } catch (CancelException $e) {
                        return null;
                    } catch (StopException $e) {
                        $callableReturn = $return;
                    } catch (SkipException $e) {
                        (string)$e; // placeholder so that code coverage runs this line
                    }

                    return $callableReturn;
                }
            }
        } elseif (!empty($this->compositeData)) {
            $nodes = $queryHandler->getData();

            $return = [];

            $stopIteration = false;

            foreach ($nodes as $index => $node) {
                $lastKey = $this->lastArrayKey($this->compositeData);

                foreach ($this->compositeData as $name => $meta) {
                    $queryHandler->setSelector($meta['xpath'], $node);
                    $nodeList = $queryHandler->getData();
                    $item = $nodeList->item(0); // TODO: abstract this so there isn't a hard dependency to \DOMNodeList
                    $buffer = $nodeList->length ? $item->nodeValue : null;

                    if (!isset($return[$index])) {
                        $return[$index] = [];
                    }

                    $childReturn = $this->runChildDataPoints($return[$index]);

                    if (!empty($childReturn)) {
                        $return[$index] = array_merge($return[$index], $childReturn);
                    }

                    if ($meta['callable']) {
                        try {
                            $buffer = $this->runCallable($meta['callable'], $item, $queryHandler, $item, $index);
                        } catch (SkipException $e) {
                            unset($return[$index]);

                            continue 2;
                        } catch (StopException $e) {
                            $stopIteration = true;
                        } catch (CancelException $e) {
                            $return = [];
                            break 2;
                        }
                    }

                    $return[$index][$name] = $buffer;

                    if ($stopIteration && $name === $lastKey) {
                        $stopIteration = false;

                        break 2;
                    }
                }
            }
        } else {
            $return = $this->runChildDataPoints($return);
        }

        return $return;
    }

    /**
     * @param $dataPointName
     * @return $this
     */
    public function __invoke($dataPointName)
    {
        $this->propertyName = $dataPointName;

        return $this;
    }

    /**
     * @param callable $callable
     * @param $dataPointElement
     * @param QueryHandlerInterface $queryHandler
     * @param $item
     * @param $index
     * @return bool
     */
    public function runCallable(
        callable $callable,
        $dataPointElement,
        QueryHandlerInterface $queryHandler,
        $item,
        $index)
    {
        if ($index === null) {
            $returnValue = $callable($dataPointElement->nodeValue,
                $queryHandler, $item);
        } else {
            $returnValue = $callable($dataPointElement->nodeValue,
                $queryHandler, $item, $index);
        }

        return $returnValue;
    }

    /**
     * @param $return
     * @return array
     * @throws \Exception
     */
    private function runChildDataPoints($return)
    {
        if (!empty($this->dataPoints)) {
            $buffer = [];

            foreach ($this->dataPoints as $dataPoint) {
                if ($groupName = $dataPoint->getGroupName()) {
                    $buffer[$groupName] = $dataPoint->getData();
                } else {
                    $buffer[] = $dataPoint->getData();
                }
            }

            if (!is_array($return) && empty($return)) {
                $return = [];
            }

            $return = array_merge($buffer, $return);

            return $return;
        }

        return $return;
    }

    private function lastArrayKey(array $array)
    {
        end($array);

        return key($array);
    }
}
