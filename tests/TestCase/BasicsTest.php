<?php
/**
 * BasicsTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase;

use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Network\Response;
use Cake\TestSuite\TestCase;
use Cake\Utility\Folder;

require_once CAKE . 'basics.php';

/**
 * BasicsTest class
 */
class BasicsTest extends TestCase {

/**
 * test the array_diff_key compatibility function.
 *
 * @return void
 */
	public function testArrayDiffKey() {
		$one = array('one' => 1, 'two' => 2, 'three' => 3);
		$two = array('one' => 'one', 'two' => 'two');
		$result = array_diff_key($one, $two);
		$expected = array('three' => 3);
		$this->assertEquals($expected, $result);

		$one = array('one' => array('value', 'value-two'), 'two' => 2, 'three' => 3);
		$two = array('two' => 'two');
		$result = array_diff_key($one, $two);
		$expected = array('one' => array('value', 'value-two'), 'three' => 3);
		$this->assertEquals($expected, $result);

		$one = array('one' => null, 'two' => 2, 'three' => '', 'four' => 0);
		$two = array('two' => 'two');
		$result = array_diff_key($one, $two);
		$expected = array('one' => null, 'three' => '', 'four' => 0);
		$this->assertEquals($expected, $result);

		$one = array('minYear' => null, 'maxYear' => null, 'separator' => '-', 'interval' => 1, 'monthNames' => true);
		$two = array('minYear' => null, 'maxYear' => null, 'separator' => '-', 'interval' => 1, 'monthNames' => true);
		$result = array_diff_key($one, $two);
		$this->assertSame(array(), $result);
	}

/**
 * testHttpBase method
 *
 * @return void
 */
	public function testEnv() {
		$this->skipIf(!function_exists('ini_get') || ini_get('safe_mode') === '1', 'Safe mode is on.');

		$server = $_SERVER;
		$env = $_ENV;

		$_SERVER['HTTP_HOST'] = 'localhost';
		$this->assertEquals(env('HTTP_BASE'), '.localhost');

		$_SERVER['HTTP_HOST'] = 'com.ar';
		$this->assertEquals(env('HTTP_BASE'), '.com.ar');

		$_SERVER['HTTP_HOST'] = 'example.ar';
		$this->assertEquals(env('HTTP_BASE'), '.example.ar');

		$_SERVER['HTTP_HOST'] = 'example.com';
		$this->assertEquals(env('HTTP_BASE'), '.example.com');

		$_SERVER['HTTP_HOST'] = 'www.example.com';
		$this->assertEquals(env('HTTP_BASE'), '.example.com');

		$_SERVER['HTTP_HOST'] = 'subdomain.example.com';
		$this->assertEquals(env('HTTP_BASE'), '.example.com');

		$_SERVER['HTTP_HOST'] = 'example.com.ar';
		$this->assertEquals(env('HTTP_BASE'), '.example.com.ar');

		$_SERVER['HTTP_HOST'] = 'www.example.com.ar';
		$this->assertEquals(env('HTTP_BASE'), '.example.com.ar');

		$_SERVER['HTTP_HOST'] = 'subdomain.example.com.ar';
		$this->assertEquals(env('HTTP_BASE'), '.example.com.ar');

		$_SERVER['HTTP_HOST'] = 'double.subdomain.example.com';
		$this->assertEquals(env('HTTP_BASE'), '.subdomain.example.com');

		$_SERVER['HTTP_HOST'] = 'double.subdomain.example.com.ar';
		$this->assertEquals(env('HTTP_BASE'), '.subdomain.example.com.ar');

		$_SERVER = $_ENV = array();

		$_SERVER['SCRIPT_NAME'] = '/a/test/test.php';
		$this->assertEquals(env('SCRIPT_NAME'), '/a/test/test.php');

		$_SERVER = $_ENV = array();

		$_ENV['CGI_MODE'] = 'BINARY';
		$_ENV['SCRIPT_URL'] = '/a/test/test.php';
		$this->assertEquals(env('SCRIPT_NAME'), '/a/test/test.php');

		$_SERVER = $_ENV = array();

		$this->assertFalse(env('HTTPS'));

		$_SERVER['HTTPS'] = 'on';
		$this->assertTrue(env('HTTPS'));

		$_SERVER['HTTPS'] = '1';
		$this->assertTrue(env('HTTPS'));

		$_SERVER['HTTPS'] = 'I am not empty';
		$this->assertTrue(env('HTTPS'));

		$_SERVER['HTTPS'] = 1;
		$this->assertTrue(env('HTTPS'));

		$_SERVER['HTTPS'] = 'off';
		$this->assertFalse(env('HTTPS'));

		$_SERVER['HTTPS'] = false;
		$this->assertFalse(env('HTTPS'));

		$_SERVER['HTTPS'] = '';
		$this->assertFalse(env('HTTPS'));

		$_SERVER = array();

		$_ENV['SCRIPT_URI'] = 'https://domain.test/a/test.php';
		$this->assertTrue(env('HTTPS'));

		$_ENV['SCRIPT_URI'] = 'http://domain.test/a/test.php';
		$this->assertFalse(env('HTTPS'));

		$_SERVER = $_ENV = array();

		$this->assertNull(env('TEST_ME'));

		$_ENV['TEST_ME'] = 'a';
		$this->assertEquals(env('TEST_ME'), 'a');

		$_SERVER['TEST_ME'] = 'b';
		$this->assertEquals(env('TEST_ME'), 'b');

		unset($_ENV['TEST_ME']);
		$this->assertEquals(env('TEST_ME'), 'b');

		$_SERVER = $server;
		$_ENV = $env;
	}

/**
 * Test h()
 *
 * @return void
 */
	public function testH() {
		$string = '<foo>';
		$result = h($string);
		$this->assertEquals('&lt;foo&gt;', $result);

		$in = array('this & that', '<p>Which one</p>');
		$result = h($in);
		$expected = array('this &amp; that', '&lt;p&gt;Which one&lt;/p&gt;');
		$this->assertEquals($expected, $result);

		$string = '<foo> & &nbsp;';
		$result = h($string);
		$this->assertEquals('&lt;foo&gt; &amp; &amp;nbsp;', $result);

		$string = '<foo> & &nbsp;';
		$result = h($string, false);
		$this->assertEquals('&lt;foo&gt; &amp; &nbsp;', $result);

		$string = '<foo> & &nbsp;';
		$result = h($string, 'UTF-8');
		$this->assertEquals('&lt;foo&gt; &amp; &amp;nbsp;', $result);

		$string = "An invalid\x80string";
		$result = h($string);
		$this->assertContains('string', $result);

		$arr = array('<foo>', '&nbsp;');
		$result = h($arr);
		$expected = array(
			'&lt;foo&gt;',
			'&amp;nbsp;'
		);
		$this->assertEquals($expected, $result);

		$arr = array('<foo>', '&nbsp;');
		$result = h($arr, false);
		$expected = array(
			'&lt;foo&gt;',
			'&nbsp;'
		);
		$this->assertEquals($expected, $result);

		$arr = array('f' => '<foo>', 'n' => '&nbsp;');
		$result = h($arr, false);
		$expected = array(
			'f' => '&lt;foo&gt;',
			'n' => '&nbsp;'
		);
		$this->assertEquals($expected, $result);

		$arr = array('invalid' => "\x99An invalid\x80string", 'good' => 'Good string');
		$result = h($arr);
		$this->assertContains('An invalid', $result['invalid']);
		$this->assertEquals('Good string', $result['good']);

		// Test that boolean values are not converted to strings
		$result = h(false);
		$this->assertFalse($result);

		$arr = array('foo' => false, 'bar' => true);
		$result = h($arr);
		$this->assertFalse($result['foo']);
		$this->assertTrue($result['bar']);

		$obj = new \stdClass();
		$result = h($obj);
		$this->assertEquals('(object)stdClass', $result);

		$obj = new Response(array('body' => 'Body content'));
		$result = h($obj);
		$this->assertEquals('Body content', $result);
	}

/**
 * test __()
 *
 * @return void
 */
	public function testTranslate() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __('Plural Rule 1');
		$expected = 'Plural Rule 1 (translated)';
		$this->assertEquals($expected, $result);

