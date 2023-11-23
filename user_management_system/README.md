# User management system

## Demonstration user management system (fullstack web application)

`db.sql` - database file; creates user, grants privileges, as well as creates supporting table.

`Users` class extends Base `Model` class, which includes necessary functions

`DB` Class is used for storing DB credentials

`AppError` Class is used for error management

`process_user_form.php` - processes create and update requests related to forms.

`getStates.php` - supplies application with administrative division information based on country. Given the problematic API, I've stored data locally in `localeData.json`.

`create_from.php` - renders the form to create a new user.

`edit_from.php` - renders the form to update an existing user.

`main.js` - Main JS object for UI functions

Self Explanatory:
    `autoload.php`, `header.php`, `footer.php`, `index.php`, `style.css`