RedBean_ReBean (RevisionBean)
=======================

This is a plugin for the [RedBeanPHP ORM](http://www.redbeanphp.com/), which
will be generating automatically revision tables for given Beans

This is achieved by creating a table named after your beantype plus the "revision" prefix.
The table contains all the columns from your bean. Additionally it has a column "action" specifying
if this revision was made by an [INSERT,UPDATE,DELETE] statement.
Finally there is a "lastedit" column indicating when the change happend.

All the functionallity is achieved by using AFTER Triggers

Current status:
The plugin so far works for Mysql, although not properly tested. Please use
it just for testing and NOT IN PRODUCTION so far.

Usage:
=======================

- Download the latest version of [RedBean from Github](https://github.com/gabordemooij/redbean) or
  install via Composer.
- Add the file ReBean.php to the RedBean/Plugin folder
- Either manually require the file or see the [RedBean instructions](http://www.redbeanphp.com/replica) for building your on RB.php file
- In your main file where you setup RedBean add following code to get an instance of the revision plugin

```php
   $rebeanPlugin = new RedBean_ReBean();
```

- Create your first bean type
- Store it in the DB (R::store($YOURBEAN))
- Call the revision method like this

```php
   $rebeanPlugin->createRevisionSupport($YOURBEAN);
```
- Happy modifying of your previous Bean. You should be able to see all changes
  in the created revisiontable

Example:
=======================

Take a look at the included example.php. It uses an rebuild RB.php which includes the Plugin already
