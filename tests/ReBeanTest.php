<?php

require_once 'PHPUnit/Autoload.php';
require_once "../rb.php";
require_once "../ReBean.php";

R::setup('mysql:host=localhost;dbname=redbeandemo',
             'root','');

class ReBeanTest extends PHPUnit_Framework_TestCase
{
  private $user = null;

  protected function setUp() {
    // create demo user for tests
    $this->user = R::dispense('testuser');
    $this->user->prename = "Test";
    $this->user->surname = "User";
    $this->user->age = 10;

    // store the bean
    R::store($this->user);

    // create revision support
    R::createRevisionSupport($this->user);
  }

  protected function tearDown() {
    // nuke to get clean set
    R::nuke();
  }

  public function testExtBinding() {
    $result = is_callable("R::createRevisionSupport");
    $this->assertTrue($result);
  }

  public function testRevisionTableCreated() {
    $expected = true;
    $actual = R::getWriter()->tableExists("revision" . $this->user->getMeta('type'));

    $this->assertTrue($expected, $actual);
  }

  public function testRevisionInsert() {
    $expected = "Tested";

    $newUser = R::dispense('testuser');
    $newUser->prename = $expected;
    R::store($newUser);

    $revisiontestuser = R::findOne('revisiontestuser',' prename = :prename AND action = :action',
      array(
        ':prename' => $expected,
        ':action' => "insert"
      )
    );

    $this->assertEquals($expected, $revisiontestuser->prename);
  }

  public function testRevisionUpdate() {
    $expected = "Tested";

    $this->user->prename = $expected;
    R::store($this->user);

    $revisiontestuser = R::findOne('revisiontestuser',' prename = :prename AND action = :action',
      array(
        ':prename' => $expected,
        ':action' => "update"
      )
    );

    $this->assertEquals($expected, $revisiontestuser->prename);
  }

  public function testRevisionDelete() {
    $expected = "Test";

    R::trash($this->user);

    $revisiontestuser = R::findOne('revisiontestuser',' prename = :prename AND action = :action',
      array(
        ':prename' => "Test",
        ':action' => "delete"
      )
    );

    $this->assertEquals($expected, $revisiontestuser->prename);
  }

  public function testProhibitDoubleRevisionSupport() {
    $this->setExpectedException('ReBean_Exception', "The given Bean has already revision support");
    R::createRevisionSupport($this->user);
  }
}
