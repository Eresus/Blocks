<?php
/**
 * Тесты класса Blocks
 *
 * @package Blocks
 * @subpackage Tests
 */


require_once __DIR__ . '/bootstrap.php';
require_once TESTS_SRC_DIR . '/blocks.php';

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
        $renderBlocks = new ReflectionMethod('Blocks', 'renderBlocks');
        $renderBlocks->setAccessible(true);

        $DB = $this->getMock('stdClass', array('fetch'));
        $DB->expects($this->any())->method('fetch')->will($this->returnValue(
            array('content' => 'FOO')
        ));
        DB::setMock($DB);

        $html = 'foo $(Blocks:foo)';
        $blocks = new Blocks();
        $result = $renderBlocks->invoke($blocks, $html, 'template');
        $this->assertEquals('foo FOO', $result);
    }
}

