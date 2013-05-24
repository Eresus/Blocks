<?php
/**
 * Тесты класса Blocks
 *
 * @package Blocks
 * @subpackage Tests
 */


require_once __DIR__ . '/bootstrap.php';
require_once TESTS_SRC_DIR . '/blocks.php';
require_once TESTS_SRC_DIR . '/blocks/classes/Entity/Table/Block.php';

/**
 * Тесты класса Blocks
 *
 * @package Blocks
 * @subpackage Tests
 */
class BlocksTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers Blocks::renderBlocks
     */
    public function testRenderBlocks()
    {
        $blocksTable = new ReflectionProperty('Blocks', 'blocksTable');
        $blocksTable->setAccessible(true);

        $renderBlocks = new ReflectionMethod('Blocks', 'renderBlocks');
        $renderBlocks->setAccessible(true);

        $table = $this->getMock('stdClass', array('getAppropriateBlock'));
        $table->expects($this->any())->method('getAppropriateBlock')->will($this->returnValue(
            array('content' => 'FOO')
        ));

        $html = 'foo $(Blocks:foo)';
        $blocks = new Blocks();
        $blocksTable->setValue($blocks, $table);
        $result = $renderBlocks->invoke($blocks, $html, 'template');
        $this->assertEquals('foo FOO', $result);
    }
}

