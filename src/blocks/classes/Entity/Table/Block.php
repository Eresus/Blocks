<?php
/**
 * Таблица блоков
 *
 * @version ${product.version}
 *
 * @copyright 2013, ООО "Два слона", http://dvaslona.ru/
 * @license http://www.gnu.org/licenses/gpl.txt	GPL License 3
 * @author Михаил Красильников <mk@dvaslona.ru>
 *
 * Данная программа является свободным программным обеспечением. Вы
 * вправе распространять ее и/или модифицировать в соответствии с
 * условиями версии 3 либо (по вашему выбору) с условиями более поздней
 * версии Стандартной Общественной Лицензии GNU, опубликованной Free
 * Software Foundation.
 *
 * Мы распространяем эту программу в надежде на то, что она будет вам
 * полезной, однако НЕ ПРЕДОСТАВЛЯЕМ НА НЕЕ НИКАКИХ ГАРАНТИЙ, в том
 * числе ГАРАНТИИ ТОВАРНОГО СОСТОЯНИЯ ПРИ ПРОДАЖЕ и ПРИГОДНОСТИ ДЛЯ
 * ИСПОЛЬЗОВАНИЯ В КОНКРЕТНЫХ ЦЕЛЯХ. Для получения более подробной
 * информации ознакомьтесь со Стандартной Общественной Лицензией GNU.
 *
 * Вы должны были получить копию Стандартной Общественной Лицензии
 * GNU с этой программой. Если Вы ее не получили, смотрите документ на
 * <http://www.gnu.org/licenses/>
 *
 * @package Blocks
 */

/**
 * Таблица блоков
 *
 * @package Blocks
 * @since 4.01
 */
class Blocks_Entity_Table_Block
{
    /**
     * Основной объект модуля
     * @var Blocks
     * @since 4.01
     */
    private $plugin;

    /**
     * Кэш запроса к БД
     * @var array
     * @since 4.01
     */
    private $query = null;

    /**
     * Кэш параметров для getAppropriateBlock
     * @var array
     * @since 4.01
     */
    private $paramCache = array();

    /**
     * @param Plugin $plugin
     * @since 4.01
     */
    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Возвращает наиболее подходящий блок
     *
     * @param string $name       имя блока
     * @param int    $sectionId  идентификатор раздела сайта
     * @param string $target     цель (страница или шаблон)
     *
     * @return array|null  описание блока в виде ассоциативного массива или null, если блока нет
     *
     * @since 4.01
     */
    public function getAppropriateBlock($name, $sectionId, $target)
    {
        /*
         * Помещаем параметры в постоянное свойство, чтобы закэшированный запрос мог обращаться к
         * ним по ссылке.
         */
        $this->paramCache['name'] = $name;
        $this->paramCache['section'] = '%|' . $sectionId . '|%';
        $this->paramCache['target'] = $target;

        if (null === $this->query)
        {
            $this->query = DB::getHandler()->createSelectQuery();
            $e = $this->query->expr;
            $this->query->select('*');
            $this->query->from($this->getTableName());
            $this->query->where(
                $e->lAnd(
                    $e->eq('active', $this->query->bindValue(true)),
                    $e->lOr(
                        $e->like('section', $this->query->bindValue('%|all|%')),
                        $e->like('section', $this->query->bindParam($this->paramCache['section']))
                    ),
                    $e->eq('block', $this->query->bindParam($this->paramCache['name'])),
                    $e->eq('target', $this->query->bindParam($this->paramCache['target']))
                )
            )
            ->orderBy('priority', ezcQuerySelect::DESC);
        }

        try
        {
            $raw = DB::fetch($this->query);
        }
        catch (DBQueryException $e)
        {
            Core::logException($e);
            $raw = null;
        }
        return $raw;
    }

    /**
     * Возвращает имя таблицы
     *
     * @return string
     * @since 4.01
     */
    private function getTableName()
    {
        return $this->plugin->name;
    }
}

