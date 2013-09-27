<?php
/**
 * RedBean ReBean (Revision Bean)
 *
 * @file    ReBean.php
 * @desc    Revisionplugin to support each bean with custom revision tables and triggers
 * @author  Zewa
 *
 */
class RedBean_ReBean implements RedBean_Plugin
{
  /**
   * Creates the revision support for the given Bean
   *
   * @param  RedBean_OODBBean $bean          The bean-type to be revision supported
   */
  public function createRevisionSupport(RedBean_OODBBean $bean)
  {
    $export = $bean->export();
    $duplicate = R::dispense("revision" . $bean->getMeta('type'));
    $duplicate->action = "";                                 // real enum needed
    $duplicate->import($export);
    $duplicate->lastedit = date('Y-m-d h:i:s');
    $duplicate->setMeta('cast.action','string');
    $duplicate->setMeta('cast.lastedit','datetime');
    //$duplicate->setMeta('cast.lastedit','timestamp');      // how to cast to timestamp
    //$duplicate->setDefault('lastedit', R::$f->now());      // aka NOW()
    RedBean_Facade::store($duplicate);

    $this->createTrigger($bean, $duplicate);
  }

  private function getRevisionColumns(RedBean_OODBBean $bean)
  {
    return implode(",",
      array_filter(                                              // remove nulls
        array_map(                                               // transform values instead foreach
          function($val) {
            return ($val == "id" || $val == null) ? null : $val;
          },
          array_keys($bean->getProperties())                     // use the array_key to get the colName
        )
      )
    );
  }

  private function getOriginalColumns(RedBean_OODBBean $bean, $prefix)
  {
    $self = $this;
    return implode(",",
      array_filter(
        array_map(
          function($col) use ($prefix) {
            if($col == "id")
              return null;
            return $prefix . $col;
          },
          array_keys($bean->getProperties())
        )
      )
    );
  }

  private function createTrigger(RedBean_OODBBean $bean, RedBean_OODBBean $duplicate)
  {
 /*   var_dump("CREATE TRIGGER `trg_" . $bean->getMeta('type') . "_AI` AFTER INSERT ON `" . $bean->getMeta('type') . "` FOR EACH ROW BEGIN
    \tINSERT INTO " . $duplicate->getMeta('type') . "(`action`, `lastedit`, " . $this->getRevisionColumns($bean) . ") VALUES ('insert', NOW(), " . $this->getOriginalColumns($bean, 'NEW.') . ");
    END;");*/

    RedBean_Facade::$adapter->exec("DROP TRIGGER IF EXISTS `trg_" . $bean->getMeta('type') . "_AI`;");
    RedBean_Facade::$adapter->exec("CREATE TRIGGER `trg_" . $bean->getMeta('type') . "_AI` AFTER INSERT ON `" . $bean->getMeta('type') . "` FOR EACH ROW BEGIN
    \tINSERT INTO " . $duplicate->getMeta('type') . "(`action`, `lastedit`, " . $this->getRevisionColumns($bean) . ") VALUES ('insert', NOW(), " . $this->getOriginalColumns($bean, 'NEW.') . ");
    END;");

    RedBean_Facade::$adapter->exec("DROP TRIGGER IF EXISTS `trg_" . $bean->getMeta('type') . "_AU`;");
    RedBean_Facade::$adapter->exec("CREATE TRIGGER `trg_" . $bean->getMeta('type') . "_AU` AFTER UPDATE ON `" . $bean->getMeta('type') . "` FOR EACH ROW BEGIN
    \tINSERT INTO " . $duplicate->getMeta('type') . "(`action`, `lastedit`, " . $this->getRevisionColumns($bean) . ") VALUES ('update', NOW(), " . $this->getOriginalColumns($bean, 'NEW.') . ");
    END;");

    RedBean_Facade::$adapter->exec("DROP TRIGGER IF EXISTS `trg_" . $bean->getMeta('type') . "_AD`;");
    RedBean_Facade::$adapter->exec("CREATE TRIGGER `trg_" . $bean->getMeta('type') . "_AD` AFTER DELETE ON `" . $bean->getMeta('type') . "` FOR EACH ROW BEGIN
    \tINSERT INTO " . $duplicate->getMeta('type') . "(`action`, `lastedit`, " . $this->getRevisionColumns($bean) . ") VALUES ('delete', NOW(), " . $this->getOriginalColumns($bean, 'OLD.') . ");
    END;");
  }
}
