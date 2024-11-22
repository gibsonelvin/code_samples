# Code Sample Repository 

## A few coding examples from my many years of coding. This is just a sample, a very small peak into my coding experience. All work was completed independently.

`Drawer` - A small JS project I did in a few hours to exercise my skill in JS/svg.
	    
`Full Multi-Scale PHP Graph` - Dynamic configurable graph tool written in PHP, can be supplied with a query, CSV, or CSV file. Extremely flexible and configurable.

`Older` - Advanced coding projects when my coding skill was much less mature.

	`db_synchronizer` - Schema synchronizer tool based on "alter" files, allowed for synchronizing, rebasing, resetting, exporting, archiving ".zip" files, as well as importing fixtures.

	`service_converter` - A converter built for service methods was used to remove classes instantiated the traditional way within those services, and replace them with injected dependencies to make our codebase more testable, and allow for easier mocking of classes. My manager at the time didn't think it could be done, and my time was limited, so it may be a little messy, but it worked! :)

	`shim_creator` - This tool was developed to create static wrappers for our dynamic classes as we were moving from a static codebase to a more testable dynamic codebase.

`React-data-request` - I wrote code in React and NodeJS to implement a Data request feature as part of a larger web application. The code covered 4 repositories represented here as folders:

	api - Routed end points responsible for sending and receiving data as well as some data grooming.
	
	fetch-api - Request framework used to facilitate requests to the API, with all api routes defined as reusable objects.
	
	db - DB Models and migrations.
	
	portal - Web Application UI with pages and components.

`Test Management Apparatus` - A tool created to execute tests, and allow for live redirecting via text-based input, during testing (i.e. excluding a directory, or adding an additional directory or test, or exiting altogether).

`user_management_system` - A full stack web development example, from scratch! Including an ORM, Main JS object for UI/UX features, and CSS animations. This was a request for a coding sample, all code is original, and no libraries were used. All functionality is from scratch, Vanilla JS, PHP, HTML5, CSS3. Here is a description of each file:

	Users class - extends Base `Model` class, which includes necessary functions

	DB Class - is used for storing DB credentials

	AppError Class - is used for error management

	process_user_form.php - processes create and update requests related to forms.

	getStates.php - supplies application with administrative division information based on country. Given the problematic API, I've stored data locally in `localeData.json`.

	create_form.php - renders the form to create a new user.

	edit_form.php - renders the form to update an existing user.

	main.js - Main JS object for UI functions

	Self Explanatory:
	    `autoload.php`, `header.php`, `footer.php`, `index.php`, `style.css`
