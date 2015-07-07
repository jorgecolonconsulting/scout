<?php
require '../vendor/autoload.php';

use _2UpMedia\Scout\QueryHandler\Xpath,
    _2UpMedia\Scout\Document\Html,
    _2UpMedia\Scout\DataPoint;

$queryHandler = new Xpath(Html::parseDocument(file_get_contents('../tests/fixtures/header-and-table.html')));

$dataPoint = (new DataPoint())->setQueryHandler($queryHandler);

$html = clone $dataPoint;
$header = clone $dataPoint;
$table = clone $dataPoint;
$trRows = clone $dataPoint;
$oddRows = clone $dataPoint;
$tdCells = clone $dataPoint;

$titlesWithPrice = clone $dataPoint;

var_dump('$html', $html->set('/html')->getData()); // gets the text for the html tag
var_dump('$header', $header->set('/html/body/header')->getData()); // gets the text for the header tag
var_dump('$header', $header->set('//header')->getData()); // gets the text for the header tag, but it's more concise. The "//" means "anywhere"
var_dump('$table', $table->set('//table[@class="data"]')->getData()); // gets the text for the table tag. Predicates "[]" are conditions that include or exclude nodes.
var_dump('$table', $table->set('//table[1]')->getData());
var_dump('$table', $table->set('//table[last()]')->getData()); // gets the same table since there's only one
var_dump('$trRows', $trRows->set('//table/tr', null, true)->getData()); // gets tr rows
var_dump('$oddRows', $oddRows->set('//table/tr[@class="odd"]', null, true)->getData()); // gets tr rows with a class of "odd"
var_dump('$oddRows', $oddRows->set('//table/tr[not(@class="even") and @class]', null, true)->getData()); // gets tr rows, that don't have a class of "even" and has a class
var_dump('$tdCells', $tdCells->set('//body//td', null, true)->getData()); // gets all tds that are descents of body anywhere

$data = $titlesWithPrice
    ->setCollection('//table/tr')
    ->forKey('title')->set('./td[1]') // each tr is used as a context, so the key selectors should use "." to be relative to it
    ->forKey('price')->set('./td[2]')
    ->getData();

var_dump('$titlesWithPrice', $data);
