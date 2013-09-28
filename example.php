<?php
// require the one-file-build
require 'rb.php';

// currently only working for MYSQL so setup your connection here
R::setup('mysql:host=localhost;dbname=redbeandemo',
         'root','');

// get instance of the revision plugin
$rebeanPlugin = new RedBean_ReBean();

/*
 * just for demo purpose to see that each time really everything gets build
 * don't do this on your DB :)
 */
R::nuke();

// Create a new Bean and set some properties
$user = R::dispense('user');
$user->prename = "Unknown";
$user->surname = "User";
$user->age = 12;

// store the bean
R::store($user);

// now ask the plugin to create revision support for your
$rebeanPlugin->createRevisionSupport($user);

/*
 * some CRUD tests to verify that all changes are tracked in the revision table
 * also add some sleeps to see difference in logged date
 */
$usernew = R::dispense('user');
$usernew->prename = "Test1";
R::store($usernew);
sleep(1);
$usernew->prename = "Test2";
R::store($usernew);
sleep(2);
R::trash($usernew);
sleep(1);

// output of the revision table
$revisions = R::find('revisionuser');
foreach($revisions as $rev)
{
  echo "Action: " . $rev->action . " -> ".
                    $rev->prename . " "  .
                    $rev->surname . " "  .
                    $rev->age            .
                    " Lastchangedate: "  .
                    $rev->lastedit . "<br/>";
}
