<?php

/**
 * Shinpuru v1.0.0
 * http://arkanis.de/projects/shinpuru/
 * By Stephan Soller <stephan.soller@helionweb.de>
 * Released under the MIT license
 */

/** topic: intro
 * 
 * Shinpuru is a small testing framework for PHP. It's designed to be as small and simple as possible
 * while still being useful and comfortable. You can use it to test a single class, "your own little blog"™, multiple
 * websites at once, a REST interface or everything you can think of doing with PHP, HTTP, HTML
 * and XML (and that is _a lot_).
 * 
 * For those who are not familiar with testing yet: Basically it's all about writing small functions that first do something and then verify that an
 * expected result actually occured. For example you can write a test that fetches the index page of
 * your weblog and then verifies that it contains 10 headlines. Here is how this looks like in Shinpuru:
 * 
 * 	code: /tests/basic_usage_test
 * 
 * When you now run that test script from the command line you'll get a nice report of the test:
 * 
 * 	console(dir: tests): php basic_usage_test.php
 * 
 * Of course there is more to Shinpuru than the small example shows. For _writing tests_ Shinpuru has
 * a compact but handy arsenal:
 * 
 * - [Basic assertions][1]: Assertions to verify the result of "usual" PHP code.
 * - [HTTP request functions][2]: Functions for common HTTP methods (`get()`, `post()`, `put()`, `delete()`)
 *   and ability to build customize requests in any way.
 * - [HTTP/HTML/XML assertions][3]: Assertions to verify the HTTP response using simple CSS selectors and
 *   XPath.
 * - [Flow control assertions][4] using checkpoints and exceptions.
 * 
 * Writing tests is one thing but _managing and running tests_ can get difficult if you have many tests at
 * hand. Shinpuru also tries to help with this:
 * 
 * - [Contexts][5]: You can structure your tests into several contexts (e.g. test for the front page, the archive,
 *   the backend, etc.) and you can nest contexts if you want.
 * - [Setup][6] and [teardown][7] functions: Every context can contain setup and teardown functions that are
 *   run before and after each test of the context.
 * - [Autotest][8]: You can run every Shinpuru test in the background. Every time a file is changed the test
 *   will be run automatically and a nice confirmation bubble will notify you if all went well or if a test failed.
 * - [Environments][9]: Run or skip tests according to a command line option, e.g. skip database reset when
 *   the test is run on the server in the production environment.
 * 
 * All this of course isn't new. In case of Shinpuru it's all heavily inspired by projects from the Ruby world like
 * Rails and Shoulda. 
 * 
 * [1]: reference#basic-assertions
 * [2]: reference#http-request-functions
 * [3]: reference#http-assertions
 * [4]: reference#flow-control-assertions
 * [5]: reference#context
 * [6]: reference#setup
 * [7]: reference#teardown
 * [8]: #autotest
 * [9]: reference#environments
 */

/** topic: getting-started
 * 
 * If you are new to the whole testing world the next few lines will try to give you a starting point from
 * where you can venture on by your own. In case you are already familiar with testing (or know
 * [Shoulda][shoulda]) you better take a look at the [features](#features) or the [reference][ref].
 * 
 * [shoulda]: http://github.com/thoughtbot/shoulda
 * [ref]: reference
 * 
 * ### Installation… sort of
 * 
 * To write your own tests with Shinpuru you just have to [download](#download) and `require` it. That's
 * all. One download, one `require` statement and you can start writing your test code. The catch is that
 * you need PHP 5.3 or newer.
 * 
 * That's it for the "installation". Now we can start writing a small test.
 * 
 * ### A small test
 * 
 * We will start with the absolute bare minimum for a test case:
 * 
 * 	code: /tests/getting_started/bare_minimum
 * 
 * Save the code in the file `bare_minimum.php` and place it in the same directory as Shinpuru
 * (`shinpuru.php`). Of course this test will do nothing useful because we just said that we want to test
 * "hello world!" but didn't say how to do it. But for now lets just run it anyway and see what happens. To
 * do this we just execute the test on the command line:
 * 
 * 	console(dir: tests/getting_started): php bare_minimum.php
 * 
 * The output shows that one test "passed" (gave up on doing something useful). This is the standard
 * behavior of empty tests and kind of a reminder that we still have to implement this one. You can use
 * this to quickly write a number of tests out of your head and then implement them one after the other.
 * 
 * For now however we want to create a test that actually tests something. Because the world as a whole
 * is somewhat hard to test with a simple PHP script we just try something else: test if a website is online
 * and responds properly.
 * 
 * 	code: /tests/getting_started/my_website
 * 
 * We specify the code to test "my website" within an [anonymus function][afun]. These are new in PHP 5.3
 * and are quite useful. If you know JavaScript you should be familiar with this kind of functions as well
 * as their syntax. They are also heavily used in the Ruby language.
 * 
 * [afun]: http://php.net/functions.anonymous
 * 
 * In the test we fire an HTTP get request at `http://arkanis.de/` which happens to be my website but of
 * course you should insert your own website there. After the request we just check that the HTTP response
 * code is `200` which is the code for "everything is alright". Now lets see what happens if we run the test:
 * 
 * 	console(dir: tests/getting_started): php my_website.php
 * 
 * Now we have on successful test! The difference to our "hello world!" test is that the test actually did
 * something and was successful in doing so. Therefore it's listed as successful.
 * 
 * ### Make it break
 * 
 * If everything works out all the time the world would be a very dull place. Lets just make it break a bit
 * by testing an URL that does not exist:
 * 
 * 	code: /tests/getting_started/explode_website
 * 
 * My website doesn't have an `explode` subdomain and therefore this code is doomed to fail. Because
 * everyone likes explosions lets take a look how it explodes (but don't take photos, please):
 * 
 * 	console(dir: tests/getting_started): php explode_website.php
 * 
 * Now that was a nice explosion! Actually `get()` could not find out who `explode.arkanis.de` is and
 * therefore failed to contact this server. Our `assert_response()` check is actually never called because
 * `get()` already exploded and it's useless to continue the test.
 * 
 * For the curious among you: try to request an URL where you get one of those ugly 404 ("file not found")
 * pages and see what the test does.
 * 
 * ### More tests
 * 
 * One test is nice but often you want to do a bit more. Therefore Shinpuru and other test frameworks provide
 * ways to manage many tests in a logical way:
 * 
 * 	code: /tests/getting_started/full_website
 * 
 * This test suite is only a rough map of what should be there and contains no test code at all. Therefore all
 * tests are marked as "passed" (read: please fill in your test code here). So if you just want to make up your
 * mind about what should be in the test suite the names of the test are enought to start with.
 * 
 * You can use contexts to group related tests together and you can nest them as much as you like. If a test
 * fails the context of the test is always shown so you know what's broken.
 * 
 * ### What next?
 * 
 * Now you have all basic knowledge for writing tests. From here on it's just a matter of experimentation and
 * throwing in new assertions (functions like `assert_response()`) here and there. It's a good idea to glance
 * though the list of available assertions over in the [reference][ref] since these are the functions that actually
 * check something. The more assertions you use in your tests the more they cover. ;)
 * 
 * If you're running Ubuntu Linux I strongly recomment that you look at the [autotest feature](#autotest)
 * since it will greatly speed up writing tests.
 * 
 * [1]: reference
 */

/** topic: feedback
 * 
 * Comments and ideas about Shinpuru are always welcome. Please post a comment on [my weblog][1] or
 * send me a mail to <stephan.soller@helionweb.de>. If there is enough interest a public repository and a
 * forum or something like this can be established.
 * 
 * [1]: http://arkanis.de/weblog/2010-11-17-shinpuru
 */

/** topic: why?
 * 
 * All this isn't new, so why another testing "framework"? The single idea behind all of Shinpuru originally
 * was: I'm to lazzy to check if all my websites and projects are still working… so let the computer do it for
 * me. Well, I wanted something small that doesn't need maintenance so I didn't even looked for existing
 * frameworks but started to write something on my own. To be honest I just wanted to take the anonymous
 * functions of PHP 5.3 out for a test drive and simply got cought up in doing so. So be prepared to use
 * anonymous functions often (yes, they are _that_ handy). ;)
 * 
 * Out came a small and simple test framework inspired by [Shoulda][1] and a little taste of autotest (both
 * are from the Ruby world). If you know Shoulda you should instantly feel familiar with Shinpuru… well,
 * except from the fact that it's PHP. However since I really don't like administration it had to be a simple
 * "drop in one PHP file and be happy" thing with PHP 5.3 as it's only "dependency" (that will be none then,
 * I suppose).
 * 
 * [1]: http://github.com/thoughtbot/shoulda
 */