		$result = __('Plural Rule 1 (from core)');
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEquals($expected, $result);

		$result = __('Some string with %s', 'arguments');
		$expected = 'Some string with arguments';
		$this->assertEquals($expected, $result);

		$result = __('Some string with %s %s', 'multiple', 'arguments');
		$expected = 'Some string with multiple arguments';
		$this->assertEquals($expected, $result);

		$result = __('Some string with %s %s', array('multiple', 'arguments'));
		$expected = 'Some string with multiple arguments';
		$this->assertEquals($expected, $result);

		$result = __('Testing %2$s %1$s', 'order', 'different');
		$expected = 'Testing different order';
		$this->assertEquals($expected, $result);

		$result = __('Testing %2$s %1$s', array('order', 'different'));
		$expected = 'Testing different order';
		$this->assertEquals($expected, $result);

		$result = __('Testing %.2f number', 1.2345);
		$expected = 'Testing 1.23 number';
		$this->assertEquals($expected, $result);
	}

/**
 * testTranslatePercent
 *
 * @return void
 */
	public function testTranslatePercent() {
		$result = __('%s are 100% real fruit', 'Apples');
		$expected = 'Apples are 100% real fruit';
		$this->assertEquals($expected, $result, 'Percent sign at end of word should be considered literal');

		$result = __('%s are %d% real fruit', 'Apples', 100);
		$expected = 'Apples are 100% real fruit';
		$this->assertEquals($expected, $result, 'A digit marker should not be misinterpreted');

		$result = __('%s are %s% real fruit', 'Apples', 100);
		$expected = 'Apples are 100% real fruit';
		$this->assertEquals($expected, $result, 'A string marker should not be misinterpreted');

		$result = __('%nonsense %s', 'Apples');
		$expected = '%nonsense Apples';
		$this->assertEquals($expected, $result, 'A percent sign at the start of the string should be considered literal');

		$result = __('%s are awesome%', 'Apples');
		$expected = 'Apples are awesome%';
		$this->assertEquals($expected, $result, 'A percent sign at the end of the string should be considered literal');

		$result = __('%2$d %1$s entered the bowl', 'Apples', 2);
		$expected = '2 Apples entered the bowl';
		$this->assertEquals($expected, $result, 'Positional replacement markers should not be misinterpreted');

		$result = __('%.2f% of all %s agree', 99.44444, 'Cats');
		$expected = '99.44% of all Cats agree';
		$this->assertEquals($expected, $result, 'significant-digit placeholder should not be misinterpreted');
	}

