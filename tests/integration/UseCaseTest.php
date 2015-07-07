<?php
use _2UpMedia\Scout\DataPoint,
	_2UpMedia\Scout\QueryHandler\Xpath,
	_2UpMedia\Scout\QueryHandler\QueryHandlerInterface,
	_2UpMedia\Scout\Document\Html,
	_2UpMedia\Scout\Document\Xml,
	_2UpMedia\Scout\Exception\SkipException,
	_2UpMedia\Scout\Exception\StopException,
	_2UpMedia\Scout\Exception\CancelException;

class UseCaseTest extends PHPUnit_Framework_TestCase {
	/**
	 * @var QueryHandlerInterface
	 */
	protected $queryHandler;

	public function setUp()
	{
		$this->queryHandler = new Xpath(
			Html::parseDocument(file_get_contents('fixtures/testing-ground.scraping.pro.html'))
		);
	}
	
	public function testOneSimpleValue()
	{
		$queryHandler = $this->queryHandler;

		$headerDp = new DataPoint();
		$headerDp->set('id("title")/text()');
		$headerDp->setQueryHandler($queryHandler);
		$header = $headerDp->getData();

		$this->assertEquals('WEB SCRAPER TESTING GROUND', $header);
	}

	public function testCollectionOfSimpleValues()
	{
		$queryHandler = $this->queryHandler;

		$computerNamesDp = new DataPoint();
		$computerNamesDp->set('.//*[@id="case1"]//div[@class="name"]/text()', null, true);
		$computerNamesDp->setQueryHandler($queryHandler);
		$computerNames = $computerNamesDp->getData();

		$expect = array (
			0 => 'Dell Latitude D610-1.73 Laptop Wireless Computer',
			1 => 'Samsung Chromebook (Wi-Fi, 11.6-Inch)',
			2 => 'Apple MacBook Pro MD101LL/A 13.3-Inch Laptop (NEWEST VERSION)',
			3 => 'Acer Aspire AS5750Z-4835 15.6-Inch Laptop (Black)',
			4 => 'HP Pavilion g7-2010nr 17.3-Inch Laptop (Black)',
			5 => 'ASUS A53Z-AS61 15.6-Inch Laptop (Mocha)',
		);

		$this->assertEquals($expect, $computerNames);
	}

	public function testCollectionOfCompositeValues()
	{
		$queryHandler = $this->queryHandler;

		$computersDetailsDp = new DataPoint();
		$computersDetailsDp->setQueryHandler($queryHandler);
		$computersDetailsDp->setCollection("id('case1')/div[@class='prod1' or @class='prod2']");
		$computersDetailsDp('name')->set('.//div[@class="name"]/text()');
		$computersDetailsDp('price')->set('.//span[2]/text()');

		$computerDetails = $computersDetailsDp->getData();

		$expect = array (
			0 =>
				array (
					'name' => 'Dell Latitude D610-1.73 Laptop Wireless Computer',
					'price' => '$239.95',
				),
			1 =>
				array (
					'name' => 'Samsung Chromebook (Wi-Fi, 11.6-Inch)',
					'price' => '$249.00',
				),
			2 =>
				array (
					'name' => 'Apple MacBook Pro MD101LL/A 13.3-Inch Laptop (NEWEST VERSION)',
					'price' => '$1,099.99',
				),
			3 =>
				array (
					'name' => 'Acer Aspire AS5750Z-4835 15.6-Inch Laptop (Black)',
					'price' => '$385.72',
				),
			4 =>
				array (
					'name' => 'HP Pavilion g7-2010nr 17.3-Inch Laptop (Black)',
					'price' => '$549.99',
				),
			5 =>
				array (
					'name' => 'ASUS A53Z-AS61 15.6-Inch Laptop (Mocha)',
					'price' => '$399.99',
				),
		);

		$this->assertEquals($expect, $computerDetails);
	}

