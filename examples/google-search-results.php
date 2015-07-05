<?php
require '../vendor/autoload.php';

use _2UpMedia\Scout\QueryHandler\Xpath;
use _2UpMedia\Scout\Document\Html;
use _2UpMedia\Scout\DataPoint;
use GuzzleHttp\Client;

$url = 'https://www.google.com/search?q=green+lasers';

for ($page = 1; $page < 6; $page++) {
    $contents = (new Client())->get($url)->getBody();

    $xpathDocument = new Xpath(Html::parseDocument($contents)); // feed contents to the Xpath query handler

    $searchResultsDp = new DataPoint();
    $searchResultsDp->setRoot(".//*[@id='ires']/ol/li");
    $searchResultsDp('title')->set('.//h3/a');
    $searchResultsDp('link')->set('.//h3/a/@href', function($value){
        $query = explode('q=', parse_url($value)['query'])[1];

        return urldecode(strstr($query, '&sa=', true)); // remove Google parameters
    });
    $searchResultsDp('rank')->set('.', function ($value, $queryHandler, $item, $index) use ($page) {
        return ($index + 1) * $page;
    });
    $searchResultsDp->setQueryHandler($xpathDocument);

    $results = $searchResultsDp->getData();

    var_dump($results);

    $nextLinkDp = new DataPoint();
    $nextLinkDp->setQueryHandler($xpathDocument);
    $nextLinkDp->set('//td[@class="b"][last()]/a/@href');

    $url = 'https://www.google.com'.$nextLinkDp->getData();
}