/**
 * testTranslateWithFormatSpecifiers
 *
 * @return void
 */
	public function testTranslateWithFormatSpecifiers() {
		$expected = 'Check,   one, two, three';
		$result = __('Check, %+10s, three', 'one, two');
		$this->assertEquals($expected, $result);

		$expected = 'Check,    +1, two, three';
		$result = __('Check, %+5d, two, three', 1);
		$this->assertEquals($expected, $result);

		$expected = 'Check, @@one, two, three';
		$result = __('Check, %\'@+10s, three', 'one, two');
		$this->assertEquals($expected, $result);

		$expected = 'Check, one, two  , three';
		$result = __('Check, %-10s, three', 'one, two');
		$this->assertEquals($expected, $result);

		$expected = 'Check, one, two##, three';
		$result = __('Check, %\'#-10s, three', 'one, two');
		$this->assertEquals($expected, $result);

		$expected = 'Check,   one, two, three';
		$result = __d('default', 'Check, %+10s, three', 'one, two');
		$this->assertEquals($expected, $result);

		$expected = 'Check, @@one, two, three';
		$result = __d('default', 'Check, %\'@+10s, three', 'one, two');
		$this->assertEquals($expected, $result);

		$expected = 'Check, one, two  , three';
		$result = __d('default', 'Check, %-10s, three', 'one, two');
		$this->assertEquals($expected, $result);

		$expected = 'Check, one, two##, three';
		$result = __d('default', 'Check, %\'#-10s, three', 'one, two');
		$this->assertEquals($expected, $result);
	}