    public function testCollectionOfCompositeValuesWithForKey()
	{
		$queryHandler = $this->queryHandler;

		$computersDetailsDp = new DataPoint();
		$computersDetailsDp->setQueryHandler($queryHandler);
		$computersDetailsDp->setCollection("id('case1')/div[@class='prod1' or @class='prod2']");
		$computersDetailsDp->forKey('name')->set('.//div[@class="name"]/text()');
		$computersDetailsDp->forKey('price')->set('.//span[2]/text()');

		$computerDetails = $computersDetailsDp->getData();

		$expect = array (
			0 =>
				array (
					'name' => 'Dell Latitude D610-1.73 Laptop Wireless Computer',
					'price' => '$239.95',
				),
			1 =>
				array (
					'name' => 'Samsung Chromebook (Wi-Fi, 11.6-Inch)',
					'price' => '$249.00',
				),
			2 =>
				array (
					'name' => 'Apple MacBook Pro MD101LL/A 13.3-Inch Laptop (NEWEST VERSION)',
					'price' => '$1,099.99',
				),
			3 =>
				array (
					'name' => 'Acer Aspire AS5750Z-4835 15.6-Inch Laptop (Black)',
					'price' => '$385.72',
				),
			4 =>
				array (
					'name' => 'HP Pavilion g7-2010nr 17.3-Inch Laptop (Black)',
					'price' => '$549.99',
				),
			5 =>
				array (
					'name' => 'ASUS A53Z-AS61 15.6-Inch Laptop (Mocha)',
					'price' => '$399.99',
				),
		);

		$this->assertEquals($expect, $computerDetails);
	}

	public function testCollectionOfDataPoints()
	{
		$queryHandler = $this->queryHandler;

		$rootDp = new DataPoint();
		$rootDp->setQueryHandler($queryHandler);

		$headerDp = new DataPoint('header');
		$headerDp->set('id("title")/text()');

		$computersDetailsDp = new DataPoint('computers');
		$computersDetailsDp->setCollection("id('case1')/div[@class='prod1' or @class='prod2']");
		$computersDetailsDp('name')->set('.//div[@class="name"]/text()');
		$computersDetailsDp('price')->set('.//span[2]/text()');

		$discount = new DataPoint('discount');
		$discount->set('.//span[2]/div');

		$computersDetailsDp->add($discount);


		$rootDp->add($headerDp);
		$rootDp->add($computersDetailsDp);

		$result = $rootDp->getData();

		$expected = array (
			'header' => 'WEB SCRAPER TESTING GROUND',
			'computers' =>
				array (
					0 =>
						array (
							'discount' => NULL,
							'name' => 'Dell Latitude D610-1.73 Laptop Wireless Computer',
							'price' => '$239.95',
						),
					1 =>
						array (
							'discount' => NULL,
							'name' => 'Samsung Chromebook (Wi-Fi, 11.6-Inch)',
							'price' => '$249.00',
						),
					2 =>
						array (
							'discount' => NULL,
							'name' => 'Apple MacBook Pro MD101LL/A 13.3-Inch Laptop (NEWEST VERSION)',
							'price' => '$1,099.99',
						),
					3 =>
						array (
							'discount' => NULL,
							'name' => 'Acer Aspire AS5750Z-4835 15.6-Inch Laptop (Black)',
							'price' => '$385.72',
						),
					4 =>
						array (
							'discount' => 'discount 7%',
							'name' => 'HP Pavilion g7-2010nr 17.3-Inch Laptop (Black)',
							'price' => '$549.99',
						),
					5 =>
						array (
							'discount' => NULL,
							'name' => 'ASUS A53Z-AS61 15.6-Inch Laptop (Mocha)',
							'price' => '$399.99',
						),
				),
		);

		$this->assertEquals($expected, $result);
	}

	public function testCollectionOfSimpleDataPoints()
	{
		$queryHandler = $this->queryHandler;

		$rootDp = new DataPoint();
		$rootDp->setQueryHandler($queryHandler);

		$caseHeaderDp = new DataPoint();
		$caseHeaderDp->setRoot('id("case1")');

		$firstComputerDp = new DataPoint();
		$firstComputerDp->set('.//div[1]/span[1]/div');

		$secondComputerDp = new DataPoint();
		$secondComputerDp->set('.//div[2]/span[1]/div');

		$caseHeaderDp->add($firstComputerDp);
		$caseHeaderDp->add($secondComputerDp);

		$rootDp->add($caseHeaderDp);

		$return = $rootDp->getData();

		$expect = array (
			0 =>
				array (
					0 => 'Dell Latitude D610-1.73 Laptop Wireless Computer',
					1 => 'Samsung Chromebook (Wi-Fi, 11.6-Inch)',
				),
		);

		$this->assertEquals($expect, $return);
	}

