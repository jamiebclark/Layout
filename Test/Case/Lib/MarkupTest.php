<?php
App::uses('Markup', 'Layout.Lib');

class MarkupTest extends CakeTestCase {
	public function setUp() {

	}

	public function testSetStyle() {
		$text = 
'== Get Excited! ==
The following link will [/register Register your totals]!
- Unordered Item 1
- Unordered Item 2';
		$text = Markup::setStyle($text);
		$this->assertContains('<h2>', $text);
		$this->assertContains('<li>', $text);
	}
}