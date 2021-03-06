<?php
/**
 * Модуль «Блоки»
 *
 * Управление текстовыми блоками
 *
 * @version ${product.version}
 *
 * @copyright 2005, Михаил Красильников, <m.krasilnikov@yandex.ru>
 * @copyright 2010, ООО "Два слона", http://dvaslona.ru/
 * @license http://www.gnu.org/licenses/gpl.txt GPL License 3
 * @author Михаил Красильников <m.krasilnikov@yandex.ru>
 *
 * Данная программа является свободным программным обеспечением. Вы
 * вправе распространять ее и/или модифицировать в соответствии с
 * условиями версии 3 либо по вашему выбору с условиями более поздней
 * версии Стандартной Общественной Лицензии GNU, опубликованной Free
 * Software Foundation.
 *
 * Мы распространяем эту программу в надежде на то, что она будет вам
 * полезной, однако НЕ ПРЕДОСТАВЛЯЕМ НА НЕЕ НИКАКИХ ГАРАНТИЙ, в том
 * числе ГАРАНТИИ ТОВАРНОГО СОСТОЯНИЯ ПРИ ПРОДАЖЕ и ПРИГОДНОСТИ ДЛЯ
 * ИСПОЛЬЗОВАНИЯ В КОНКРЕТНЫХ ЦЕЛЯХ. Для получения более подробной
 * информации ознакомьтесь со Стандартной Общественной Лицензией GNU.
 *
 * @package Blocks
 */

/**
 * Класс плагина
 *
 * @package Blocks
 */
class Blocks extends Plugin
{
    /**
     * Требуемая версия ядра
     * @var string
     */
    public $kernel = '3.00';

    /**
     * Название плагина
     * @var string
     */
    public $title = 'Блоки';

    /**
     * Версия плагина
     * @var string
     */
    public $version = '${product.version}';

    /**
     * Описание плагина
     * @var string
     */
    public $description = 'Система управления текстовыми блоками';

    /**
     * Описание таблицы данных
     * @var array
     */
    public $table = array (
        'name' => 'blocks',
        'key'=> 'id',
        'sortMode' => 'id',
        'sortDesc' => false,
        'columns' => array(
            array('name' => 'caption', 'caption' => 'Название'),
            array('name' => 'block', 'caption' => 'Блок', 'align'=> 'right'),
            array('name' => 'description', 'caption' => 'Описание'),
            array('name' => 'priority', 'caption' =>
                '<span title="Приоритет" style="cursor: default;">&nbsp;&nbsp;*</span>',
                'align'=>'center'),
        ),
        'controls' => array (
            'delete' => '',
            'edit' => '',
            'toggle' => '',
        ),
        'tabs' => array(
            'width'=>'180px',
            'items'=>array(
                array('caption'=>'Добавить блок', 'name'=>'action', 'value'=>'create')
            ),
        )
    );

    /**
     * Таблица блоков
     * @var null|Blocks_Entity_Table_Block
     * @since 4.01
     */
    private $blocksTable = null;

    /**
     * Текущий уровень рекурсии
     * @var int
     * @since 4.01
     */
    private $recursion = 0;

    /**
     * Предел рекурсивной обработки блоков
     * @var int
     * @since 4.01
     */
    private $recursionLimit = 10;

    /**
     * Конструктор
     *
     * @return Blocks
     */
    public function __construct()
    {
        parent::__construct();
        $this->listenEvents('adminOnMenuRender', 'clientOnContentRender', 'clientOnPageRender');
    }