	public function testSimpleValueWithCallable()
	{
		$self = $this;

		$queryHandler = $this->queryHandler;

		$headerDp = new DataPoint();
		$headerDp->set('id("title")/text()', function ($value, $queryHandler, $item) use ($self) {
			$self->assertInstanceOf('_2UpMedia\Scout\QueryHandler\QueryHandlerInterface', $queryHandler);
			$self->assertInstanceOf('DOMNode', $item);

			return $value.'1';
		});
		$headerDp->setQueryHandler($queryHandler);
		$header = $headerDp->getData();

		$this->assertEquals('WEB SCRAPER TESTING GROUND1', $header);
	}

	public function testCollectionOfSimpleValuesWithCallable()
	{
		$self = $this;

		$queryHandler = $this->queryHandler;

		$computerNamesDp = new DataPoint();

		$computerNamesDp->set(
			'.//*[@id="case1"]//div[@class="name"]/text()',
			function ($value, $queryHandler, $item, $index) use ($self) {
				$self->assertInstanceOf('_2UpMedia\Scout\QueryHandler\QueryHandlerInterface', $queryHandler);
				$self->assertInstanceOf('DOMNode', $item);
				$self->assertTrue(is_int($index));

				return $value.' 1';
			},
			true
		);

		$computerNamesDp->setQueryHandler($queryHandler);
		$computerNames = $computerNamesDp->getData();

		$expect = array (
			0 => 'Dell Latitude D610-1.73 Laptop Wireless Computer 1',
			1 => 'Samsung Chromebook (Wi-Fi, 11.6-Inch) 1',
			2 => 'Apple MacBook Pro MD101LL/A 13.3-Inch Laptop (NEWEST VERSION) 1',
			3 => 'Acer Aspire AS5750Z-4835 15.6-Inch Laptop (Black) 1',
			4 => 'HP Pavilion g7-2010nr 17.3-Inch Laptop (Black) 1',
			5 => 'ASUS A53Z-AS61 15.6-Inch Laptop (Mocha) 1',
		);

		$this->assertEquals($expect, $computerNames);
	}

	public function testCollectionOfCompositeValuesWithCallable()
	{
		$self = $this;

		$queryHandler = $this->queryHandler;

		$computersDetailsDp = new DataPoint();
		$computersDetailsDp->setCollection("id('case1')/div[@class='prod1' or @class='prod2']");
		$computersDetailsDp('name')->set(
			'.//div[@class="name"]/text()',
			function ($value, $queryHandler, $item, $index) use ($self) {
				$self->assertInstanceOf('_2UpMedia\Scout\QueryHandler\QueryHandlerInterface', $queryHandler);
				$self->assertInstanceOf('DOMNode', $item);
				$self->assertTrue(is_int($index));

				return $value.' 1';
			}
		);

		$computersDetailsDp('price')->set(
			'.//span[2]/text()',
			function ($value, $queryHandler, $item) use ($self) {
				$self->assertInstanceOf('_2UpMedia\Scout\QueryHandler\QueryHandlerInterface', $queryHandler);
				$self->assertInstanceOf('DOMNode', $item);

				return (float) number_format(preg_replace("/[^0-9\.]/", "", $value), 2, '.', '');
			}
		)
		;
		$computersDetailsDp->setQueryHandler($queryHandler);

		$computerDetails = $computersDetailsDp->getData();

		$expect = array (
			0 =>
				array (
					'name' => 'Dell Latitude D610-1.73 Laptop Wireless Computer 1',
					'price' => 239.95,
				),
			1 =>
				array (
					'name' => 'Samsung Chromebook (Wi-Fi, 11.6-Inch) 1',
					'price' => 249,
				),
			2 =>
				array (
					'name' => 'Apple MacBook Pro MD101LL/A 13.3-Inch Laptop (NEWEST VERSION) 1',
					'price' => 1099.99,
				),
			3 =>
				array (
					'name' => 'Acer Aspire AS5750Z-4835 15.6-Inch Laptop (Black) 1',
					'price' => 385.72,
				),
			4 =>
				array (
					'name' => 'HP Pavilion g7-2010nr 17.3-Inch Laptop (Black) 1',
					'price' => 549.99,
				),
			5 =>
				array (
					'name' => 'ASUS A53Z-AS61 15.6-Inch Laptop (Mocha) 1',
					'price' => 399.99,
				),
		);

		$this->assertEquals($expect, $computerDetails);
	}

