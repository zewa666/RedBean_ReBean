RedBean_ReBean (RevisionBean)
=======================

This is a plugin for the [RedBeanPHP ORM](http://www.redbeanphp.com/), which
will be generating automatically revision tables for given Beans

This is achieved by creating a table named after your beantype plus the "revision" prefix.
The table contains all the columns from your bean. Additionally it has a column "action" specifying
if this revision was made by an [INSERT,UPDATE,DELETE] statement.
Also inlcuded is the column "original_id" which represents the ID of the bean, that's been
revisioned. Finally there is a "lastedit" column indicating when the change happend.

All the functionallity is achieved by using AFTER Triggers

Current status:
The plugin so far works for Mysql, although not properly tested. Please use
it just for testing and NOT IN PRODUCTION so far.

Update:
=======================

- Now uses the R::ext plugin helper so you can access Revisioning without any previous
  instance creation. Just use R::createRevisionSupport($YOURBEAN);
- Added PHPUnit Tests
- Throw Exception if Bean is already under revision support


Usage:
=======================

- Download the latest version of [RedBean from Github](https://github.com/gabordemooij/redbean) or
  install via Composer.
- Add the file ReBean.php to the RedBean/Plugin folder
- Either manually require the file or see the [RedBean instructions](http://www.redbeanphp.com/replica) for building your on RB.php file
- Create your first bean type
- Store it in the DB (R::store($YOURBEAN))
- Call the revision method like this

```php
   R::createRevisionSupport($YOURBEAN);
```
- Happy modifying of your previous Bean. You should be able to see all changes
  in the created revisiontable

Example:
=======================

Take a look at the included example.php.
