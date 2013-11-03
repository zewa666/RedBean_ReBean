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
    // check if the bean already has revision support
    if(R::getWriter()->tableExists("revision" . $bean->getMeta('type')))
    {
      throw new ReBean_Exception("The given Bean has already revision support");
    }

    $export = $bean->export();
    $duplicate = R::dispense("revision" . $bean->getMeta('type'));
    $duplicate->action = "";                                 // real enum needed
    $duplicate->original_id = $bean->id;
    $duplicate->import($export);
    $duplicate->lastedit = date('Y-m-d h:i:s');
    $duplicate->setMeta('cast.action','string');
    $duplicate->setMeta('cast.lastedit','datetime');
    RedBean_Facade::store($duplicate);

    $this->createTrigger($bean, $duplicate);
  }

  private function getRevisionColumns(RedBean_OODBBean $bean)
  {
    return implode(",",
      array_filter(                                              // remove nulls
        array_map(                                               // transform values instead foreach
          function($val) {
            if($val == "id")
            {
              return "original_id";
            }
            else
            {
              return (empty($val) || $val == null) ? null : $val;
            }
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
            return $prefix . $col;
          },
          array_keys($bean->getProperties())
        )
      )
    );
  }

  private function createTrigger(RedBean_OODBBean $bean, RedBean_OODBBean $duplicate)
  {
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

class ReBean_Exception extends Exception
{
  public function __construct($message, $code = 0, Exception $previous = null) {
    parent::__construct($message, $code, $previous);
  }
}

// add plugin to RedBean facade
R::ext( 'createRevisionSupport', function(RedBean_OODBBean $bean) {
    $rebeanPlugin = new RedBean_ReBean();
    $rebeanPlugin->createRevisionSupport($bean);
});