	public function testSimpleValueWithCancelExceptionInCallable()
	{
		$self = $this;

		$queryHandler = $this->queryHandler;

		$headerDp = new DataPoint();
		$headerDp->set('id("title")/text()', function ($value) use ($self) {
			if (stripos($value, 'scraper') !== false) {
				throw new CancelException();
			}

			return $value.'1';
		});
		$headerDp->setQueryHandler($queryHandler);
		$header = $headerDp->getData();

		$this->assertNull($header);
	}

	public function testSimpleValueWithSkipExceptionInCallable()
	{
		$self = $this;

		$queryHandler = $this->queryHandler;

		$headerDp = new DataPoint();
		$headerDp->set('id("title")/text()', function ($value) use ($self) {
			if (stripos($value, 'scraper') !== false) {
				throw new SkipException();
			}

			return $value.'1';
		});
		$headerDp->setQueryHandler($queryHandler);
		$header = $headerDp->getData();

		$this->assertNull($header);
	}

	public function testSimpleValueWithStopExceptionInCallable()
	{
		$self = $this;

		$queryHandler = $this->queryHandler;

		$headerDp = new DataPoint();
		$headerDp->set('id("title")/text()', function ($value) use ($self) {
			if (stripos($value, 'scraper') !== false) {
				throw new StopException();
			}

			return $value.'1';
		});
		$headerDp->setQueryHandler($queryHandler);
		$header = $headerDp->getData();

		$this->assertEquals('WEB SCRAPER TESTING GROUND', $header);
	}

	public function testCollectionOfSimpleValuesWithCancelExceptionInCallable()
	{
		$queryHandler = $this->queryHandler;

		$computerNamesDp = new DataPoint();
		$computerNamesDp->set(
			'//*[@id="case1"]//div[@class="name"]/text()',
			function ($computerTitle) {
				if (strpos($computerTitle, 'ASUS') !== false) {
					throw new CancelException();
				}

				return $computerTitle;
			},
			true);
		$computerNamesDp->setQueryHandler($queryHandler);
		$computerNames = $computerNamesDp->getData();

		$this->assertEmpty($computerNames);
	}

	public function testCollectionOfSimpleValuesWithSkipExceptionInCallable()
	{
		$queryHandler = $this->queryHandler;

		$computerNamesDp = new DataPoint();
		$computerNamesDp->set(
			'//*[@id="case1"]//div[@class="name"]/text()',
			function ($computerTitle) {
				if (strpos($computerTitle, '15.') === false) { // filter out non-15 inch laptops
					throw new SkipException();
				}

				return $computerTitle;
			},
			true);
		$computerNamesDp->setQueryHandler($queryHandler);
		$computerNames = $computerNamesDp->getData();

		$expect = array (
			'Acer Aspire AS5750Z-4835 15.6-Inch Laptop (Black)',
			'ASUS A53Z-AS61 15.6-Inch Laptop (Mocha)',
		);

		$this->assertEquals($expect, $computerNames);
	}

	public function testCollectionOfSimpleValuesWithStopExceptionInCallable()
	{
		$queryHandler = $this->queryHandler;

		$computerNamesDp = new DataPoint();
		$computerNamesDp->set(
			'//*[@id="case1"]//div[@class="name"]/text()',
			function ($computerTitle) {
				if (strpos($computerTitle, 'Apple') !== false) {
					throw new StopException();
				}

				return $computerTitle;
			},
			true);
		$computerNamesDp->setQueryHandler($queryHandler);
		$computerNames = $computerNamesDp->getData();

		$expect = array (
			'Dell Latitude D610-1.73 Laptop Wireless Computer',
			'Samsung Chromebook (Wi-Fi, 11.6-Inch)',
			'Apple MacBook Pro MD101LL/A 13.3-Inch Laptop (NEWEST VERSION)'
		);

		$this->assertEquals($expect, $computerNames);
	}

