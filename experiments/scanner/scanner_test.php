<?php

/*

Shinpuru whishlist:
- test "bag" object to store test local variables without writing `use` all the time.
  especially useful for transfer between setup and test functions. can also hold the
  result values of some assertions (e.g. HTTP stuff).
  The "test" object is the first argument to any function objects (try to make it work
  that it can be ommited when it is not needed). Should also be the argument of any
  functions some assertions take (e.g. assert_fails).
- execution highjack function. A way to take test case execution into your own hands.
  e.g. useful to execute an entire test context multiple times with different environments
  (e.g. once with using string as scanner source and once with a file source)

Shinpuru modifications:
- Changed assert_fails and assert_fails_with to intercept all `Exception`s. Otherwise they
  only catch failed asserts... not very useful to test your own exceptions.
- Moved the test execution and reporting from the exit callback to the end of the action test
  file. Otherwise exceptions do not work (results in "Exception thrown without a stack frame"
  fatal errors).
  TRIED: tried to move the calls into the ShinpuruTestSuite destruction (they are called to
    when the script is exited). Same error message. The docs even state that throwing an
    exception in a destructor results in a fatal error.
  IDEA for workaround: execute the tests at the end of the outer most context() or test()
    function. That way it still is in the normal execution path.
    Problem with multiple top level context() or test() functions. Eeach one would run it's
    tests and show a report. Idea to solve it: run tests at the end of each outer most context()
    or test() and move the show_report() function to the script shutdown handler.

*/

require('shinpuru.php');
require('scanner.php');

context('Scanner', function(){
	
	context('one_of function', function(){
		$scan = null;
		
		setup(function() use(&$scan) {
			$scan = new Scanner('abc');
		});
		
		test('should match and consume a token at the current position', function() use(&$scan) {
			assert_equal($scan->one_of('a'), 'a');
			assert_equal($scan->one_of('b'), 'b');
			assert_equal($scan->one_of('c'), 'c');
		});
		test('should accept mutliple alternatives and return the matched one', function() use(&$scan) {
			assert_equal($scan->one_of('0', '1', 'a'), 'a');
		});
		test('should work with alternatives with different lengths', function() use(&$scan) {
			assert_equal($scan->one_of('x', 'yz', 'ab'), 'ab');
			assert_equal($scan->one_of('x', 'yz', 'c'), 'c');
		});
		test('should be able to match an EOF', function() use(&$scan) {
			assert_equal($scan->one_of('abc'), 'abc');
			assert_null($scan->one_of('1', '2', null));
		});
		test('should throw an exception or return false on a missmatch', function() use(&$scan) {
			assert_fails(function() use(&$scan) {
				$scan->one_of('x');
			});
			assert_false($scan->one_of('x', false));
		});
		test('should missmatch when EOF is expected but not there', function() use(&$scan) {
			assert_fails(function() use(&$scan) {
				var_dump($scan->one_of(null));
			});
			assert_false($scan->one_of(null, false));
		});
	});
	
	context('as_long_as function', function(){
		test('should consume input while it matches and return a peek at the first unmatched token', function() use(&$scan) {
			$scan = new Scanner('aba!');
			assert_equal($scan->as_long_as('a', 'b'), array('aba', '!'));
			assert_equal($scan->as_long_as('!'), array('!', null));
		});
		
		test('should return the peek with the length of the longest token', function(){
			$scan = new Scanner('23 01 457');
			assert_equal($scan->as_long_as('01', '23', '456', ' '), array('23 01 ', '457'));
			assert_equal($scan->as_long_as('457'), array('457', null));
		});
	});
	
	context('until function', function(){
		test('should consume input until one of the tokens matches and peek the matched token', function(){
			$scan = new Scanner("name: foo\n  end\n  ");
			assert_equal($scan->until(':'), array('name', ':'));
			assert_equal($scan->until("\n"), array(': foo', "\n"));
			assert_equal($scan->until('end'), array("\n  ", 'end'));
			assert_equal($scan->until(null), array("end\n  ", null));
		});
	});
	
	context('peek function', function(){
		test('should allow to peek for a tokens without consuming them and without throwing exceptions', function(){
			$scan = new Scanner('123');
			assert_equal($scan->peek('1'), '1');
			assert_equal($scan->peek('12'), '12');
			assert_equal($scan->peek('x', 'yz', '1'), '1');
			
			assert_equal($scan->peek('00000'), false);
			assert_equal($scan->peek('x', 'y', '012'), false);
			
			assert_equal($scan->one_of('123'), '123');
			assert_equal($scan->peek('1'), false);
			assert_null($scan->peek(null));
			assert_null($scan->peek('1', 'a', null), null);
		});
	});
	
	context('bytes function', function(){
		test('should read and return a fixed number of bytes', function(){
			$scan = new Scanner('data:aGVsbG8gd29ybGQsIHRoaXMgaXMganVzdCBhIHRlc3Q=');
			assert_equal($scan->one_of('data:'), 'data:');
			assert_equal($scan->bytes(44), 'aGVsbG8gd29ybGQsIHRoaXMgaXMganVzdCBhIHRlc3Q=');
		});
		test('should return false if the data could only be read incomplete', function(){
			$scan = new Scanner('data:aGVsbG8');
			assert_equal($scan->one_of('data:'), 'data:');
			assert_false($scan->bytes(44));
		});
		test('should return null if we are at EOF', function(){
			$scan = new Scanner('data:');
			assert_equal($scan->one_of('data:'), 'data:');
			assert_null($scan->bytes(44));
		});
	});
	
	context('the matching functions', function(){
		test('should also accept lambdas as tokens', function(){
			$scan = new Scanner("name: \tfoo");
			assert_equal( $scan->as_long_as(function($token){ return ctype_alnum($token); }), array('name', ':') );
			assert_equal( $scan->as_long_as(function($token){ return ctype_space($token); }, ':'), array(": \t", 'f') );
			assert_equal($scan->until(null), array('foo', null));
		});
	});
	
	/* maybe not needed…
	context('with_filter', function(){
		
		test('should allow to process the tokens before matching', function(){
			$scan = new Scanner('NAME: foo');
			$scan->with_filter(function($token){
				return strtolower($token);
			}, function($scan){
				assert_equal($scan->one_of('name'), 'name');
			});
		});
		
	});
	*/
});

$failed_tests = $shinpuru_test_suite->run_tests();
$shinpuru_test_suite->show_report();
exit($failed_tests);

?>