class AssertException extends Exception { }
class SkippedException extends AssertException { }
class PassedException extends AssertException { }
class PhpErrorException extends AssertException {
	public function __construct($message, $file, $line, $code = 0, Exception $previous = null){
		parent::__construct($message, $code, $previous);
		$this->file = $file;
		$this->line = $line;
	}
}


class ShinpuruTestSuite {
	
	protected static $styles = array(
		'unknown' => array('before' => '', 'after' => ''),
		'highlight' => array('before' => "\033[1m", 'after' =>  "\033[22m"),
		'successful' => array('before' => "\033[32m", 'after' => "\033[0m"),
		'skipped' => array('before' => "\033[34m", 'after' => "\033[0m"),
		'passed' => array('before' => "\033[34m", 'after' => "\033[0m"),
		'failed' => array('before' => "\033[31m", 'after' => "\033[0m")
	);
	
	/** topic: Command line arguments
	 * 
	 * Every Shinpuru test supports the following command line arguments:
	 * 
	 * `-a`, `--autotest`
	 * 
	 * : This option activates autotest mode. The test will be run once and after that the current directory
	 *   (and its subdirectories) will be monitored for changes. As soon as a changed file is saved the test
	 *   will be run again. If you work with Ubuntu Linux a confirmation bubble will show the result of the
	 *   test run. Use the `--silent` option to disable this. If you want Shinpuru to monitor other directories
	 *   than the current one use the `--monitor` option to specify one or more directories you want to be
	 *   monitored for changes.
	 * 
	 * `-s`, `--silent`
	 * 
	 * : This option prevents Shinpuru from triggering confirmation bubbles when run in autotest mode.
	 * 
	 * `-m PATH`, `--monitor PATH`
	 * 
	 * : Monitors `PATH` for changes. If `PATH` is a file this file will be monitored. If it's a directory all files
	 *   in it and its subdirectories will be monitored. As soon as a monitored file changes the test will be run
	 *   again. You can use this option multiple times to make Shinpuru monitor multiple paths. `--monitor`
	 *   also enables the autotest mode so you don't need to specify `--autotest` once you specified `--monitor`.
	 *   In fact `--autotest` is more like a shortcut for `--monitor .` (the dot is the current directory).
	 * 
	 * `-r STATUS`, `--report STATUS`
	 * 
	 * : Makes Shinpuru print a report which lists all tests with the status `STATUS`. Valid states are `failed`
	 *   (the default), `passed`, `skipped` and `successful`. You can specify multiple states by using multiple
	 *   `--report` options, e.g. `--report failed --report passed`. This will report all failed and passed tests.
	 * 
	 * `-e ENV`, `--environment ENV`
	 * 
	 * : Runs the test in the environment `ENV`. The code after `skip_in()` calls for `ENV` will be skipped
	 *   and the code after `only_in()` calls for `ENV` will be run. This allows you to write test cases that don't
	 *   run destructive tests in a production environment. You can specify multiple environments by using
	 *   multiple `--environment` options to further fine tune what test cases should be run (e.g. on a feature by
	 *   feature basis).
	 */
	// The array of status names for which a detailed report should be shown. The default is set when the
	// constructor parses the command line arguments.
	private $report_states;
	// The array of environment names the test suite is run in. These are set through the command line and
	// the default is set when the constructor parses the command line arguments.
	private $environments;
	
	/**
	 * Reads the passed command line parameters and sets corresponding settings or options to the
	 * specified values.
	 */
	function __construct($command_line_args){
		$this->name = basename($command_line_args[0]);
		
		// Creates an associative array with the argument name as key and an array of all its values
		// as value.
		$group_args = function($args){
			$arg_name = null;
			$grouped_args = array();
			while (($arg = next($args)) !== false) {
				if ($arg[0] == '-') {
					$arg_name = $arg;
					if ( !isset($grouped_args[$arg_name]) )
						$grouped_args[$arg_name] = array();
				} elseif ( $arg_name !== null ) {
					array_push($grouped_args[$arg_name], $arg);
				}
			}
			return $grouped_args;
		};
		
		// Creates a normal argument list out of the grouped arguments.
		$ungroup_args = function($grouped_args, $arg_name_blacklist = array()){
			$args = array();
			foreach($grouped_args as $name => $values){
				if ( !in_array($name, $arg_name_blacklist) ) {
					foreach($values as $value){
						$args[] = $name;
						$args[] = $value;
					}
				}
			}
			return $args;
		};
		
		// Returns true if the specified command line argument is present.
		$argument_present = function($short_name, $long_name, $grouped_args){
			return array_key_exists($short_name, $grouped_args) or array_key_exists($long_name, $grouped_args);
		};
		
		// Returns the values for the specified command line argument as an array. If $only_last_value is set
		// to true only the last argument value is returned.
		$argument_values = function($short_name, $long_name, $grouped_args, $only_last_value = false){
			$values = array_key_exists($short_name, $grouped_args) ? $grouped_args[$short_name] : array();
			if ( array_key_exists($long_name, $grouped_args) )
				$values = array_merge($values, $grouped_args[$long_name]);
			return $only_last_value ? end($values) : $values;
		};
		
		$grouped_args = $group_args($command_line_args);
		
		// Read the autotest related arguments
		$monitor_paths = $argument_values('-m', '--monitor', $grouped_args);
		$silent = $argument_present('-s', '--silent', $grouped_args);
		if ( $argument_present('-a', '--autotest', $grouped_args) )
			$monitor_paths[] = '.';
		
		if ( !empty($monitor_paths) ) {
			$child_test_args = $ungroup_args($grouped_args, array('-a', '--autotest', '-s', '--silent', '-m', '--monitor'));
			array_unshift($child_test_args, $command_line_args[0]);
			// This call will never return, run_as_monitor() calls exit() when it's done
			$this->run_as_monitor($monitor_paths, $child_test_args, !$silent);
		}
		
		// Read the states the report should list
		$this->report_states = $argument_values('-r', '--report', $grouped_args);
		if ( !$argument_present('-r', '--report', $grouped_args) )
			$this->report_states = array('failed');
		
		// Read the environments this test in run in
		$this->environments = $argument_values('-e', '--environment', $grouped_args);
		
		// Call assertion hooks for initialization and define the global shortcut functions
		$this->call_assertion_hooks('init_');
		$this->define_global_shortcut_functions( array('run_tests', 'show_report') );
	}
	
	/**
	 * Runs all methods of this class that start with the specified prefix (e.g. "prepare_"). This is used
	 * by different assertitions to initialize their data structures and register setup and teardown
	 * functions in the global context.
	 */
	protected function call_assertion_hooks($method_prefix){
		$methods = get_class_methods($this);
		foreach($methods as $method){
			if( preg_match('/^' . preg_quote($method_prefix) . '/', $method) )
				$this->$method();
		}
	}
	
	/**
	 * Defines global shortcut functions so you don't have to drag the $shinpuru_test_suite variable around
	 * scopes and can write
	 * 
	 * 	assert_true(1 == 1);
	 * 
	 * instead of
	 * 
	 * 	$shinpuru_test_suite->assert_true(1 == 1);
	 * 
	 **
	 * Collects all public functions of this class that are not in the specified no_shortcuts_for list and
	 * don't start with "__" (e.g. like "__get"). For each one a global function with the same name is
	 * created that calls this function on the variable "$test" which is the Shinpuru instance in the
	 * global scope.
	 */
	protected function define_global_shortcut_functions($no_shortcuts_for){
		$reflector = new ReflectionClass($this);
		foreach ($reflector->getMethods(ReflectionMethod::IS_PUBLIC) as $method)
		{
			if ( substr($method->name, 0, 2) == '__' or in_array($method->name, $no_shortcuts_for) )
				continue;
			eval('function ' . $method->name . '(){' .
				'global $shinpuru_test_suite;' .
				'$args = func_get_args();' .
				'return call_user_func_array(array($shinpuru_test_suite, "' . $method->name . '"), $args);' .
			' }');
		}
	}
	