/**
 * testTranslateDomainPluralWithFormatSpecifiers
 *
 * @return void
 */
	public function testTranslateDomainPluralWithFormatSpecifiers() {
		$result = __dn('core', '%+5d item.', '%+5d items.', 1, 1);
		$expected = '   +1 item.';
		$this->assertEquals($expected, $result);

		$result = __dn('core', '%-5d item.', '%-5d items.', 10, 10);
		$expected = '10    items.';
		$this->assertEquals($expected, $result);

		$result = __dn('core', '%\'#+5d item.', '%\'*+5d items.', 1, 1);
		$expected = '###+1 item.';
		$this->assertEquals($expected, $result);

		$result = __dn('core', '%\'#+5d item.', '%\'*+5d items.', 90, 90);
		$expected = '**+90 items.';
		$this->assertEquals($expected, $result);

		$result = __dn('core', '%\'#+5d item.', '%\'*+5d items.', 9000, 9000);
		$expected = '+9000 items.';
		$this->assertEquals($expected, $result);
	}

/**
 * test testTranslatePluralWithFormatSpecifiers
 *
 * @return void
 */
	public function testTranslatePluralWithFormatSpecifiers() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __n('%-5d = 1', '%-5d = 0 or > 1', 10);
		$expected = '%-5d = 0 or > 1 (translated)';
		$this->assertEquals($expected, $result);
	}

/**
 * test testTranslateDomainCategoryWithFormatSpecifiers
 *
 * @return void
 */
	public function testTranslateDomainCategoryWithFormatSpecifiers() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __dc('default', '%+10s world', 6, 'hello');
		$expected = '     hello world';
		$this->assertEquals($expected, $result);

		$result = __dc('default', '%-10s world', 6, 'hello');
		$expected = 'hello      world';
		$this->assertEquals($expected, $result);

		$result = __dc('default', '%\'@-10s world', 6, 'hello');
		$expected = 'hello@@@@@ world';
		$this->assertEquals($expected, $result);
	}

/**
 * test testTranslateDomainCategoryPluralWithFormatSpecifiers
 *
 * @return void
 */
	public function testTranslateDomainCategoryPluralWithFormatSpecifiers() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __dcn('default', '%-5d = 1', '%-5d = 0 or > 1', 0, 6);
		$expected = '%-5d = 0 or > 1 (translated)';
		$this->assertEquals($expected, $result);

		$result = __dcn('default', '%-5d = 1', '%-5d = 0 or > 1', 1, 6);
		$expected = '%-5d = 1 (translated)';
		$this->assertEquals($expected, $result);
	}

/**
 * test testTranslateCategoryWithFormatSpecifiers
 *
 * @return void
 */
	public function testTranslateCategoryWithFormatSpecifiers() {
		$result = __c('Some string with %+10s', 6, 'arguments');
		$expected = 'Some string with  arguments';
		$this->assertEquals($expected, $result);

		$result = __c('Some string with %-10s: args', 6, 'arguments');
		$expected = 'Some string with arguments : args';
		$this->assertEquals($expected, $result);

		$result = __c('Some string with %\'*-10s: args', 6, 'arguments');
		$expected = 'Some string with arguments*: args';
		$this->assertEquals($expected, $result);
	}

/**
 * test __n()
 *
 * @return void
 */
	public function testTranslatePlural() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __n('%d = 1', '%d = 0 or > 1', 0);
		$expected = '%d = 0 or > 1 (translated)';
		$this->assertEquals($expected, $result);

		$result = __n('%d = 1', '%d = 0 or > 1', 1);
		$expected = '%d = 1 (translated)';
		$this->assertEquals($expected, $result);

		$result = __n('%d = 1 (from core)', '%d = 0 or > 1 (from core)', 2);
		$expected = '%d = 0 or > 1 (from core translated)';
		$this->assertEquals($expected, $result);

		$result = __n('%d item.', '%d items.', 1, 1);
		$expected = '1 item.';
		$this->assertEquals($expected, $result);

		$result = __n('%d item for id %s', '%d items for id %s', 2, 2, '1234');
		$expected = '2 items for id 1234';
		$this->assertEquals($expected, $result);

		$result = __n('%d item for id %s', '%d items for id %s', 2, array(2, '1234'));
		$expected = '2 items for id 1234';
		$this->assertEquals($expected, $result);
	}

