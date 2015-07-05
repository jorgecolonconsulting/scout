<?php
require '../vendor/autoload.php';

use _2UpMedia\Scout\QueryHandler\Xpath;
use _2UpMedia\Scout\Document\Xml;
use _2UpMedia\Scout\DataPoint;
use _2UpMedia\Scout\Exception\SkipException;

use GuzzleHttp\Client;

$url = 'https://authenticjobs.com/rss/custom.php?terms=&amp%3btype=3,2,6&amp%3bcats=&amp%3bonlyremote=1&amp%3blocation=';

$contents = (new Client())->get($url)->getBody();
$xpathDocument = new Xpath(Xml::parseDocument($contents));

$phpJobs = new DataPoint();
$phpJobs->setQueryHandler($xpathDocument);
$phpJobs->setRoot(".//item");
$phpJobs('title')->set('.//title');
$phpJobs('description')->set('.//description', function ($value) {
    if (stripos($value, 'Ruby') === false) {
        throw new SkipException();
    }

    return $value;
});
$phpJobs('link')->set('.//link');
$phpJobs('date')->set('.//pubDate');

var_dump($phpJobs->getData());