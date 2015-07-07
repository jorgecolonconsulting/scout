Scout, PHP Scraper - Data your way
==================================

[![Build Status](https://travis-ci.org/2upmedia/scout.svg?branch=master)](https://travis-ci.org/2upmedia/scout)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/2upmedia/scout/badges/quality-score.png?s=2de4fb739a50630ffcbc61b62bfda161ac38afd4)](https://scrutinizer-ci.com/g/2upmedia/scout/)
[![Code Coverage](https://scrutinizer-ci.com/g/2upmedia/scout/badges/coverage.png?s=e77261403858e1bd97b4135a622e76a0423ec248)](https://scrutinizer-ci.com/g/2upmedia/scout/)
[![Latest Stable Version](https://poser.pugx.org/2upmedia/scout/v/stable.png)](https://packagist.org/packages/2upmedia/scout)
[![Dependency Status](https://www.versioneye.com/php/2upmedia:scout/0.1/badge.png)](https://www.versioneye.com/php/2upmedia:scout/0.1)

Scout is a easy-to-use and fast scraper that uses your knowledge of PHP to transform data the way you want without having to learn another transformation language such as XSLT.

This is currently in stable beta and I encourage submitting tickets for bug, feedback, and ideas.

## Currently Supported

- Document types: HTML and XML
- Querying: XPath
- PHP 5.4+, including PHP 7!
 
## Planned for the future

- Save to a JSON, CSV, and XML file
- Support for querying with CSS selectors
- Support for querying JSON
- Ability to persist information and track atomic changes

## Possible Uses

- Track search rankings
- Spy competitors websites
- Scrape coupon websites
- Scrape websites for your own aggregation website
- Migrate data from large static websites to import into a CMS
- Get a list of jobs you're interested in from a wide range of job boards online
- Transform XML responses from your webservice into JSON
- Anything else that involves transforming XML/HTML to a data structure _you_ want.

## Consulting

For consulting, contact <a href="mailto:jorge@2upmedia.com&subject=Consulting Needed">jorge@2upmedia.com</a>

## Examples

```php
<?php

$queryHandler = new Xpath(Html::parseDocument(file_get_contents('./tests/fixtures/header-and-table.html')));

$titlesAndPrices = (new DataPoint())->setQueryHandler($queryHandler);

$data = $titlesAndPrices
    ->setCollection('//table/tr')
    ->forKey('title')->set('./td[1]') // each tr is used as a context, so the key selectors should use "." to be relative to it
    ->forKey('price')->set('./td[2]')
    ->getData();
/*
    array (
      0 => 
      array (
        'title' => 'Title #1',
        'price' => '$10.00',
      ),
      1 => 
      array (
        'title' => 'Title #2',
        'price' => '$23.20',
      ),
      2 => 
      array (
        'title' => 'Title #3',
        'price' => '$1.00',
      ),
      3 => 
      array (
        'title' => 'Title #4',
        'price' => '$5.00',
      ),
    )
*/
```

- [Get Google Rankings for the keyword "green lasers"](examples/google-search-results.php)
- [Parse Authentic Jobs feed for Ruby jobs](examples/parse-job-feed.php)

For more information on how to use the API please have a look at the [integration test](tests/integration/UseCaseTest.php).

## XPath Primer

Currently XPath is used as the query language. XPath is simple to use after a little bit of practice.

The core of XPath is the "path". If you understand file paths and URLs, you understand half of XPath already.

Read up on the syntax: http://www.w3schools.com/xpath/xpath_syntax.asp. Then have a look at the [XPath Primer example](examples/xpath-primer.php).