/**
 * test __d()
 *
 * @return void
 */
	public function testTranslateDomain() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __d('default', 'Plural Rule 1');
		$expected = 'Plural Rule 1 (translated)';
		$this->assertEquals($expected, $result);

		$result = __d('core', 'Plural Rule 1');
		$expected = 'Plural Rule 1';
		$this->assertEquals($expected, $result);

		$result = __d('core', 'Plural Rule 1 (from core)');
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEquals($expected, $result);

		$result = __d('core', 'Some string with %s', 'arguments');
		$expected = 'Some string with arguments';
		$this->assertEquals($expected, $result);

		$result = __d('core', 'Some string with %s %s', 'multiple', 'arguments');
		$expected = 'Some string with multiple arguments';
		$this->assertEquals($expected, $result);

		$result = __d('core', 'Some string with %s %s', array('multiple', 'arguments'));
		$expected = 'Some string with multiple arguments';
		$this->assertEquals($expected, $result);
	}

/**
 * test __dn()
 *
 * @return void
 */
	public function testTranslateDomainPlural() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __dn('default', '%d = 1', '%d = 0 or > 1', 0);
		$expected = '%d = 0 or > 1 (translated)';
		$this->assertEquals($expected, $result);

		$result = __dn('core', '%d = 1', '%d = 0 or > 1', 0);
		$expected = '%d = 0 or > 1';
		$this->assertEquals($expected, $result);

		$result = __dn('core', '%d = 1 (from core)', '%d = 0 or > 1 (from core)', 0);
		$expected = '%d = 0 or > 1 (from core translated)';
		$this->assertEquals($expected, $result);

		$result = __dn('default', '%d = 1', '%d = 0 or > 1', 1);
		$expected = '%d = 1 (translated)';
		$this->assertEquals($expected, $result);

		$result = __dn('core', '%d item.', '%d items.', 1, 1);
		$expected = '1 item.';
		$this->assertEquals($expected, $result);

		$result = __dn('core', '%d item for id %s', '%d items for id %s', 2, 2, '1234');
		$expected = '2 items for id 1234';
		$this->assertEquals($expected, $result);

		$result = __dn('core', '%d item for id %s', '%d items for id %s', 2, array(2, '1234'));
		$expected = '2 items for id 1234';
		$this->assertEquals($expected, $result);
	}

/**
 * test __c()
 *
 * @return void
 */
	public function testTranslateCategory() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __c('Plural Rule 1', 6);
		$expected = 'Plural Rule 1 (translated)';
		$this->assertEquals($expected, $result);

		$result = __c('Plural Rule 1 (from core)', 6);
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEquals($expected, $result);

		$result = __c('Some string with %s', 6, 'arguments');
		$expected = 'Some string with arguments';
		$this->assertEquals($expected, $result);

		$result = __c('Some string with %s %s', 6, 'multiple', 'arguments');
		$expected = 'Some string with multiple arguments';
		$this->assertEquals($expected, $result);

		$result = __c('Some string with %s %s', 6, array('multiple', 'arguments'));
		$expected = 'Some string with multiple arguments';
		$this->assertEquals($expected, $result);
	}

/**
 * test __dc()
 *
 * @return void
 */
	public function testTranslateDomainCategory() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __dc('default', 'Plural Rule 1', 6);
		$expected = 'Plural Rule 1 (translated)';
		$this->assertEquals($expected, $result);

		$result = __dc('default', 'Plural Rule 1 (from core)', 6);
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEquals($expected, $result);

		$result = __dc('core', 'Plural Rule 1', 6);
		$expected = 'Plural Rule 1';
		$this->assertEquals($expected, $result);

		$result = __dc('core', 'Plural Rule 1 (from core)', 6);
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEquals($expected, $result);

		$result = __dc('core', 'Some string with %s', 6, 'arguments');
		$expected = 'Some string with arguments';
		$this->assertEquals($expected, $result);

		$result = __dc('core', 'Some string with %s %s', 6, 'multiple', 'arguments');
		$expected = 'Some string with multiple arguments';
		$this->assertEquals($expected, $result);

		$result = __dc('core', 'Some string with %s %s', 6, array('multiple', 'arguments'));
		$expected = 'Some string with multiple arguments';
		$this->assertEquals($expected, $result);
	}

