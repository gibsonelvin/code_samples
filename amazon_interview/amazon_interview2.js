/*
Imagine that we are 10 years in the future. One thing that changes between now and then is that 
every vehicle reports its location (GPS coordinates) to a central server every second. 


You have access to the data-set containing all (past) vehicle positions for your city 
and are tasked with building a tool that helps city planners to measure traffic in the city.

 
The tool collects the following inputs from the user:
* Query start time
* Query end time 
* GPS coordinates of an intersection in the city


And should produce the number of times the intersection was crossed between 
the start time and end time. Write the algorithm that would power such a tool.

Note: This is not a system design question, for this exercise, you can assume 
the datasets can fit in memory. Remember this is a problem solving exercise with code and not a system design solution.




GPS Every Second
ALL GPS positions available
Time + coordinates

Times intersection has been crossed.

*/

let intersection = {
    "a": [2200, 3000]
}

let pastCoordinates = {
    "vehicle1": [
        ['2022-08-08 15:34:00', 1000, 2000],
        ['2022-08-08 15:35:00', 1500, 2000],
        ['2022-08-08 15:36:00', 2000, 2000],
        ['2022-08-08 15:37:00', 2500, 2000]
    ],
    "vehicle2": [
        ['2022-08-08 15:34:00', 1000, 2000],
        ['2022-08-08 15:35:00', 1500, 2000],
        ['2022-08-08 15:36:00', 2000, 2000],
        ['2022-08-08 15:37:00', 2500, 2000]
    ]
}

let position = null;
let intersectionCrosses = 0;

for(let vehicle in pastCoordinates) {
    for(let dataset in vehicle) {
        let time = dataset[0];
        let latt = dataset[1];
        let long = dataset[2];
        if(position === null) {
            if(latt < intersection.a[0] && long === intersection.a[1]) {
                position = "left";
            } else if(latt > intersection.a[0] && long === intersection.a[1]) {
                position = "right";
            } else {
                position = "not_equal_longtitude";
            }
        } else {
            if(latt < intersection.a[0] && long === intersection.a[1] && position === 'right') {
                intersectionCrosses++;
            } else if(latt > intersection.a[0] && long === intersection.a[1] && position === 'left') {
                intersectionCrosses++;
            }
        }
    }
}

return intersectionCrosses;

