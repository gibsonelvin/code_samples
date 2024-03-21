<?php

$fh = fopen("php://stdin", "r");
$task = ask('Greetings! How can I help?');
processTask($task);

/**
 * Asks the user a question, and returns response
 * @param $question
 * @return string
 **/
function ask($question) {
	global $fh;
	print "\nComputer: $question\nUser: ";
	return fgets($fh);
}

/**
 * Adds task, or exits when told, and understands.
 * @param $task
 * @return bool
 **/
function processTask($task) {
	if(preg_match('@(you\scan\'t|nothing|no|quit|cancel|exit|bye|close|terminate)@', $task)) {
		print "\nIt was a pleasure, if I've ever known one, goodbye!\n\n\n";
		return false;

	} else if(preg_match('@[A-z0-9_/\-[:space:]]+@', $task)) {
		file_put_contents('/home/egibson/projects/test_manager_comms.txt', $task);
		return processTask(ask("Alright, I'll $task\nHow else can I help?"));

	} else {
		return processTask(ask("I don\'t understand your inquiry: '$task', please rephrase?"));
	}
}