	/** topic: autotest
	 * 
	 * Usually you write your tests in some kind of text editor, save them, switch to the command line
	 * and run them. While this isn't much overhead it can add up.
	 * 
	 * ![Confirmation bubble of a successful test](images/confirmation-bubble.png)
	 * 
	 * In the Ruby on Rails world there is a tool called [autotest][rails-autotest] that runs the test
	 * automatically as soon as files are modified and saved. Someone later added Ubuntu OSD
	 * notifications to the mix and this gave writing tests a good flow.
	 * 
	 * Shinpuru also provides this feature and it's as easy as running a test with the `-a` (short for
	 * `--autotest`) option. However PHP can't do this out of the box and therefore you need to install
	 * the `inotify` PHP
	 * extension as well as the `libnotify-bin` package. On Ubuntu these commands will do:
	 * 
	 * 	sudo apt-get install php5-dev php-pear
	 * 	sudo pecl install channel://pecl.php.net/inotify-0.1.4
	 * 	sudo apt-get install libnotify-bin
	 * 
	 * Now Shinpuru will monitor the current directory (and all child directories) for changes. As soon
	 * as something happens the test will run and you get a nice confirmation message showing what
	 * happened. This way you can focus on your source code while the test result is shown shortly each
	 * time you save the file.
	 * 
	 * You can modify the behavior of the autotest mode with the [`--monitor` and `--silent` command
	 * line arguments][cmd]. With `--monitor` you can make Shinpuru monitor more directories or files,
	 * e.g. if your source file you edit is not in a subdirectory of the test. `--silent` on the other hand
	 * will turn off the confirmation bubbles in case you don't like them.
	 * 
	 * [rails-autotest]: http://ph7spot.com/musings/getting-started-with-autotest
	 * [cmd]: reference#command-line-arguments
	 */
	
	/**
	 * Waits for changes in the specified directory or file (e.g. saving a source file in the editor) and
	 * runs a test after each change. This is done by a child process that runs with the $test_options
	 * command line parameters.
	 * 
	 * If $send_notifications is set to true the monitor also tries to send notifications using the
	 * notify-send command line program.
	 */
	protected static function run_as_monitor($monitor_paths, $test_args, $send_notifications){
		if ( !extension_loaded('inotify') ) {
			file_put_contents('php://stderr', 'The inotify extension is not loaded but is needed to look for changes. Please install and enable it and try again.' . "\n");
			exit(246);
		}
		
		exec('which notify-send', &$output, &$return_code);
		if ( $send_notifications and $return_code != 0 ) {
			file_put_contents('php://stderr', 'The notify-send program could not be found and therefore no notifications will be send.' . "\n");
			$send_notifications = false;
		}
		
		$paths = array();
		foreach($monitor_paths as $path){
			if ( is_file($path) ) {
				$paths[] = $path;
			} elseif ( is_dir($path) ) {
				$dir_iterator = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS);
				$files = new RecursiveIteratorIterator($dir_iterator);
				foreach($files as $path => $file_info)
					$paths[] = $path;
			}
		}
		
		$inotify_instance = inotify_init();
		$watchers = array();
		foreach($paths as $path){
			$watch = inotify_add_watch($inotify_instance, $path, IN_CLOSE_WRITE);
			$watchers[$watch] = $path;
		}
		
		// Just add a dummy handler to prevent the default action (killing the script). The signal
		// interrupts inotify_read() which is enough for us. No need to do something in the handler
		// and therefore no need for declare(ticks).
		pcntl_signal(SIGINT, function(){});
		
		$test_command = 'php ' . join(' ', $test_args);
		while(true){
			passthru($test_command, $exit_code);
			
			if ($send_notifications) {
				if ($exit_code == 0) {
					$summary = 'Good Job!';
					$body = 'No tests failed.';
					$icon = '/usr/share/icons/gnome/48x48/emblems/emblem-default.png';
				} elseif($exit_code <= 245) {
					$failed_tests = $exit_code;
					$pluralized_test = ($failed_tests == 1) ? 'test' : 'tests';
					$summary = 'Something exploded…';
					$body = sprintf('%d %s failed.', $failed_tests, $pluralized_test);
					$icon = '/usr/share/icons/gnome/48x48/emotes/face-raspberry.png';
					// /usr/share/icons/gnome/scalable/emotes/face-raspberry.svg
					// /usr/share/icons/gnome/scalable/actions/stop.svg
				} else {
					$summary = 'PHP error';
					$body = "PHP couldn't run the test. Forgot a semicolon?";
					$icon = '/usr/share/icons/gnome/48x48/status/error.png';
				}
				
				// Shows a confirmation bubble (shorter timeout and replaces the previous one)
				// See https://wiki.ubuntu.com/NotifyOSD#org.freedesktop.Notifications.Notify
				system('notify-send --hint string:x-canonical-private-synchronous: --icon=' . escapeshellarg($icon) . ' ' . escapeshellarg($summary) . ' ' . escapeshellarg($body));
			}
			
			print('Waiting for changes… press ctrl+c to quit' . "\n");
			$events = @inotify_read($inotify_instance);
			if (!$events) // break the loop if interrupted by ctrl+c
				break;
			
			foreach($events as $event)
				print('Changes on ' . $watchers[$event['wd']] . ' detected' . "\n");
		}
		
		print('Done. Have a nice and error free day. :)' . "\n");
		
