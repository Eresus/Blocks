<?php
/**
 * Тесты класса Blocks_Entity_Table_Block
 *
 * @package Blocks
 * @subpackage Tests
 */


require_once __DIR__ . '/../../bootstrap.php';
require_once TESTS_SRC_DIR . '/blocks/classes/Entity/Table/Block.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';

/**
 * Тесты класса Blocks_Entity_Table_Block
 *
 * @package Blocks
 * @subpackage Tests
 */
class Blocks_Entity_Table_BlockTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers Blocks_Entity_Table_Block::getAppropriateBlock
     */
    public function testGetAppropriateBlock()
    {
        $DB = $this->getMock('stdClass', array('fetch'));
        $DB->expects($this->once())->method('fetch')->will($this->returnValue(array(
            'content' => 'foo'
        )));
        DB::setMock($DB);
        $table = new Blocks_Entity_Table_Block(new Plugin());
        $block = $table->getAppropriateBlock('foo', 1, 'page');
        $this->assertEquals(array('content' => 'foo'), $block);
    }

    /**
     * Проверка кэширования запроса
     * @covers Blocks_Entity_Table_Block::getAppropriateBlock
     */
    public function testQueryCaching()
    {
        $DB = $this->getMock('stdClass', array('fetch'));
        $DB->expects($this->any())->method('fetch')->will($this->returnCallback(
            function ($q)
            {
                static $previous = null;
                if (null === $previous)
                {
                    $previous = $q;
                }
                else
                {
                    assertSame($previous, $q);
                }
                return array();
            }
        ));
        DB::setMock($DB);
        $table = new Blocks_Entity_Table_Block(new Plugin());
        $table->getAppropriateBlock('foo', 1, 'page');
        $table->getAppropriateBlock('bar', 1, 'page');
    }

    /**
     * Проверка что в закэшированный запрос нормально попадают аргументы
     * @covers Blocks_Entity_Table_Block::getAppropriateBlock
     */
    public function testCachedQueryArgs()
    {
        $query = new Blocks_Entity_Table_BlockTest_ezcQuerySelect(null);

        $handler = $this->getMock('stdClass', array('createSelectQuery'));
        $handler->expects($this->any())->method('createSelectQuery')
            ->will($this->returnValue($query));

        $DB = $this->getMock('stdClass', array('getHandler'));
        $DB->expects($this->any())->method('getHandler')->will($this->returnValue($handler));
        DB::setMock($DB);
        $table = new Blocks_Entity_Table_Block(new Plugin());
        $table->getAppropriateBlock('foo', 1, 'page');
        $this->assertEquals('%|1|%', $query->params[0]);
        $this->assertEquals('foo', $query->params[1]);
        $this->assertEquals('page', $query->params[2]);
        $table->getAppropriateBlock('bar', 2, 'template');
        $this->assertEquals('%|2|%', $query->params[0]);
        $this->assertEquals('bar', $query->params[1]);
        $this->assertEquals('template', $query->params[2]);
    }
}

class Blocks_Entity_Table_BlockTest_ezcQuerySelect extends ezcQuerySelect
{
    public $params = array();

    public function bindParam(&$param)
    {
        $this->params []= &$param;
    }
}