/**
 * test __dcn()
 *
 * @return void
 */
	public function testTranslateDomainCategoryPlural() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __dcn('default', '%d = 1', '%d = 0 or > 1', 0, 6);
		$expected = '%d = 0 or > 1 (translated)';
		$this->assertEquals($expected, $result);

		$result = __dcn('default', '%d = 1 (from core)', '%d = 0 or > 1 (from core)', 1, 6);
		$expected = '%d = 1 (from core translated)';
		$this->assertEquals($expected, $result);

		$result = __dcn('core', '%d = 1', '%d = 0 or > 1', 0, 6);
		$expected = '%d = 0 or > 1';
		$this->assertEquals($expected, $result);

		$result = __dcn('core', '%d item.', '%d items.', 1, 6, 1);
		$expected = '1 item.';
		$this->assertEquals($expected, $result);

		$result = __dcn('core', '%d item for id %s', '%d items for id %s', 2, 6, 2, '1234');
		$expected = '2 items for id 1234';
		$this->assertEquals($expected, $result);

		$result = __dcn('core', '%d item for id %s', '%d items for id %s', 2, 6, array(2, '1234'));
		$expected = '2 items for id 1234';
		$this->assertEquals($expected, $result);
	}

/**
 * test debug()
 *
 * @return void
 */
	public function testDebug() {
		ob_start();
		debug('this-is-a-test', false);
		$result = ob_get_clean();
		$expectedText = <<<EXPECTED
%s (line %d)
########## DEBUG ##########
'this-is-a-test'
###########################

EXPECTED;
		$expected = sprintf($expectedText, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 9);

		$this->assertEquals($expected, $result);

		ob_start();
		debug('<div>this-is-a-test</div>', true);
		$result = ob_get_clean();
		$expectedHtml = <<<EXPECTED
<div class="cake-debug-output">
<span><strong>%s</strong> (line <strong>%d</strong>)</span>
<pre class="cake-debug">
&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;
</pre>
</div>
EXPECTED;
		$expected = sprintf($expectedHtml, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 10);
		$this->assertEquals($expected, $result);

		ob_start();
		debug('<div>this-is-a-test</div>', true, true);
		$result = ob_get_clean();
		$expected = <<<EXPECTED
<div class="cake-debug-output">
<span><strong>%s</strong> (line <strong>%d</strong>)</span>
<pre class="cake-debug">
&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;
</pre>
</div>
EXPECTED;
		$expected = sprintf($expected, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 10);
		$this->assertEquals($expected, $result);

		ob_start();
		debug('<div>this-is-a-test</div>', true, false);
		$result = ob_get_clean();
		$expected = <<<EXPECTED
<div class="cake-debug-output">

<pre class="cake-debug">
&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;
</pre>
</div>
EXPECTED;
		$expected = sprintf($expected, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 10);
		$this->assertEquals($expected, $result);

		ob_start();
		debug('<div>this-is-a-test</div>', null);
		$result = ob_get_clean();
		$expectedHtml = <<<EXPECTED
<div class="cake-debug-output">
<span><strong>%s</strong> (line <strong>%d</strong>)</span>
<pre class="cake-debug">
&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;
</pre>
</div>
EXPECTED;
		$expectedText = <<<EXPECTED
%s (line %d)
########## DEBUG ##########
'<div>this-is-a-test</div>'
###########################

EXPECTED;
		if (php_sapi_name() === 'cli') {
			$expected = sprintf($expectedText, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 18);
		} else {
			$expected = sprintf($expectedHtml, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 19);
		}
		$this->assertEquals($expected, $result);

		ob_start();
		debug('<div>this-is-a-test</div>', null, false);
		$result = ob_get_clean();
		$expectedHtml = <<<EXPECTED
<div class="cake-debug-output">

<pre class="cake-debug">
&#039;&lt;div&gt;this-is-a-test&lt;/div&gt;&#039;
</pre>
</div>
EXPECTED;
		$expectedText = <<<EXPECTED

########## DEBUG ##########
'<div>this-is-a-test</div>'
###########################

EXPECTED;
		if (php_sapi_name() === 'cli') {
			$expected = sprintf($expectedText, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 18);
		} else {
			$expected = sprintf($expectedHtml, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 19);
		}
		$this->assertEquals($expected, $result);

		ob_start();
		debug('<div>this-is-a-test</div>', false);
		$result = ob_get_clean();
		$expected = <<<EXPECTED
%s (line %d)
########## DEBUG ##########
'<div>this-is-a-test</div>'
###########################

EXPECTED;
		$expected = sprintf($expected, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 9);
		$this->assertEquals($expected, $result);

		ob_start();
		debug('<div>this-is-a-test</div>', false, true);
		$result = ob_get_clean();
		$expected = <<<EXPECTED
%s (line %d)
########## DEBUG ##########
'<div>this-is-a-test</div>'
###########################

EXPECTED;
		$expected = sprintf($expected, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 9);
		$this->assertEquals($expected, $result);

		ob_start();
		debug('<div>this-is-a-test</div>', false, false);
		$result = ob_get_clean();
		$expected = <<<EXPECTED

########## DEBUG ##########
'<div>this-is-a-test</div>'
###########################

EXPECTED;
		$expected = sprintf($expected, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 9);
		$this->assertEquals($expected, $result);

		ob_start();
		debug(false, false, false);
		$result = ob_get_clean();
		$expected = <<<EXPECTED

########## DEBUG ##########
false
###########################

EXPECTED;
		$expected = sprintf($expected, str_replace(CAKE_CORE_INCLUDE_PATH, '', __FILE__), __LINE__ - 9);
		$this->assertEquals($expected, $result);
	}

