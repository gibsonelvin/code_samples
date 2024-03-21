<?php
ini_set('memory_limit', '512M');
date_default_timezone_set('America/Chicago');

function testDirectory($folder) {
	global $tests;

	print "\n\n\nLooking for tests in: $folder\n";
	$tests = array_merge($tests, getTestsFromFolder($folder));
	executeTests();
}

function addTest($test) {
	global $tests;

	$tests[] = $test;
	executeTests();
}

function getTestsFromFolder($folder) {
	$folder_tests = [];
	$dh = opendir($folder);
	while($file = readdir($dh)) {

print "\n\nTest count: " . count($$folder_tests) . "\n\n";

		$base_path = $folder;
		$full_path = "$base_path/$file";

		if(in_array($file, ['.', '..'])) {
			print "\nBuilt ins, continuing...";
			continue;

		} elseif(is_dir($full_path)) {
			print "\nRecursively exploring: $full_path";
			$folder_tests = array_merge($folder_tests, getTestsFromFolder($full_path));

		} elseif(substr($file, -2)==".t") {
			print "\nAdded test: $full_path";
			$folder_tests[] = $full_path;
		} else {
			print "\nIgnoring unknown file: $file";
			continue;
		}
	}

	closedir($dh);
	return $folder_tests;
}

function executeTests() {
	global $batch_size, $batch_cnt, $tests;
	while($batch_cnt < $batch_size && !empty($tests)) {
		executeNext();
	}
}

function executeNext() {
	global $tests, $batch_cnt, $running_tests;

	// If there's another test to execute, executes it
	if(!empty($tests)) {
		$test = array_shift($tests);

		$batch_cnt++;

		$root_dir = '/home/egibson/';
		$test_output_file = getTestOutputFileName($test);
		$running_tests[$test] = [
			'time' => time(),
			'output_file' => $test_output_file,
			'process_id' => 0
		];
		$pretty_time = date('Y-m-d H:i:s', $running_tests[$test]['time']);
		
		print "\n-------------------------------------------------------------\n"
			. "Test execution started at $pretty_time for: $test...\nWaiting...\n";
		
		$command = $root_dir . "perl5/perlbrew/perls/perl-5.16.3/bin/prove -vrI lib $test > $test_output_file & echo $!";
		$running_tests[$test]['process_id'] = exec($command, $response, $result);

		print "\nProcess ID: " . $running_tests[$test]['process_id'] . "\n";
	}

}

function completeTest($test) {
	global $running_tests;

	$pass = didTestPass($test);
	
	$run_time = time() - $running_tests[$test]['time'];
	print "\n***Test (PID: " . $running_tests[$test]['process_id'] . ", Test: $test\texecution completed in $run_time seconds.***\n\n";

	addTestRun($test, $run_time, ($pass ? '1': '0'), null);
}

function expireTestExecution($test) {
	global $running_tests;

	print "\n***Test (PID: " . $running_tests[$test]['process_id'] . ", Test: $test\tfailed to complete in allotted time.***\n\n";
	terminateTest($test, 'Exceeded maximum time threshold.');

}

function terminateTest($test, $msg) {
	global $running_tests;

	$run_time = intval(time()) - $running_tests[$test]['time'];

	print "\nTerminating Test '$test' after $run_time seconds.";

	exec('kill ' . $running_tests[$test]['process_id'], $response, $result);

	if($result==0 || preg_match('@No\ssuch\sprocess@', $response)) {
		addTestRun($test, $run_time, '0', 'TERMINATED: ' . $msg);
	} else {
		die("\nFATAL ERROR: UNABLE TO TERMINATE TEST!!!\nTest: $test\nRuntime: $run_time\n\n\n");
	}

}

function addTestRun($test, $time, $pass, $note = null) {
	global $running_tests, $test_runs, $batch_cnt;

	$batch_cnt--;
	unset($running_tests[$test]);
	$test_runs[$test] = [
		'run_time' => $time,
		'pass' => $pass,
		'note' => $note
	];

	$output_line = "\n$test,$time,$pass,$note";
	file_put_contents('/home/egibson/projects/test_manager_output/output.csv', $output_line, FILE_APPEND);
	executeNext();
}

function didTestPass($test) {
	$output_file = getTestOutputFileName($test);
	$test_output = file_get_contents($output_file);
	return !preg_match('@Result:\sFAIL@', $test_output);
}

