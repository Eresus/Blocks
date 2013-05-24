<?php
/**
 * Тесты класса Blocks_Entity_Table_Block
 *
 * @package Blocks
 * @subpackage Tests
 */


require_once __DIR__ . '/../../bootstrap.php';
require_once TESTS_SRC_DIR . '/blocks/classes/Entity/Table/Block.php';

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
}

