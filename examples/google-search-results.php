<?php
require '../vendor/autoload.php';

use _2UpMedia\Scout\QueryHandler\Xpath;
use _2UpMedia\Scout\Document\Html;
use _2UpMedia\Scout\DataPoint;
use GuzzleHttp\Client;

$url = 'https://www.google.com/search?q=green+lasers';

for ($page = 1; $page < 6; $page++) {
    $contents = (new Client())->get($url)->getBody();

    $queryHandler = new Xpath(Html::parseDocument($contents)); // feed contents to the Xpath query handler

    // a datapoint is the representation of single unit of information. For example, the header in a page, one row of
    // tabular data, and a next link
    $searchResultsDp = new DataPoint();

    // since we're getting a collection of values, we need to set the collection's selector to one that gives us a list of elements
    $searchResultsDp->setCollection("//*[@id='ires']/ol/li"); // all li's in an ol that's in any element with an id of 'ires' ANYWHERE (//) in the document

    $searchResultsDp->forKey('title')->set('.//h3/a'); // "." means from the current node which will be the individual li's, important to include if you're setting keys

    // with xpath you could cherry pick attributes too (a/@href)
    $searchResultsDp->forKey('link')->set('.//h3/a/@href', function ($value) { // we can assign a custom callable to process a specific piece of data
        $query = explode('q=', parse_url($value)['query'])[1];

        return urldecode(strstr($query, '&sa=', true)); // remove Google parameters
    });

    // we can add arbitrary data that isn't tied to a specific selector, in this case we used "." but any valid selector would work.
    $searchResultsDp->forKey('rank')->set('.', function ($value, $queryHandler, $item, $index) use ($page) {
        return ($index + 1) * $page;
    });

    $searchResultsDp->setQueryHandler($queryHandler);

    $results = $searchResultsDp->getData();

    var_dump($results);

    $nextLinkDp = new DataPoint();
    $nextLinkDp->setQueryHandler($queryHandler);
    $nextLinkDp->set('//td[@class="b"][last()]/a/@href');

    $url = 'https://www.google.com'.$nextLinkDp->getData();
}