	public function testCollectionOfCompositeValuesWithCancelExceptionInCallable()
	{
		$self = $this;

		$queryHandler = $this->queryHandler;

		$computersDetailsDp = new DataPoint();
		$computersDetailsDp->setCollection("id('case1')/div[@class='prod1' or @class='prod2']");

		$computersDetailsDp('name')->set(
			'.//div[@class="name"]/text()',
			function ($value) {
				if (strpos($value, 'Apple') !== false) {
					throw new CancelException();
				}

				return $value;
			}
		);
		$computersDetailsDp('price')->set(
			'.//span[2]/text()',
			function ($value) use ($self) {
				$value = (float) number_format(preg_replace("/[^0-9\.]/", "", $value), 2, '.', '');

				return $value;
			}
		);

		$computersDetailsDp->setQueryHandler($queryHandler);
		$computerDetails = $computersDetailsDp->getData();

		$this->assertEmpty($computerDetails);
	}

	public function testCollectionOfCompositeValuesWithSkipExceptionInCallable()
	{
		$self = $this;

		$queryHandler = $this->queryHandler;

		$computersDetailsDp = new DataPoint();
		$computersDetailsDp->setCollection("id('case1')/div[@class='prod1' or @class='prod2']");

		$computersDetailsDp('name')->set('.//div[@class="name"]/text()');
		$computersDetailsDp('price')->set(
			'.//span[2]/text()',
			function ($value) use ($self) {
				$value = (float) number_format(preg_replace("/[^0-9\.]/", "", $value), 2, '.', '');

				if ($value > 399) {
					throw new SkipException();
				}

				return $value;
			}
		);

		$computersDetailsDp->setQueryHandler($queryHandler);
		$computerDetails = $computersDetailsDp->getData();

		$expect = array (
			0 =>
				array (
					'name' => 'Dell Latitude D610-1.73 Laptop Wireless Computer',
					'price' => 239.95,
				),
			1 =>
				array (
					'name' => 'Samsung Chromebook (Wi-Fi, 11.6-Inch)',
					'price' => 249,
				),
			3 =>
				array (
					'name' => 'Acer Aspire AS5750Z-4835 15.6-Inch Laptop (Black)',
					'price' => 385.72,
				),
		);

		$this->assertEquals($expect, $computerDetails);
	}

	public function testCollectionOfCompositeValuesWithStopExceptionInCallable()
	{
		$self = $this;

		$queryHandler = $this->queryHandler;

		$computersDetailsDp = new DataPoint();
		$computersDetailsDp->setCollection("id('case1')/div[@class='prod1' or @class='prod2']");

		$computersDetailsDp('name')->set(
			'.//div[@class="name"]/text()',
			function ($value) {
				if (strpos($value, 'Apple') !== false) {
					throw new StopException();
				}

				return $value;
			}
		);
		$computersDetailsDp('price')->set(
			'.//span[2]/text()',
			function ($value) use ($self) {
				$value = (float) number_format(preg_replace("/[^0-9\.]/", "", $value), 2, '.', '');

				return $value;
			}
		);

		$computersDetailsDp->setQueryHandler($queryHandler);
		$computerDetails = $computersDetailsDp->getData();

		$expect = array (
			0 =>
				array (
					'name' => 'Dell Latitude D610-1.73 Laptop Wireless Computer',
					'price' => 239.95,
				),
			1 =>
				array (
					'name' => 'Samsung Chromebook (Wi-Fi, 11.6-Inch)',
					'price' => 249,
				),
			2 =>
				array (
					'name' => 'Apple MacBook Pro MD101LL/A 13.3-Inch Laptop (NEWEST VERSION)',
					'price' => 1099.99,
				),
		);

		$this->assertEquals($expect, $computerDetails);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testNotSettingQueryHandlerThrowsException()
	{
		$dp = new DataPoint();
		$dp->getData();
	}

	/**
	 * @expectedException \Exception
	 */
	public function testBadXpathThrowsException()
	{
		$this->queryHandler->setSelector('asdioihw(&$&(');
		$this->queryHandler->getData();
	}

	/**
	 * @expectedException \Exception
	 */
	public function testBadXMLThrowsException()
	{
		$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<CATALOG>
	<CD>
		<TITLE>Empire Burlesque</TITLE>
		<ARTIST>Bob Dylan</ARTIST>
		<COUNTRY>USA</COUNTRY>
		<COMPANY>Columbia</COMPANY>
		<PRICE>10.90</PRICE>
		<YEAR>1985</YEAR>
	</CD>
XML;


		$queryHandler = new Xpath(Xml::parseDocument($xml));
	}
}