function getTestOutputFileName($test) {
	$file_parts = explode("/", $test);
	// Removes '/home/egibson/projects' from the path, and returns unique test path parts
	$unique_test_path_parts = array_splice($file_parts, 4, -1);
	$test_output_path_parts = ['home', 'egibson', 'projects', 'test_manager_output'];

	$folder_path_parts = array_merge($test_output_path_parts, $unique_test_path_parts);
	$folder_path = '/' . join('/', $folder_path_parts);
	makeFolderPath('/' . join('/', $test_output_path_parts), $unique_test_path_parts);

	$file = array_pop($file_parts);
	return $folder_path . '/' . substr($file, 0, -2) . '_OUTPUT.log';
}

// Should have a good starting point, and then the folder list it wants to create from there
function makeFolderPath($start, $potentially_new_folders) {
	$current_dir = $start;
	foreach($potentially_new_folders as $potential) {
		$current_dir .= '/' . $potential;
		// print "\n\nCURRENT DIRECTORY: $current_dir\n\n";
		if(!is_dir($current_dir)) {
			exec('mkdir ' . $current_dir, $res, $status);
			if($status==0) {
				continue;
			} else {
				die("\n\nUnable to create path for test output, please fix!\nPath:$current_dir");
			}
		} else {
			continue;
		}
	}
}

function monitor() {
	global $running_tests;

	while(1==1) {

		checkComms();
		checkAllTests();
		checkTimes();

		if(empty($running_tests)) {
			dumpTestRuns();
		}

		sleep(1);
	}
}

function checkComms() {
	print "\n\nChecking Communications...";

	$comm_file = '/home/egibson/projects/test_manager_comms.txt';

	$comms = file($comm_file);
	foreach($comms as $comm) {

		print "\n\n--------------------------------------Communication-received:-------------------------------------"
			. "\n$comm\n\n";

		if(preg_match('@(execute|run)\stest\s(?P<test>[A-z0-9._\-/]+)@i', $comm, $matches)) {
			addTest($matches['test']);

		} else if(preg_match('@(halt|stop)\s(execution|test)\s(?P<test>[A-z0-9._\-/]+)@i', $comm, $matches)) {
			terminateTest($matches['test'], 'received communication.');

		} else if(preg_match('@(execute|test|run)\s(directory|folder)\s(?P<directory>[A-z0-9._\-/]+)@i', $comm, $matches)) {
			testDirectory($matches['directory']);

		} else if(preg_match('@(dump|display|show|output|get)\stest\s(executions|runs)@i', $comm)) {
			dumpTestRuns();

		} else {
			print "\n\n\nUnrecognized Communication: $comm\nExiting...";
			exit;
		}
	}

	// Clears out the file since communications have been received
	file_put_contents($comm_file, '');
}

function checkAllTests() {
	global $running_tests;
	print "\nChecking the status of all running tests...";

	foreach($running_tests as $test => $process_info) {
		checkTestStatus($test, $process_info['process_id']);
	}
}

// To help find tests that have silently failed, and who's process ID has been replaced:
function checkTestStatus($test, $process_id) {
	global $running_tests;

	$running = 0;

	if(isset($running_tests[$test])) {
		print "\n----------------------------------------------\nChecking Test status for: $test";
		exec('ps ' . $process_id, $response, $result);

		foreach($response as $status_line) {
			list($pid, , $stat, $time, $command) = preg_split("@\s+@", $status_line);
			if($pid==$process_id) {
				print "\nProcess:\t$pid\tTime:\t$time\tStatus:\t$stat";
				$running = 1;
			}
		}

		if(!$running) {
			completeTest($test);
		}
	}
}

function checkTimes() {
	global $running_tests;

	print "\nChecking the run time for all running tests.";

	foreach($running_tests as $test => $start) {
		if($start['time']+180 < time()) {
			expireTestExecution($test);
		}
	}
}

// Returns ran tests from this session
function dumpTestRuns() {
	global $test_runs;
	if(!empty($test_runs)) {
		print_r($test_runs);

		$formatted_output = json_encode($test_runs);
		file_put_contents('/home/egibson/projects/test_manager_output/output.json', $formatted_output);
		print "\nTest runs dumped to: '/home/egibson/projects/test_manager_output/output.json'\n\n";

		sleep(5);
	} else {
		print "\nNo tests to dump...\n";
	}
}


$tests = [];
$running_tests = [];
$test_runs = [];

$batch_cnt = 0;
$batch_size = 3;

// Run with command:
// test folder /home/egibson/projects/gator-bill-cron/t
// output test runs
// execute test /home/egibson/projects/gator-bill-cron/t/crons/addon/constantcontact/01-create.t
// stop test /home/egibson/projects/gator-bill-cron/t/crons/addon/constantcontact/01-create.t

// $test_dir = '/home/egibson/projects/gator-bill-cron/t';
// testDirectory($test_dir);
monitor();
