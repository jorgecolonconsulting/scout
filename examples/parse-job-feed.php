<?php
require '../vendor/autoload.php';

use _2UpMedia\Scout\QueryHandler\Xpath;
use _2UpMedia\Scout\Document\Xml;
use _2UpMedia\Scout\DataPoint;
use _2UpMedia\Scout\Exception\SkipException;

use GuzzleHttp\Client;

$url = 'https://authenticjobs.com/rss/custom.php?terms=&amp%3btype=3,2,6&amp%3bcats=&amp%3bonlyremote=1&amp%3blocation=';

$contents = (new Client())->get($url)->getBody();
$queryHandler = new Xpath(Xml::parseDocument($contents)); // we can parse XML documents too

$rubyJobs = new DataPoint();
$rubyJobs->setQueryHandler($queryHandler);
$rubyJobs->setRoot(".//item");
$rubyJobs('title')->set('.//title'); // calling the object as a function is shorthand for $phpJobs->forKey('title')->
$rubyJobs('description')->set('.//description', function ($value) {
    if (stripos($value, 'Ruby') === false) {
        throw new SkipException(); // skips information we don't care about
    }

    return $value;
});
$rubyJobs('link')->set('.//link');
$rubyJobs('date')->set('.//pubDate');

var_dump($rubyJobs->getData());