    /**
     * @see Plugin::install()
     */
    public function install()
    {
        parent::install();
        $this->dbCreateTable('
            `id` int(10) unsigned NOT NULL auto_increment,
            `caption` varchar(255) default NULL,
            `description` varchar(255) default NULL,
            `active` tinyint(1) unsigned default NULL,
            `section` varchar(255) default NULL,
            `priority` int(10) unsigned default NULL,
            `block` varchar(31) default NULL,
            `target` varchar(63) default NULL,
            `content` text,
            PRIMARY KEY (`id`),
            KEY `active` (`active`),
            KEY `section` (`section`),
            KEY `block` (`block`),
            KEY `target` (`target`)
        ');
    }

    /**
     * Добавляет пункт «Блоки» в меню «Расширения»
     *
     * @return void
     */
    public function adminOnMenuRender()
    {
        /** @var TAdminUI $page */
        $page = Eresus_Kernel::app()->getPage();
        $page->addMenuItem(admExtensions, array(
            'access' => EDITOR,
            'link' => $this->name,
            'caption' => $this->title,
            'hint' => $this->description
        ));
    }

    /**
     * Возвращает разметку интерфейса управления
     *
     * @return string  HTML
     */
    public function adminRender()
    {
        /** @var TAdminUI $page */
        $page = Eresus_Kernel::app()->getPage();

        $result = '';
        if (arg('id'))
        {
            $item = $this->dbItem('', arg('id', 'int'));
            if (!empty($item['caption']))
            {
                $page->title .= ' - ' . $item['caption'];
            }
        }
        if (arg('update'))
        {
            $this->update();
        }
        elseif (arg('toggle'))
        {
            $this->toggle(arg('toggle', 'int'));
        }
        elseif (arg('delete'))
        {
            $this->delete(arg('delete', 'int'));
        }
        elseif (arg('id'))
        {
            $result = $this->edit();
        }
        else
        {
            switch (arg('action'))
            {
                case 'create':
                    $result = $this->create();
                    break;
                case 'insert':
                    $this->insert();
                    break;
                default:
                    $result = $page->renderTable($this->table);
                    break;
            }
        }
        return $result;
    }

    /**
     * Подставляет блоки в шаблон
     *
     * @param string $text
     *
     * @return string
     */
    public function clientOnContentRender($text)
    {
        /** @var TClientUI $page */
        $page = Eresus_Kernel::app()->getPage();
        $template = Templates::getInstance()->load($page->getTemplateName());
        $source = $this->renderBlocks($template->getSource(), 'template');
        $template->setSource($source);
        return $text;
    }

    /**
     * Подставляет блоки в отрисованную страницу
     *
     * @param string $text Содержимое страницы
     *
     * @return string
     */
    public function clientOnPageRender($text)
    {
        $text = $this->renderBlocks($text, 'page');
        return $text;
    }

    /**
     * Строит дерево разделов для диалогов добавления и изменения блока
     *
     * @param int $owner
     * @param int $level
     *
     * @return array
     */
    private function menuBranch($owner = 0, $level = 0)
    {
        $result = array(array(), array());

        $q = DB::getHandler()->createSelectQuery();
        $e = $q->expr;
        $q->select('id', 'caption')
            ->from('pages')
            ->where(
                $e->lAnd(
                    $e->gte('access', USER),
                    $e->eq('owner', $q->bindValue($owner, null, PDO::PARAM_INT)),
                    $e->eq('active', true)
                )
            )
            ->orderBy('position');

        $items = DB::fetchAll($q);

        if (count($items))
        {
            foreach ($items as $item)
            {
                $result[0][] = str_repeat('- ', $level) . $item['caption'];
                $result[1][] = $item['id'];
                $sub = $this->menuBranch($item['id'], $level + 1);
                if (count($sub[0]))
                {
                    $result[0] = array_merge($result[0], $sub[0]);
                    $result[1] = array_merge($result[1], $sub[1]);
                }
            }
        }
        return $result;
    }

    /**
     * Добавляет блок в БД
     *
     * @return void
     */
    private function insert()
    {
        $item = array();
        $item['caption'] = arg('caption', 'dbsafe');
        $item['description'] = arg('description', 'dbsafe');
        $item['priority'] = arg('priority', 'int');
        $item['block'] = arg('block', 'dbsafe');
        $item['target'] = arg('target', 'dbsafe');
        $item['content'] = arg('content', 'dbsafe');

        $section = arg('section');
        if ($section && $section != 'all')
        {
            $item['section'] = '|' . implode('|', $section) . '|';
        }
        else
        {
            $item['section'] = '|all|';
        }

        $item['active'] = true;
        $this->dbInsert('', $item);
        HTTP::redirect(arg('submitURL'));
    }

    /**
     * Изменяет блок в БД
     *
     * @return void
     */
    private function update()
    {
        $item = $this->dbItem('', arg('update', 'int'));

        $item['caption'] = arg('caption', 'dbsafe');
        $item['description'] = arg('description', 'dbsafe');
        $item['priority'] = arg('priority', 'int');
        $item['block'] = arg('block', 'dbsafe');
        $item['target'] = arg('target', 'dbsafe');
        $item['content'] = arg('content', 'dbsafe');
        $item['active'] = arg('active', 'int');

        $section = arg('section');
        if ($section && $section != 'all')
        {
            $item['section'] = '|' . implode('|', $section) . '|';
        }
        else
        {
            $item['section'] = '|all|';
        }

        $this->dbUpdate('', $item);
        HTTP::redirect(arg('submitURL'));
    }

    /**
     * Возвращает диалог добавления блока
     *
     * @return string HTML
     */
    private function create()
    {
        $sections = $this->menuBranch();
        array_unshift($sections[0], 'ВСЕ РАЗДЕЛЫ');
        array_unshift($sections[1], 'all');
        $form = array(
            'name' => 'formCreate',
            'caption' => 'Добавить блок',
            'width' => '95%',
            'fields' => array (
                array ('type'=>'hidden','name'=>'action', 'value'=>'insert'),
                array ('type' => 'edit', 'name' => 'caption', 'label' => 'Заголовок', 'width' => '100%',
                    'maxlength' => '255', 'pattern'=>'/\S+/', 'errormsg'=>'Заголовок не может быть пустым!'),
                array ('type' => 'edit', 'name' => 'description', 'label' => 'Описание', 'width' => '100%',
                    'maxlength' => '255'),
                array ('type' => 'listbox', 'name' => 'section', 'label' => 'Разделы', 'height'=> 5,
                    'items'=>$sections[0], 'values'=>$sections[1]),
                array ('type' => 'edit', 'name' => 'priority', 'label' => 'Приоритет', 'width' => '20px',
                    'comment' => 'Большие значения - больший приоритет', 'value'=>0,
                    'pattern'=>'/^\d+$/', 'errormsg'=>'Приоритет задается только цифрами!'),
                array ('type' => 'edit', 'name' => 'block', 'label' => 'Блок', 'width' => '100px',
                    'maxlength' => 31, 'pattern'=>'/^\S+$/',
                    'errormsg'=>'Имя блока не может быть пустым или содержать пробелы!'),
                array ('type' => 'select', 'name' => 'target', 'label' => 'Область',
                    'items' => array('Отрисованная страница','Шаблон страницы'),
                    'values' => array('page','template')),
                array ('type' => 'html', 'name' => 'content', 'label' => 'Содержимое', 'height' => '300px'),
            ),
            'buttons' => array('ok', 'cancel'),
        );

        /** @var TAdminUI $page */
        $page = Eresus_Kernel::app()->getPage();
        $result = $page->renderForm($form);
        return $result;
    }

    /**
     * Возвращает диалог изменения блока
     *
     * @return string  HTML
     */
    private function edit()
    {
        $item = $this->dbItem('', arg('id', 'int'));
        $item['section'] = explode('|', $item['section']);
        $sections = $this->menuBranch();
        array_unshift($sections[0], 'ВСЕ РАЗДЕЛЫ');
        array_unshift($sections[1], 'all');
        $form = array(
            'name' => 'formEdit',
            'caption' => 'Изменить блок',
            'width' => '95%',
            'fields' => array (
                array ('type' => 'hidden','name'=>'update', 'value'=>$item['id']),
                array ('type' => 'edit', 'name' => 'caption', 'label' => 'Заголовок', 'width' => '100%',
                    'maxlength' => '255', 'pattern'=>'/\S+/',
                    'errormsg'=>'Заголовок не может быть пустым!'),
                array ('type' => 'edit', 'name' => 'description', 'label' => 'Описание', 'width' => '100%',
                    'maxlength' => '255'),
                array ('type' => 'listbox', 'name' => 'section', 'label' => 'Разделы', 'height'=> 5,
                    'items'=>$sections[0], 'values'=>$sections[1]),
                array ('type' => 'edit', 'name' => 'priority', 'label' => 'Приоритет', 'width' => '20px',
                    'comment' => 'Большие значения - больший приоритет', 'default'=>0,
                    'pattern'=>'/^\d+$/', 'errormsg'=>'Приоритет задается только цифрами!'),
                array ('type' => 'edit', 'name' => 'block', 'label' => 'Блок', 'width' => '100px',
                    'maxlength' => 31, 'pattern'=>'/^\S+$/',
                    'errormsg'=>'Имя блока не может быть пустым или содержать пробелы!'),
                array ('type' => 'select', 'name' => 'target', 'label' => 'Область',
                    'items' => array('Отрисованная страница','Шаблон страницы'),
                    'values' => array('page','template')),
                array ('type' => 'html', 'name' => 'content', 'label' => 'Содержимое', 'height' => '300px'),
                array ('type' => 'checkbox', 'name' => 'active', 'label' => 'Активировать'),
            ),
            'buttons' => array('ok', 'apply', 'cancel'),
        );

        /** @var TAdminUI $page */
        $page = Eresus_Kernel::app()->getPage();
        $result = $page->renderForm($form, $item);
        return $result;
    }

    /**
     * Переключает активность блока
     *
     * @param int $id  идентификатор блока
     *
     * @return void
     */
    private function toggle($id)
    {
        $q = DB::getHandler()->createUpdateQuery();
        $e = $q->expr;
        $q->update($this->table['name'])
            ->set('active', $e->not('active'))
            ->where($e->eq('id', $id));
        DB::execute($q);

        /** @var TAdminUI $page */
        $page = Eresus_Kernel::app()->getPage();
        HTTP::redirect(str_replace('&amp;', '&', $page->url()));
    }

    /**
     * Удаление
     *
     * Перенесено из TListContentPlugin
     *
     * @param int $id
     */
    private function delete($id)
    {
        $this->dbDelete('', $id);
        HTTP::goback();
    }

    /**
     * Подставляет блоки в текст
     *
     * @param string $html   исходный текст
     * @param string $target  "page" или "template"
     *
     * @return string Обработанный текст
     */
    private function renderBlocks($html, $target)
    {
        $this->recursion++;

        if ($this->recursion <= $this->recursionLimit)
        {
            /** @var TAdminUI $page */
            $page = Eresus_Kernel::app()->getPage();

            $table = $this->getTable();

            preg_match_all('/\$\(Blocks:([^\)]+)\)/', $html, $blocks);
            foreach ($blocks[1] as $blockName)
            {
                $block = $table->getAppropriateBlock($blockName, $page->id, $target);
                if (null !== $block)
                {
                    $replace = $this->renderBlocks(trim($block['content']), $target);
                    $html = str_replace('$(Blocks:'.$blockName.')', $replace, $html);
                }
            }
        }

        $this->recursion--;
        return $html;
    }

    /**
     * Возвращает таблицу блоков
     * @return Blocks_Entity_Table_Block
     * @since 4.01
     */
    private function getTable()
    {
        if (null === $this->blocksTable)
        {
            $this->blocksTable = new Blocks_Entity_Table_Block($this);
        }
        return $this->blocksTable;
    }
}

