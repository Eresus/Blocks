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

    /**
     * Проверка рекурсивной подстановки блоков
     * @covers Blocks::renderBlocks
     */
    public function testRenderBlocksRecursive()
    {
        $blocksTable = new ReflectionProperty('Blocks', 'blocksTable');
        $blocksTable->setAccessible(true);

        $renderBlocks = new ReflectionMethod('Blocks', 'renderBlocks');
        $renderBlocks->setAccessible(true);

        $page = new stdClass();
        $page->id = 1;

        $app = $this->getMock('stdClass', array('getPage'));
        $app->expects($this->any())->method('getPage')->will($this->returnValue($page));

        $Eresus_Kernel = $this->getMock('stdClass', array('app'));
        $Eresus_Kernel->expects($this->any())->method('app')->will($this->returnValue($app));
        Eresus_Kernel::setMock($Eresus_Kernel);

        $table = $this->getMock('stdClass', array('getAppropriateBlock'));
        $table->expects($this->any())->method('getAppropriateBlock')->will($this->returnValueMap(
            array(
                array('foo', 1, 'page', array('content' => '$(Blocks:bar)')),
                array('bar', 1, 'page', array('content' => 'BAR'))
            )
        ));

        $html = 'foo $(Blocks:foo)';
        $blocks = new Blocks();
        $blocksTable->setValue($blocks, $table);
        $result = $renderBlocks->invoke($blocks, $html, 'page');
        $this->assertEquals('foo BAR', $result);
    }
}