		foreach($watchers as $watcher => $path)
			inotify_rm_watch($inotify_instance, $watcher);
		fclose($inotify_instance);
		exit(0);
	}
	
	
	/** topic: Options
	 * 
	 * Options change some details in how Shinpuru or some of its functions work. For example you can set
	 * a common base URL that is used by all HTTP request functions instead of writing it into every function
	 * call. Options can be set using the `option()` method and be read as properties of the test suite returned
	 * by the `suite()` function. These options are currently available:
	 * 
	 * `base_url`
	 * 
	 * : This string will be prepended to any URL requested by the HTTP request functions (`get()`, `post()`,
	 *   …). This will spare you the work to write the full URL at each function call. Combined with environments
	 *   you can set different base URLs depending on the environment the test suite runs in. The default is an
	 *   empty string.
	 * 
	 * `fail_on_error_level`
	 * 
	 * : The [error level][1] at which a test of this test suite fails. The default value is set to `E_ALL`, that is as
	 *   soon as a test generates an error it fails. The reasoning behind this rather strict default value is that
	 *   even notices (`E_NOTICE` level errors) often indicate errors in the code. If this value is to strict you
	 *   can use this option to set it to a more permissive value (e.g. `E_ALL ^ E_NOTICE`).
	 * 
	 * Test suite name
	 * 
	 * : This is more of a cosmetic option but with the `name()` function you can set the name of a test suite.
	 *   The default name is the base name of the test suites file.
	 * 
	 * [1]: http://www.php.net/errorfunc.constants
	 */
	private $name;
	private $options = array(
		'base_url' => '',
		'fail_on_error_level' => E_ALL
	);
	
	/** category: Options
	 * 
	 * Sets the option `$name` of this test suite to the specified `$value`. All valid option names are listed
	 * above. If you specify the optional target envionment the option is only set if the test suite is run in this
	 * environment.
	 * 
	 * `$name`
	 * : The name of the option you want to set. See the list above.
	 * 
	 * `$value`
	 * : The value of the option.
	 * 
	 * `$target_environment`
	 * : If this parameter is specified the option will only be set if the test suite is run in the specified
	 *   environment. This way you can specify different options for e.g. a "production" environment that
	 *   tests the live system instead of localhost.
	 * 
	 * Returns
	 * : The test object itself to allow method chaining.
	 * 
	 * The value of an option can be read as a property on the test suite (returned by the `suite()` function).
	 * 
	 * 	code: /tests/examples/options
	 */
	function option($name, $value, $target_environment = null){
		if ( !$target_environment or in_array($target_environment, $this->environments) )
			$this->options[$name] = $value;
		return $this;
	}
	
	/** category: Options
	 * 
	 * Sets the name of this test suite.
	 * 
	 * `$test_suite_name`
	 * : The new name of the test suite.
	 * 
	 * Returns
	 * : The test suite itself. This allows method chaining (e.g. with the `option` method).
	 * 
	 * The name of the test suite is available through the `name` property of test suite (use the `suite()`
	 * function to get it).
	 */
	function name($test_suite_name){
		$this->name = $test_suite_name;
		return $this;
	}
	
	/**
	 * This magic function makes several things available as read-only properties:
	 * 
	 * - The test suite name
	 * - The environments it's run in
	 * - The value of all options
	 * - All fields of the last HTTP response
	 */
	function __get($property){
		if ( in_array($property, array('name', 'environments', 'report_states')) )
			return $this->$property;
		if ( preg_match('/^response_(.*)/', $property, $matches) )
			return $this->response[$matches[1]];
		if ( array_key_exists($property, $this->options) ) {
			return $this->options[$property];
		}
	}
	
	
	/** topic: Structure
	 * 
	 * The basic building blocks of a Shinpuru test suite are tests which are defined with the `test()` function.
	 * However managing a test suite with dozens of tests can get a bit difficult if you don't have means to
	 * express any structure. Therefore Shinpuru (inspired by [Shoulda][1]) provides a way to group related
	 * tests into a "context".
	 * 
	 * A context is defined with the `context()` function and can contain as many tests as you want. A context
	 * can also contain other contexts allowing a hierarchical structure. If you have some code that is common
	 * to all tests in a context you can put this code into `setup()` and `teardown()` functions. These are run
	 * before each test in the context and it's child contexts.
	 * 
	 * [1]: http://github.com/thoughtbot/shoulda
	 */
	
	// If a context is defined it's pushed on this stack and poped afterwards. The tests in this context then copy
	// this stack to remember to which contexts they belong.
	protected $context_stack = array(
		array('name' => '', 'setup' => array(), 'teardown' => array())
	);
	
	protected $tests = array();
	protected $report = array();
	
	/** category: Structure
	 * 
	 * Defines a new test.
	 * 
	 * `$description`
	 * : A small sentence to describe what the test verifies (e.g. "The front page should contain 10 articles").
	 * 
	 * `$test_function`
	 * : An anonymous function that contains the actual test code that verifies what the description says. If
	 *   it's missing the test is automatically marked as "passed".
	 * 
	 * If no test function is specified the defined test automatically calls `pass()` to mark itself as "not done
	 * yet". This is especially useful for quickly dumping out a sort of specification to get an overview of
	 * what you want the test suite to look like.
	 * 
	 * 	code: /tests/examples/tests
	 */
	function test($description, $test_function = null){
		/** topic: stacktrace
		 * Find the point in the call stack where this test function was called
		 * from the original test script (outside of this file). This information
		 * is only important if no test function was specified or no exception
		 * was thrown (the exceptions provide more detailed data).
		 */
		$callstack = debug_backtrace(false);
		foreach($callstack as $caller){
			if ( isset($caller['file']) and substr($caller['file'], 0, strlen(__FILE__)) != __FILE__)
				break;
		}
		
		$test = array(
			'description' => $description,
			'function' => $test_function ? $test_function : function(){ pass(); },
			'file' => str_replace(getcwd() . '/', '', $caller['file']),
			'line' => $caller['line']
		);
		
		// Store a reference to each enclosing context with the test. This way later modifications
		// to the context (e.g. a teardown function) are also accessible for this test.
		foreach($this->context_stack as &$context)
			$test['context'][] =& $context;
		
		array_push($this->tests, $test);
	}
	
	/** category: Structure
	 * 
	 * With a call to this function a test is marked as "not done yet" (inspired by Python). Passed tests
	 * are marked on the report so you know what's still to do.
	 * 
	 * `$message`
	 * : If you plan to leave a test unimplemented for a longer time you can state the reasons in this
	 *   message.
	 * 
	 * You can use the command line argument `--report passed` to get a detailed list of all tests that are
	 * not implemented yet. The `$message` parameter is also listed in this report.
	 */
	function pass($message = 'Passed'){
		throw new PassedException($message);
	}
	
	/** category: Structure
	 * 
	 * Defines a new context to group related tests together.
	 * 
	 * `$name`
	 * : The name of the context, e.g. "The front page", "The backend", "A post", …
	 * 
	 * `$test_definitions`
	 * : An anonymus function that defines the tests of this context. It can also contain new contexts
	 *   which are then child contexts of this one. The name of every child context is prepended with
	 *   the names of all it's parent contexts.
	 * 
	 * If all tests in a context share some common code you can put this code into a `setup()` or
	 * `teardown()` function of the context. These are run before and after each test of the context and
	 * it's child contexts.
	 * 
	 * Contexts and tests allow you to quickly write down the basic structure of a test suite. Even
	 * without any test code the test suite is already runnable. You can then implement test after test.
	 * 
	 * 	code: /tests/getting_started/full_website
	 */
	function context($name, $test_definitions){
		array_push($this->context_stack, array('name' => $name, 'setup' => array(), 'teardown' => array()));
		$test_definitions();
		array_pop($this->context_stack);
	}
	
	/** category: Structure
	 * 
	 * Registers `$setup_function` to be executed before each test of the current context. If a context
	 * contains multiple setup functions they are run in the order they were registered.
	 * 
	 * `$setup_function`
	 * : An anonymous function containing the setup code. It can contain assertions. If they fail a test
	 *   is marked as failed. Since the setup function is run before all tests in the current context all of
	 *   them will fail.
	 * 
	 * A setup function is ideal for stuff that each test of the current contest does before actually
	 * testing what it should. If you're testing a website that requires login the login code could be
	 * moved into a setup function if it's required by multiple tests.
	 * 
	 * Child context iherit the setup functions of their parent contexts. That is before any test the setup
	 * functions of all parent contexts and the current context are run.
	 * 
	 * The global scope is an context of it's own. Therefore `setup()` can also be used in the global
	 * scope and is _not_ required to always be inside of a `context()` function.
	 * 
	 * 	code: /tests/examples/setup
	 */
	function setup($setup_function){
		array_push($this->context_stack[count($this->context_stack) - 1]['setup'], $setup_function);
	}
	
	/** category: Structure
	 * 
	 * Registers `$teardown_function` to be executed after each test of the current context. If a context
	 * contains multiple teardown functions they are run in reverse order of their registration. This allows
	 * you to group related setup and teardown functions together.
	 * 
	 * The purpose of a teardown function is to clean up and free the resources a setup function allocated.
	 * Teardown functions are therefore run even if the test failed and _must not_ contain assertions (they
	 * will not be catched and PHP will report them as exceptions). If a setup function fails the teardown
	 * functions up to the context where the setup failed will be run.
	 * 
	 * `$teardown_function`
	 * : An anonymous function containing the code that should be run after the tests. It _must not_ contain
	 *   assertions but should only be concerned with cleaning up the resources the corresponding setup
	 *   function allocated.
	 * 
	 * A teardown function can also be used on the global scope (just like `setup()` can be too). Child
	 * contexts will also inherit the teardown functions of all their parent contexts.
	 * 
	 * 	code: /tests/examples/teardown
	 */
	function teardown($teardown_function){
		array_unshift($this->context_stack[count($this->context_stack) - 1]['teardown'], $teardown_function);
	}
	
	/** category: Structure
	 * 
	 * Just returns the global test suite object. This allows easy access to it's properties, e.g. the request
	 * body of the last HTTP request.
	 */
	function suite(){
		return $this;
	}
	
	/**
	 * Runs the tests defined with context() and test().
	 */
	function run_tests(){
		print('Running: ' . $this->name . "\n");
		$failed_tests = 0;
		
		/** topic: stacktrace
		 * A helper function to extract the file name and line number where the
		 * assertition was written that caused the error.
		 */
		$extract_exception_data = function($exception){
			$file = null;
			$line = null;
			foreach($exception->getTrace() as $caller){
				if ( isset($caller['line']) and isset($caller['file']) and substr($caller['file'], 0, strlen(__FILE__)) != __FILE__ ){
					$line = $caller['line'];
					$file = str_replace(getcwd() . '/', '', $caller['file']);
					break;
				}
			}
			$exception_data = array( 'error' => $exception->getMessage() );
			if ( $line and $file ){
				$exception_data['file'] = $file;
				$exception_data['line'] = $line;
			}
			return $exception_data;
		};
		
		// Handle PHP errors with the specified error reporting as failures. Also respect
		// the @ error-control operator.
		set_error_handler(function($errno, $errstr, $errfile, $errline){
			if (error_reporting() != 0)
				throw new PhpErrorException($errstr, $errfile, $errline);
		}, $this->options['fail_on_error_level']);
		
		foreach($this->tests as &$test){
			$this->call_assertion_hooks('prepare_');
			$contexts_setup = 0;
			
			try {
				foreach($test['context'] as $context){
					foreach($context['setup'] as $setup)
						$setup();
					$contexts_setup++;
				}
				
				$test['function']();
				
				$test['status'] = 'successful';
				styled_printf($test['status'], '.');
			} catch (SkippedException $exception) {
				$test['status'] = 'skipped';
				$test = array_merge($test, $extract_exception_data($exception));
				styled_printf($test['status'], 's');
			} catch (PassedException $exception) {
				$test['status'] = 'passed';
				$test = array_merge($test, $extract_exception_data($exception));
				styled_printf($test['status'], 'P');
			} catch (AssertException $exception) {
				$test['status'] = 'failed';
				$test = array_merge($test, $extract_exception_data($exception));
				$failed_tests++;
				styled_printf($test['status'], 'F');
			}
			
			// Only run the teardown functions of contexts that had no errors in their setup
			// functions.
			$setup_contexts = array_slice($test['context'], 0, $contexts_setup);
			// Run the teardown functions for each context (context in reverse order, from in
			// to out). Whithin on context the teardown functions are already in proper order.
			foreach(array_reverse($setup_contexts) as $context)
				foreach($context['teardown'] as $teardown)
					$teardown();
			
			// Clear the state variables of some assertitions so the following test
			// does not accidentially uses them
			$this->call_assertion_hooks('cleanup_');
		}
		
		print("\n");
		
		restore_error_handler();
		return $failed_tests;
	}
	
	/**
	 * Shows a report of all run test cases.
	 */
	function show_report()
	{
		$tests_by_status = group_by($this->tests, function($test){ return $test['status']; });
		
		foreach($tests_by_status as $status => $tests)
		{
			$count = count($tests);
			styled_printf($status, $count . ' ' . ($count == 1 ? 'test' : 'tests') . ' ' . $status . "\n");
			if ( in_array($status, $this->report_states) )
			{
				$tests_by_context = group_by($tests, function($test){
					return trim(join(' ', array_map(function($con){ return $con['name']; }, $test['context'])));
				});
				foreach($tests_by_context as $context => $context_tests)
				{
					if ($context)
						styled_printf($status, '  ' . $context . "\n");
					
					foreach($context_tests as $test){
						if ( isset($test['error']) and isset($test['file']) and isset($test['line']) )
							styled_printf($status, "  - %s (%s:%d): %s\n", $test['description'], $test['file'], $test['line'], $test['error']);
						else
							styled_printf($status, "  - %s\n", $test['description']);
					}
				}
			}
		}
		
		if ( !isset($tests_by_status['failed']) )
			styled_printf('successful', 'No tests failed, good job!' . "\n");
	}
	
	
	/** topic: Environments
	 * 
	 * Environments allow you to define which test (that is one `test()` function) should be run or skipped
	 * under which conditions. For example you can write a test suite that tests everything when run
	 * normally but skips several dangerous tests if run in the "production" environment (you don't want
	 * to reset your database in a production environment).
	 * 
	 * This can be achieved by calling `skip_in()` or `only_in()` directly at the start of a test.
	 * 
	 * 	code: /tests/test_dummies/environments/skip_with_one_env
	 * 
	 * If this test suite is run in the "production" environment (with the `--environment` or `-e` command line
	 * argument) the test is marked as skipped:
	 * 
	 * 	console(dir: tests): php test_dummies/environments/skip_with_one_env.php --environment production
	 * 
	 * `skip_in()` as well as `only_in()` can take an array of environments as the first parameter and a test
	 * suite can also take several environments as arguments. This way you can write very fine adjustable
	 * tests (e.g. on a feature by feature basis). 
	 */
	
	/** category: Environments
	 * 
	 * Skips everything of the test after this function. This is meant to mark some test as not
	 * relevant or unsuitable for a specific environment. For example you don't want to do destructive
	 * tests in your production environment but you can still test read only stuff.
	 * 
	 * With `skip_in()` you would mark those destructive tests with `skip_in('production')` and run the
	 * test suite on the server in the production environment (`--environment production`). If this is to
	 * dangerous you can do it the other way around and use `only_in()` to run destructive tests only
	 * in your testing environment.
	 * 
	 * Skipped tests are shown in the report so you have an impression how much was successfully
	 * tested and how much was skipped.
	 * 
	 * `$environment`
	 * : The name of the environment this test should be skipped in. It can also be an array of multiple
	 *   environment names, as long as one of them matches an environment the test suite is run in the
	 *   test is skipped (logical or).
	 * 
	 * `$reason`
	 * : An optional reason why the test was skipped.
	 * 
	 * If you run the test suite with the command line argument `--report skipped` all skipped tests are
	 * listed with their corresponding reasons.
	 * 
	 * 	code: /tests/test_dummies/environments/skip_with_one_env #test:name(rebuild the database)
	 * 	code: /tests/test_dummies/environments/skip_with_multiple_envs #test:name(rebuild the database)
	 * 	code: /tests/test_dummies/environments/skip_with_message #test:name(rebuild the database)
	 */
	function skip_in($environment, $reason = 'Skipped'){
		if ( (is_array($environment) and count(array_intersect($this->environments, $environment)) > 0 ) or in_array($environment, $this->environments) )
			throw new SkippedException($reason);
	}
	
	/** category: Environments
	 * 
	 * This is the negative `skip_in()`, that is it only _continues_ if the test suite is run in the specified
	 * environment. It also accepts an array with multiple environment names as the first parameter.
	 * 
	 * `$environment`
	 * : The name of the environment this test should be run in. Can also be an array of names, as long
	 *   as one of them matches the current environment the test is run.
	 * 
	 * `$reason`
	 * : An optional reason in case the test is skipped.
	 * 
	 * If you run the test suite with the command line argument `--report skipped` all skipped tests are
	 * listed with their corresponding reasons.
	 * 
	 * 	code: /tests/test_dummies/environments/only_with_one_env #test:name(test dead links)
	 * 	code: /tests/test_dummies/environments/only_with_multiple_envs #test:name(test dead links)
	 * 	code: /tests/test_dummies/environments/only_with_message #test:name(test dead links)
	 */
	function only_in($environment, $reason = 'Skipped'){
		if ( !( (is_array($environment) and count(array_intersect($this->environments, $environment)) > 0 ) or in_array($environment, $this->environments) ) )
			throw new SkippedException($reason);
	}
	
	
	/** topic: Basic assertions
	 * 
	 * With the following assertions you can cover the functionality of basic PHP code:
	 * 
	 * - Verify equality of a result and an expected value (special assertions for `true`, `false`, `null`
	 *   and not `null`).
	 * - Verify identity or that something is not identical to a forbidden value.
	 * - Verify that something is (not) empty.
	 * - Verify array counts or contents.
	 * - Last but not least verify that something matches an regular expression pattern.
	 * 
	 * You can also use PHPs build in `assert()` but note that you can not specify a custom error message
	 * for it. In that case you need to use `assert_true()`.
	 */
	
	/** category: Basic assertions
	 * 
	 * Verifies that `$value` equals `$expected_value`. For that the usual PHP equality operator is used
	 * (==).
	 */
	function assert_equal($value, $expected_value, $message = 'Got value %s but expected %s'){
		if ($value != $expected_value)
			throw new AssertException(sprintf($message, nice_val($value), nice_val($expected_value)));
	}
	
	/** category: Basic assertions
	 * 
	 * Verifies that `$value` is not equal to `$forbidden_value`. For that the usual PHP equality operator
	 * is used (==).
	 */
	function assert_not_equal($value, $forbidden_value, $message = 'Got %s with is equal to the forbidden value of %s'){
		if ($value == $forbidden_value)
			throw new AssertException(sprintf($message, nice_val($value), nice_val($forbidden_value)));
	}
	
	/** category: Basic assertions
	 * 
	 * Verifies that `$result` is `true`.
	 * 
	 * This assertion is more or less like PHPs build in `assert()` but supports an optional message in case
	 * the assertion fails.
	 */
	function assert_true($result, $message = 'Expected true but got %s'){
		if ($result != true)
			throw new AssertException(sprintf($message, nice_val($result)));
	}
	
	/** category: Basic assertions
	 * 
	 * Verifies that `$result` is equal to `false`.
	 * 
	 * Note that in PHP several values are equal to `false`, e.g. `0` and `""` (an empty string). If you want
	 * to verify that something is identical to `false` use `assert_identical()`.
	 */
	function assert_false($result, $message = 'Expected false but got %s'){
		if ($result != false)
			throw new AssertException(sprintf($message, nice_val($result)));
	}
	
	/** category: Basic assertions
	 * 
	 * Verifies that `$result` is identical to `null`.
	 */
	function assert_null($result, $message = 'Expected null but got %s'){
		if ( !is_null($result) )
			throw new AssertException(sprintf($message, nice_val($result)));
	}
	
	/** category: Basic assertions
	 * 
	 * Verifies that `$result` is not `null`.
	 */
	function assert_not_null($result, $message = 'Expected something else than null but got %s'){
		if ( is_null($result) )
			throw new AssertException(sprintf($message, nice_val($result)));
	}
	
	/** category: Basic assertions
	 * 
	 * This is `assert_equals()` bigger sister (bigger sisters are always strict, you know… no offense
	 * intended). Anyway, `assert_identical()` verifies that `$value` is identical to `$expected_value`
	 * as with PHPs `===` operator. That is both parameters are expected to have the same value
	 * and type.
	 */
	function assert_identical($value, $expected_value, $message = 'Got value %s but expected something identical to %s'){
		if ($value !== $expected_value)
			throw new AssertException(sprintf($message, nice_val($value), nice_val($expected_value)));
	}
	
	/** category: Basic assertions
	 * 
	 * Verifies that `$value` is not identical to `$forbidden_value`.
	 */
	function assert_not_identical($value, $forbidden_value, $message = 'Got the forbidden value %s'){
		if ($value === $forbidden_value)
			throw new AssertException(sprintf($message, nice_val($value), nice_val($forbidden_value)));
	}
	
	/** category: Basic assertions
	 *
	 * Verifies that the specified parameter is empty. That is `empty($value)` returns `true`.
	 */
	function assert_empty($value, $message = 'Expected something empty but got %s'){
		if ( !empty($value) )
			throw new AssertException(sprintf($message, nice_val($value)));
	}
	
	/** category: Basic assertions
	 *
	 * Verifies that the specified parameter is _not_ empty. That is `empty($value)` returns `false`.
	 */
	function assert_not_empty($value, $message = 'Exptected something that is not empty but got %s'){
		if ( empty($value) )
			throw new AssertException(sprintf($message, nice_val($value)));
	}
	
	/** category: Basic assertions
	 * 
	 * Verifies the count of `$something_countable` to be qual to `$expteced_count`.
	 */
	function assert_count($something_countable, $expteced_count, $message = 'Got a count of %s but expected %s'){
		$count = count($something_countable);
		if ( $count != $expteced_count )
			throw new AssertException(sprintf($message, nice_val($count), nice_val($expteced_count)));
	}
	
	/**
	 * The real test behind `assert_contains()` and `assert_not_contains()`. Because the logic of these
	 * two assertions is almost identical it's put into this common function that is used by both.
	 */
	private function evaluate_assert_contains($haystack, $needle){
		if ( is_string($haystack) ) {
			return strpos($haystack, $needle);
		} elseif ( is_array($haystack) ) {
			return in_array($needle, $haystack);
		} else {
			throw new AssertException(sprintf('Got %s when expecting a haystack to search in (string or array)', nice_val($haystack)));
		}
	}
	
	/** category: Basic assertions
	 * 
	 * Verifies that `$haystack` contains `$needle`. If `$haystack` is a string `strpos()` will be used, if
	 * it's an array `in_array()` will be used for the check.
	 */
	function assert_contains($haystack, $needle, $message = 'Could not find %s in %s'){
		if ( $this->evaluate_assert_contains($haystack, $needle) === false )
			throw new AssertException(sprintf($message, nice_val($needle), nice_val($haystack)));
	}
	
	/** category: Basic assertions
	 * 
	 * The exact oposit of `assert_contains()`. Fails if the `$needle` is part of `$haystack`.
	 */
	function assert_not_contains($haystack, $needle, $message = '%s was not supposed to be in %s'){
		if ( $this->evaluate_assert_contains($haystack, $needle) !== false )
			throw new AssertException(sprintf($message, nice_val($needle), nice_val($haystack)));
	}
	
	/** category: Basic assertions
	 * 
	 * Verifies that the regular expression `$pattern` can be found in `$haystack`. `preg_match()`
	 * is used to do the job.
	 */
	function assert_regex($pattern, $haystack, $message = '%s does not match %s'){
		if ( preg_match($pattern, $haystack) == 0 )
			throw new AssertException(sprintf($message, nice_val($pattern), nice_val($haystack)));
	}
	
	
	/** topic: HTTP request functions
	 * 
	 * These functions make it easy to perform HTTP requests. PHPs `file_get_contents()` function is used
	 * so you have the full power of PHPs streams at you disposal. You can modify the behavior of every request
	 * function by specifying [context options][1] which allow you to add you own headers (e.g. for authorization),
	 * change the user agent, set SSL options, etc. In fact `post()`, `put()` and `delete()` are nothing more
	 * than calls to `get()` with some context options set.
	 * 
	 * 
	 * 
	 * [1]: http://php.net/context
	 */
	protected $empty_response = array(
		'status' => null,
		'status_phrase' => null,
		'headers' => array(),
		'body' => null
	);
	protected $response = null;
	
	/**
	 * Start the first test with an empty response.
	 */
	protected function init_http_request_functions(){
		$this->response = $this->empty_response;
	}
	
	/**
	 * And reset the response to empty after each test.
	 */
	protected function cleanup_http_request_functions(){
		$this->response = $this->empty_response;
	}
	
	/** category: HTTP request functions
	 * 
	 * Performes an HTTP `GET` request on the specified `$url` and returns the response body. If the `base_url`
	 * option is set it's value will be prepended to the URL. `get()` will fail if the HTTP request was not
	 * successful (e.g. DNS lookup failed or an 404 error code was returned). If you want to ignore HTTP error
	 * codes you can set the [`ignore_errors` HTTP context option][1]. After the request the [HTTP assertions][2]
	 * can then be used to verify several aspects of the HTTP response (response code, content).
	 * 
	 * `$url`
	 * : The URL to request, e.g. `http://arkanis.de/projects`. If the `base_url` option is set it's value will be
	 *   prepended to the URL. For example you can set the `base_url` option to `http://arkanis.de` and then
	 *   request the URL `/projects`. Together this will request `http://arkanis.de/projects`, too.
	 *   
	 *   You can specify any URL PHPs `file_get_contents()` function can handle, e.g. FTP or HTTPS will work,
	 *   too.
	 * 
	 * `$context_options`
	 * : An array containing [context options][3] that are given to `stream_context_create()`. This allows you
	 *   customize the request (e.g. setting additional headers, the request method, the user agent string, SSL
	 *   behaviour, …). Especially the [HTTP context options][4] are worth a look.
	 * 
	 * Returns
	 * : If the request was successful the response body is returned as a string. Otherwise `false` is returned.
	 *   The details of the response are available as properties though the test suite:
	 *   
	 *   - `suite()->response_body` contains the response body as a string
	 *   - `suite()->response_status` is the HTTP status code (e.g. 200 or 404)
	 *   - `suite()->response_status_phrase` contains the HTTP status phrase (e.g. "OK" or "Not Found")
	 *   - `suite()->response_headers` is an array of HTTP headers with the header names as keys and their
	 *      content as values
	 * 
	 * [1]: http://php.net/context.http#context.http.ignore-errors
	 * [2]: #http-assertions
	 * [3]: http://php.net/context
	 * [4]: http://php.net/context.http
	 */
	function get($url, $context_options = array()){
		$context = stream_context_create($context_options);
		$this->response['body'] = file_get_contents($this->options['base_url'] . $url, false, $context);
		
		if (isset($http_response_header)) {
			// Search for the last HTTP status code line in case there have been multiple requests (e.g. redirect)
			$headers_start_line = 0;
			foreach($http_response_header as $line => $header){
				if (substr($header, 0, 4) == 'HTTP')
					$headers_start_line = $line;
			}
			$headers = array_slice($http_response_header, $headers_start_line);
			
			// Parse the status line and headers
			$status_line = array_shift($headers);
			list(, $code, $reason_phrase) = explode(' ', $status_line, 3);
			$this->response['status'] = $code;
			$this->response['status_phrase'] = $reason_phrase;
			$this->response['headers'] = array();
			foreach($headers as $header){
				list($name, $value) = explode(':', $header, 2);
				$this->response['headers'][trim($name)] = trim($value);
			}
		} else {
			$this->response['status'] = null;
			$this->response['status_phrase'] = null;
			$this->response['headers'] = array();
		}
		
		$this->call_assertion_hooks('update_http_response_');
		
		return $this->response['body'];
	}
	
	/** category: HTTP request functions
	 * 
	 * Sends an HTTP `POST` request with the specified `$data` to the `$url`. It's basically the same
	 * as `get()` but with some context options set to make it a `POST` request.
	 * 
	 * `$url`
	 * : The URL to send the `POST` request to. If the `base_url` option is set it's value will be
	 *   prepended to the URL.
	 * 
	 * `$data`
	 * : An associative array containing the data to send with the request. [`http_build_query()`][1] is used
	 *   to encode the data which is then send as the request content.
	 * 
	 * `$context_options`
	 * : Additional context options for the request. See `get()`.
	 * 
	 * Returns
	 * : If the request is successful the response body is returned, otherwise `false`. More details of the
	 *   response can be accessed as properties of the test suite, see `get()`.
	 * 
	 * [1]: http://php.net/http_build_query
	 */
	function post($url, $data = array(), $context_options = array()){
		$context_options = array_replace_recursive(array(
			'http' => array(
				'method' => 'POST',
				'header' => 'Content-Type: application/x-www-form-urlencoded',
				'content' => http_build_query($data)
			)
		), $context_options);
		return get($url, $context_options);
	}
	
	/** category: HTTP request functions
	 * 
	 * Sends an HTTP `PUT` request with the specified `$data` to the `$url`. It's basically the same
	 * as `get()` but with some context options set to make it a `PUT` request.
	 * 
	 * `$url`
	 * : The URL to send the `PUT` request to. If the `base_url` option is set it's value will be
	 *   prepended to the URL.
	 * 
	 * `$data`
	 * : An associative array containing the data to send with the request. [`http_build_query()`][1] is used
	 *   to encode the data which is then send as the request content.
	 * 
	 * `$context_options`
	 * : Additional context options for the request. See `get()`.
	 * 
	 * Returns
	 * : If the request is successful the response body is returned, otherwise `false`. More details of the
	 *   response can be accessed as properties of the test suite, see `get()`.
	 * 
	 * [1]: http://php.net/http_build_query
	 */
	function put($url, $data = array(), $context_options = array()){
		$context_options = array_replace_recursive(array(
			'http' => array(
				'method' => 'PUT',
				'header' => 'Content-Type: application/x-www-form-urlencoded',
				'content' => http_build_query($data)
			)
		), $context_options);
		return get($url, $context_options);
	}
	
	/** category: HTTP request functions
	 * 
	 * Sends an HTTP `DELETE` request with the specified `$data` to the `$url`. It's basically the same
	 * as `get()` but with some context options set to make it a `DELETE` request.
	 * 
	 * `$url`
	 * : The URL to send the `DELETE` request to. If the `base_url` option is set it's value will be
	 *   prepended to the URL.
	 * 
	 * `$data`
	 * : An associative array containing the data to send with the request. [`http_build_query()`][1] is used
	 *   to encode the data which is then send as the request content.
	 * 
	 * `$context_options`
	 * : Additional context options for the request. See `get()`.
	 * 
	 * Returns
	 * : If the request is successful the response body is returned, otherwise `false`. More details of the
	 *   response can be accessed as properties of the test suite, see `get()`.
	 * 
	 * [1]: http://php.net/http_build_query
	 */
	function delete($url, $context_options = array()){
		$context_options = array_replace_recursive(array(
			'http' => array(
				'method' => 'DELETE',
				'header' => 'Content-Type: application/x-www-form-urlencoded'
			)
		), $context_options);
		return get($url, $context_options);
	}
	
	
	/** topic: HTTP assertions
	 * 
	 * These assertions can be used to verify several aspects of the last HTTP request. You can check the
	 * HTTP response code or phrase with `assert_response()` and check the content of the request body
	 * with `assert_select()` and `assert_xpath()`.
	 * 
	 * Additional to the HTTP assertions you can also get the details of the last HTTP response and verify
	 * them with the other assertions:
	 * 
	 * - `suite()->response_body` contains the response body as a string
	 * - `suite()->response_status` is the HTTP status code (e.g. 200 or 404)
	 * - `suite()->response_status_phrase` contains the HTTP status phrase (e.g. "OK" or "Not Found")
	 * - `suite()->response_headers` is an array of HTTP headers with the header names as keys and their
	 *   content as values
	 */
	
	/** category: HTTP assertions
	 * 
	 * Verifies the HTTP resonse status code or phrase. If `$status` is an integer it will verify the HTTP status
	 * code (e.g. `200` or `404`). If it's a string it verifies the response status phrase. Note that these status
	 * phrases are not standardized and depend on the webserver.
	 */
	function assert_response($status, $message = 'Got HTTP status code %s but expected %s'){
		if ( is_int($status) ) {
			if ($status != $this->response['status'])
				throw new AssertException(sprintf($message, nice_val($this->response['status'] . ' (' . $this->response['status_phrase'] . ')'), nice_val($status)));
		} else {
			if ($status != $this->response['status_phrase'])
				throw new AssertException(sprintf($message, nice_val($this->response['status_phrase'] . ' (' . $this->response['status'] . ')'), nice_val($status)));
		}
	}
	
	
	/**
	 * Variables for HTML structure assertitions
	 */
	protected $xpath = null;
	protected $xpath_context = null;
	
	protected function cleanup_html_assertitions(){
		$this->xpath = null;
		$this->xpath_context_element = null;
	}
	
	protected function update_http_response_xpath(){
		$this->cleanup_html_assertitions();
	}
	
	/** category: HTTP assertions
	 * 
	 * Evaluates the specified XPath `$expression` against the latest response body and compares it
	 * to an `$expected` value. If the expression yields a list of elements you can loop over these by
	 * specifying a function as `$expected` value and check every one in detail.
	 * 
	 * For simple standard stuff you should check out `assert_select()`. It converts simple CSS
	 * selectors to XPath and then calls `assert_xpath()`.
	 * 
	 * When you nest `assert_xpath()` calls they are automatically scoped to the current element.
	 * 
	 * You can find further information in the [DOMXPath evaluate()][1] method, the [DOMElement][2]
	 * class and the [XPath specification][3] (the [core function library][4] is very handy).
	 * 
	 * Some handy starting points for XPath:
	 * 
	 * - `id("some-id")` gets the element with the ID `some-id`
	 * - `string(/some/where/p)` to get the content of all child text nodes (concatenated) as one string
	 * - `count(//p)` to get the count of all paragraphs
	 * 
	 * [1]: http://php.net/domxpath.evaluate
	 * [2]: http://php.net/domelement
	 * [3]: http://www.w3.org/TR/xpath/
	 * [4]: http://www.w3.org/TR/xpath/#corelib
	 */
	function assert_xpath($expression, $expected = true, $message = "Got %s but expected %s on XPath test '%s'"){
		if ($this->xpath == null){
			$doc = new DOMDocument();
			if ( !@$doc->loadHTML($this->response['body']) )
				throw new AssertException('Could not parse the response as HTML to search its contents');
			$this->xpath = new DomXPath($doc);
			$this->xpath_context = $doc->documentElement;
		}
		
		$result = $this->xpath->evaluate($expression, $this->xpath_context);
		if ($result instanceof DOMNodeList) {
			if ( is_callable($expected) ) {
				$parent_context = $this->xpath_context;
				foreach($result as $element){
					$this->xpath_context = $element;
					$expected($element);
				}
				$this->xpath_context = $parent_context;
			} else {
				throw new AssertException(sprintf($message, nice_val('a list of elements'), nice_val($expected), $expression));
			}
		} else {
			if ($result != $expected)
				throw new AssertException(sprintf($message, nice_val($result), nice_val($expected), $expression));
		}
	}
	
	/** category: HTTP assertions
	 * 
	 * With `assert_select()` you can use simple CSS selectors to check the structure of the latest HTTP response
	 * body. It's sort of the little brother of `assert_xpath()` since all CSS selectors are transformed into XPath
	 * expressions and then handed of to `assert_xpath()`. Depending on the `$test` parameter you can verify
	 * different aspects (e.g. count of element, text content of an element, …).
	 * 
	 * `$simple_css_selector`
	 * : A simple CSS selector that will be translated into an XPath expression.
	 *   
	 *   The mapping looks like this:
	 *   
	 *   - `elem`		→	`//elem`
	 *   - `elem#idxyz`	→	`//elem[@id='idxyz']`
	 *   - `#idxyz`		→	`//*[@id='idxyz']`
	 *   - `elem.classxyz`	→	`//elem[contains(concat(' ', normalize-space(@class), ' '), ' classxyz ')]`
	 *   - `.classxyz`	→	`//*[contains(concat(' ', normalize-space(@class), ' '), ' classxyz ')]`
	 *   - `selector, selector`	→	`xpath | xpath`
	 *   - `> piece`			→	`/ insead of //`
	 * 
	 * `$test`
	 * : Depending on the type of this parameter `assert_select()` performs different tests.
	 *   
	 *   - If it's not speified or set to the default value of `null` the expression have to yield at least
	 *     one element: `count(…) > 0`.
	 *   - In case it's an integer the number of found elements have to match the specified number:
	 *     `count(…) = $test`.
	 *   - If it's a string it have to equal the string value of the found node and all of it's child nodes:
	 *     `string(…) = '$test'`.
	 *   - If it's a callback it will be called for each found element as an iterator.
	 * 
	 * `$message`
	 * : An optional message reported when the assertion fails.
	 * 
	 * The CSS class to XPath expression was taken from [Parsing XML documents with CSS selectors][1], so
	 * all credit for it belongs to Fabien Potencier.
	 * 
	 * [1]: http://fabien.potencier.org/article/42/parsing-xml-documents-with-css-selectors
	 */
	function assert_select($simple_css_selector, $test = null, $message = "Got %s but expected %s on CSS selector test '%s'"){
		$sub_selectors = explode(',', $simple_css_selector);
		$sub_xpath_expressions = array();
		
		foreach($sub_selectors as $selector){
			$pieces = preg_split('/\s+/', trim($selector));
			$sub_xpath_expression = '.';
			
			$recursive = true;
			foreach($pieces as $piece){
				$prefix = $recursive ? '//' : '/';
				if ( preg_match('/(\w+)?#(\w+)/', $piece, $matches) ) {
					$tag_name = $matches[1] ? $matches[1] : '*';
					$id = $matches[2];
					$sub_xpath_expression .= $prefix . $tag_name . "[@id='$id']";
					$recursive = true;
				} else if ( preg_match('/(\w+)?\.(\w+)/', $piece, $matches) ) {
					$tag_name = $matches[1] ? $matches[1] : '*';
					$class = $matches[2];
					$sub_xpath_expression .= $prefix . $tag_name . "[contains(concat(' ', normalize-space(@class), ' '), ' $class ')]";
					$recursive = true;
				} else if ( preg_match('/\w+/', $piece, $matches) ) {
					$sub_xpath_expression .= $prefix . $matches[0];
					$recursive = true;
				} else if ( $piece == '>') {
					$recursive = false;
				} else {
					throw new AssertException('Unsupported piece ' . nice_val($piece) . ' found in CSS selector ' . nice_val($simple_css_selector));
				}
			}
			
			$sub_xpath_expressions[] = $sub_xpath_expression;
		}
		$xpath_expression = join(' | ', $sub_xpath_expressions);
		
		if ($test == null) {
			// "count(expr) > 0"
			return assert_xpath('count(' . $xpath_expression . ') > 0', true, $message);
		} else if ( is_int($test) ) {
			// "count(expr) = expected"
			return assert_xpath('count(' . $xpath_expression . ')', $test, $message);
		} else if ( is_string($test) ) {
			// "string(expr) = 'expected'"
			return assert_xpath('string(' . $xpath_expression . ')', $test, $message);
		} else if ( is_callable($test) ) {
			return assert_xpath($xpath_expression, $test, $message);
		} else {
			throw new AssertException('Unsupported test argument ' . nice_val($test) . ' for assert_select()');
		}
	}
	
	
	/** topic: Flow control assertions
	 * 
	 * These assertions allow you to verify some aspects of your programs control flow like checkponts and exception
	 * handling.
	 */
	protected $reached_checkpoints = 0;
	
	/** category: Flow control assertions
	 * 
	 * Verifies that within the specified function `$expected_count` checkpoints are
	 * reached. That is `checkpoint()` is called `$expected_count` times.
	 */
	function assert_checkpoints($expected_count, $function_or_message, $function = null){
		if ( is_callable($function) ) {
			$message = $function_or_message;
		} else {
			$message = 'Only reached %s of %s checkpoints';
			$function = $function_or_message;
		}
		
		$this->reached_checkpoints = 0;
		$function();
		if ($this->reached_checkpoints != $expected_count)
			throw new AssertException(sprintf($message, nice_val($this->reached_checkpoints), nice_val($expected_count)));
	}
	
	/** category: Flow control assertions
	 * 
	 * Marks one checkpoint as reached within a `assert_checkpoints()` function.
	 */
	function checkpoint(){
		$this->reached_checkpoints++;
	}
	
	/** category: Flow control assertions
	 * 
	 * Checks that the enclosed code contains a failing assertition.
	 */
	function assert_fails($message_or_function, $function = null){
		$args = func_get_args();
		$function = array_pop($args);
		$message = (count($args) > 0) ? array_pop($args) : 'The enclosed code did not fail but was expected to';
		
		try {
			$function();
		} catch(Exception $exception) {
			return true;
		}
		
		throw new AssertException($message);
	}
	
	/** category: Flow control assertions
	 * 
	 * Verifies that the enclosed code fails with the specified exception message.
	 * 
	 * Note that this assertion strips all console control codes from the error message before
	 * comparing it with the expected message. This way the console formating does not get
	 * in our way.
	 */
	function assert_fails_with($expected_exception_message, $message_or_function, $function = null){
		$args = func_get_args();
		$function = array_pop($args);
		$message = (count($args) > 1) ? array_pop($args) : 'The enclosed code failed with %s but should have failed with %s';
		
		try {
			$function();
		} catch(Exception $exception) {
			$exception_message = $exception->getMessage();
			// Strip console control codes from the exception message
			$exception_message = preg_replace('/\033\[[^m]+m/', '', $exception_message);
			if ( $exception_message != $expected_exception_message )
				throw new AssertException(sprintf($message, nice_val($exception_message), nice_val($expected_exception_message)));
			else
				return true;
		}
		
		throw new AssertException(sprintf('The enclosed code did not fail at all but was expected to fail with %s', nice_val($expected_exception_message)));
	}
	
	
	/**
	 * A generic grouping function much like the one of Ruby and other languages.
	 */
	static function group_by($array, $element_to_group)
	{
		$grouped_array = array();
		
		foreach($array as $element){
			$group = $element_to_group($element);
			if ( isset($grouped_array[$group]) )
				$grouped_array[$group][] = $element;
			else
				$grouped_array[$group] = array($element);
		}
		
		return $grouped_array;
	}
	
	/**
	 * A `printf()` like function that surrounds the output with string configured by
	 * the style (usually special codes turning console color on and off). It can be used
	 * like `printf()` but requires the name of the desired style as first argument, see
	 * the `$styles` array.
	 * 
	 * 	styled_printf('skipped', 'Hello %s!', 'world');  // => "\033[34mHello world!\033[0m"
	 */
	static function styled_printf($style, $format){
		$args = array_slice(func_get_args(), 2);
		if ( !isset(self::$styles[$style]) )
			$style = 'unknown';
		return vprintf(self::$styles[$style]['before'] . $format . self::$styles[$style]['after'], $args);
	}
	
	/**
	 * Use this function to highlight values in assertition messages. It returns a readable
	 * representation of the value and also adds codes to print the value with a bold font
	 * on the console. This helps to faster scan the output for important values.
	 * 
	 * 	nice_val(true);  // => "\033[1mtrue\033[22m" or just "true" in bold
	 * 	nice_val(2);  // => "\033[1m2\033[22m" or just "2" in bold
	 * 	nice_val('output');  // => "\033[1moutput\033[22m" or just "output" in bold
	 */
	static function nice_val($value){
		if ($value === true or $value === false or $value === null or is_array($value) or $value === '')
			$value = var_export($value, true);
		return self::$styles['highlight']['before'] . $value . self::$styles['highlight']['after'];
	}
	
}

$shinpuru_test_suite = new ShinpuruTestSuite($argv);

?>