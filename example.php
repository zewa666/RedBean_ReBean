<?php

require 'rb.php';

R::setup('mysql:host=localhost;dbname=redbeandemo',
         'root','');

R::nuke();

$user = R::dispense('user');
$user->prename = "Unknown";
$user->surname = "User";
$user->age = 12;

R::store($user);

$export = $user->export();

$duplicate = R::dispense("revision" . $user->getMeta('type'));
$duplicate->action = "";                               // real enum needed
$duplicate->import($export);
$duplicate->lastedit = date('Y-m-d');
$duplicate->setMeta('cast.action','string');
$duplicate->setMeta('cast.lastedit','datetime');
//$duplicate->setMeta('cast.lastedit','timestamp');      // how to cast to timestamp
//$duplicate->setMeta('lastedit', 'CURRENT_TIMESTAMP');  // aka NOW()
R::store($duplicate);

// create trigger
function createOriginalColumnPrefix($prefix) {
  return function($col) use($prefix) {
    if($col == "id")
      return null;
    return $prefix . $col;
  };
};

function filter($val) { return !($val == "id" || $val == null); }
$revisionColumns = implode(",", array_filter(array_keys($user->getProperties()), "filter"));
$origColumns = function($prefix) use ($user) {
  return implode(",",
    array_filter(
      array_map(
        createOriginalColumnPrefix($prefix),
        array_keys($user->getProperties())
      ),
      'filter'
    )
  );
};

var_dump("INSERT INTO " . $duplicate->getMeta('type') . "(`action`, `lastedit`, " . $revisionColumns . ") VALUES ('insert', NOW(), " . $origColumns('NEW.') . ")");

R::exec("DROP TRIGGER IF EXISTS `trg_" . $user->getMeta('type') . "_AI`;");
R::exec("CREATE TRIGGER `trg_" . $user->getMeta('type') . "_AI` AFTER INSERT ON `" . $user->getMeta('type') . "` FOR EACH ROW BEGIN
\tINSERT INTO " . $duplicate->getMeta('type') . "(`action`, `lastedit`, " . $revisionColumns . ") VALUES ('insert', NOW(), " . $origColumns('NEW.') . ");
END;");

R::exec("DROP TRIGGER IF EXISTS `trg_" . $user->getMeta('type') . "_AU`;");
R::exec("CREATE TRIGGER `trg_" . $user->getMeta('type') . "_AU` AFTER UPDATE ON `" . $user->getMeta('type') . "` FOR EACH ROW BEGIN
\tINSERT INTO " . $duplicate->getMeta('type') . "(`action`, `lastedit`, " . $revisionColumns . ") VALUES ('update', NOW(), " . $origColumns('NEW.') . ");
END;");

R::exec("DROP TRIGGER IF EXISTS `trg_" . $user->getMeta('type') . "_AD`;");
R::exec("CREATE TRIGGER `trg_" . $user->getMeta('type') . "_AD` AFTER DELETE ON `" . $user->getMeta('type') . "` FOR EACH ROW BEGIN
\tINSERT INTO " . $duplicate->getMeta('type') . "(`action`, `lastedit`, " . $revisionColumns . ") VALUES ('delete', NOW(), " . $origColumns('OLD.') . ");
END;");



$usernew = R::dispense('user');
$usernew->prename = "Blub";
R::store($usernew);
