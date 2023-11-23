<?php

// Big O timing would be less than n^2

// Temporary Storage
$cache = [];

// Test Queue of IDs
$queue = [3, 3, 5, 4, 5, 5, 3, 2, 1, 4];

// Positions of each ID in the queue
$positions = collectPositions();


// Initializes script:
runAlgorithm();


// ---------------------------------------FUNCTIONS---------------------------------------------


// Collects positions of IDs -- O(n)
function collectPositions() {
	global $queue;
	$positions = [];
	for($i = 0; $i < count($queue); $i++) {

		// Adds queued ID position to positions array, defining the key as a single element array if not previously defined
		if(!in_array($queue[$i], array_keys($positions))) {
			$positions[$queue[$i]] = [$i];
		} else {
			$positions[$queue[$i]][] = $i;
		}
	}
	return $positions;
}


// Adds each queued ID to the cache -- O(n)
function runAlgorithm() {
	global $queue;
	for($i = 0; $i < count($queue); $i++) {
		addToCache($queue[$i], $i);
		displayCache();
	}
}

// Evicts cache and adds new element (if necessary)
function addToCache($id, $currentIteration) {
	global $positions, $cache;
	evictCache($id, $currentIteration);
	if(!cacheHasId($id)) {
		$cache[$id] = $positions[$id];
	}
}

// Evicts cache if necessary (2 elements, without current element found)
function evictCache($id, $currentIteration) {
	global $cache;

	// If there are two elements in the cache, checks if the current ID requires eviction
	if(count($cache) === 2 && !cacheHasId($id)) {
		$removeID = determineFurthestAway($cache, $currentIteration);
		unset($cache[$removeID]);
	} else {
		return;
	}
}

// Returns whether or not a given ID is in the cache
function cacheHasId($id) {
	global $cache;
	return in_array($id, array_keys($cache));
}

// Looks at the (COLLECTED) positions of cache elements, and determines next occurrence of each
function determineFurthestAway($cache, $currentIteration) {
	$ids = array_keys($cache);
	$nextPositionA = determineNextPosition($cache[$ids[0]], $currentIteration);
	$nextPositionB = determineNextPosition($cache[$ids[1]], $currentIteration);

	// Returns the element with the highest (last) position, if both are equal, drops last added element
	return ( $nextPositionA > $nextPositionB
		 ? $ids[0]
		 : $ids[1]
	);
}


// Determines next position by looping though known (collected) positions, and finding next position past the current position/iteration
// O time is limited to the number of occurrences of each number * 1
function determineNextPosition($positions, $currentIteration) {

	// Loops through positions of a given ID, skipping positions lower than the current iteration
	for($i = 0; $i < count($positions); $i++) {
		if($positions[$i] < $currentIteration) {
			continue;
		} else {
			return $positions[$i];
		}
	}

	// Returns infinite if not found
	return INF;
}

function displayCache() {
	global $cache;
	var_dump($cache);
}