/**
 * test pr()
 *
 * @return void
 */
	public function testPr() {
		$this->skipIf(php_sapi_name() === 'cli', 'Skipping web test in cli mode');
		ob_start();
		pr('this is a test');
		$result = ob_get_clean();
		$expected = "<pre>this is a test</pre>";
		$this->assertEquals($expected, $result);

		ob_start();
		pr(array('this' => 'is', 'a' => 'test'));
		$result = ob_get_clean();
		$expected = "<pre>Array\n(\n    [this] => is\n    [a] => test\n)\n</pre>";
		$this->assertEquals($expected, $result);
	}

/**
 * test pr()
 *
 * @return void
 */
	public function testPrCli() {
		$this->skipIf(php_sapi_name() != 'cli', 'Skipping cli test in web mode');
		ob_start();
		pr('this is a test');
		$result = ob_get_clean();
		$expected = "\nthis is a test\n";
		$this->assertEquals($expected, $result);

		ob_start();
		pr(array('this' => 'is', 'a' => 'test'));
		$result = ob_get_clean();
		$expected = "\nArray\n(\n    [this] => is\n    [a] => test\n)\n\n";
		$this->assertEquals($expected, $result);
	}

/**
 * Test splitting plugin names.
 *
 * @return void
 */
	public function testPluginSplit() {
		$result = pluginSplit('Something.else');
		$this->assertEquals(array('Something', 'else'), $result);

		$result = pluginSplit('Something.else.more.dots');
		$this->assertEquals(array('Something', 'else.more.dots'), $result);

		$result = pluginSplit('Somethingelse');
		$this->assertEquals(array(null, 'Somethingelse'), $result);

		$result = pluginSplit('Something.else', true);
		$this->assertEquals(array('Something.', 'else'), $result);

		$result = pluginSplit('Something.else.more.dots', true);
		$this->assertEquals(array('Something.', 'else.more.dots'), $result);

		$result = pluginSplit('Post', false, 'Blog');
		$this->assertEquals(array('Blog', 'Post'), $result);

		$result = pluginSplit('Blog.Post', false, 'Ultimate');
		$this->assertEquals(array('Blog', 'Post'), $result);
	}

/**
 * test namespaceSplit
 *
 * @return void
 */
	public function testNamespaceSplit() {
		$result = namespaceSplit('Something');
		$this->assertEquals(array('', 'Something'), $result);

		$result = namespaceSplit('\Something');
		$this->assertEquals(array('', 'Something'), $result);

		$result = namespaceSplit('Cake\Something');
		$this->assertEquals(array('Cake', 'Something'), $result);

		$result = namespaceSplit('Cake\Test\Something');
		$this->assertEquals(array('Cake\Test', 'Something'), $result);
	}

/**
 * Tests that the stackTrace() method is a shortcut for Debugger::trace()
 *
 * @return void
 */
	public function testStackTrace() {
		ob_start();
		list($r, $expected) = [stackTrace(), \Cake\Utility\Debugger::trace()];
		$result = ob_get_clean();
		$this->assertEquals($expected, $result);

		$opts = ['args' => true];
		ob_start();
		list($r, $expected) = [stackTrace($opts), \Cake\Utility\Debugger::trace($opts)];
		$result = ob_get_clean();
		$this->assertEquals($expected, $result);
	}
}