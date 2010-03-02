<?php
/**
 * Blocks
 *
 * Eresus 2
 *
 * Управление текстовыми блоками
 *
 * @version 2.05
 *
 * @copyright 2005, ProCreat Systems, http://procreat.ru/
 * @copyright 2007, Eresus Group, http://eresus.ru/
 * @copyright 2010, ООО "Два слона", http://dvaslona.ru/
 * @license http://www.gnu.org/licenses/gpl.txt  GPL License 3
 * @author Mikhail Krasilnikov <mk@procreat.ru>
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
 *
 * $Id$
 */

/**
 * Класс плагина
 *
 * @package Blocks
 */
class TBlocks extends TListContentPlugin
{
	/**
	 * Имя плагина
	 * @var string
	 */
  public $name = 'blocks';

  /**
   * Требуемая версия ядра
   * @var string
   */
  public $kernel = '2.12';

  /**
   * Название плагина
   * @var string
   */
  public $title = 'Блоки';

  /**
   * Тип плагина
   * @var string
   */
  public $type = 'client,admin';

  /**
   * Версия плагина
   * @var string
   */
  public $version = '2.05a';

  /**
   * Описание плагина
   * @var string
   */
  public  $description = 'Система управления текстовыми блоками';

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
      array('name' => 'priority', 'caption' =>
      	'<span title="Приоритет" style="cursor: default;">&nbsp;&nbsp;*</span>', 'align'=>'center'),
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
    ),
    'sql' => "(
      `id` int(10) unsigned NOT NULL auto_increment,
      `caption` varchar(255) default NULL,
      `active` tinyint(1) unsigned default NULL,
      `section` varchar(255) default NULL,
      `priority` int(10) unsigned default NULL,
      `block` varchar(31) default NULL,
      `target` varchar(63) default NULL,
      `content` text,
      PRIMARY KEY  (`id`),
      KEY `active` (`active`),
      KEY `section` (`section`),
      KEY `block` (`block`),
      KEY `target` (`target`)
    ) TYPE=MyISAM COMMENT='Content blocks';",
  );

  /**
   * Конструктор
   *
   * @return TBlocks
   */
  public function TBlocks()
  {
  	global $plugins;

    parent::TListContentPlugin();
    if (defined('CLIENTUI')) {
      $plugins->events['clientOnContentRender'][] = $this->name;
      $plugins->events['clientOnPageRender'][] = $this->name;
    } else $plugins->events['adminOnMenuRender'][] = $this->name;
  }
  //-----------------------------------------------------------------------------

  /**
   * ???
   * @param $owner
   * @param $level
   * @return unknown_type
   */
  public function menuBranch($owner = 0, $level = 0)
  {
  	global $db;

    $result = array(array(), array());
    $items = $db->select('`pages`', "(`access`>='".USER."')AND(`owner`='".$owner."') AND (`active`='1')", "`position`", false, "`id`,`caption`");
    if (count($items)) foreach($items as $item) {
      $result[0][] = str_repeat('- ', $level).$item['caption'];
      $result[1][] = $item['id'];
      $sub = $this->menuBranch($item['id'], $level+1);
      if (count($sub[0])) {
        $result[0] = array_merge($result[0], $sub[0]);
        $result[1] = array_merge($result[1], $sub[1]);
      }
    }
    return $result;
  }
  //-----------------------------------------------------------------------------

  /**
   * ???
   * @return unknown_type
   */
  public function insert()
  {
  	global $db, $request;

    $item = GetArgs($db->fields($this->table['name']));
    if (isset($item['section'])) $item['section'] = ($item['section'] != 'all')?':'.implode(':', $request['arg']['section']).':':'all';
    $item['content'] = arg('content', 'dbsafe');
    $item['active'] = true;
    $db->insert($this->table['name'], $item);
    sendNotify('Добавлен блок: '.$item['caption']);
    HTTP::redirect($request['arg']['submitURL']);
  }
  //-----------------------------------------------------------------------------

  /**
   * ???
   * @return unknown_type
   */
  public function update()
  {
  global $db, $request;

    $item = $db->selectItem($this->table['name'], "`id`='".$request['arg']['update']."'");
    $item = GetArgs($item);
    $item['section'] = ($item['section'] != 'all')?':'.implode(':', $request['arg']['section']).':':'all';
    $item['content'] = arg('content', 'dbsafe');
    $db->updateItem($this->table['name'], $item, "`id`='".$request['arg']['update']."'");
    $item = $db->selectItem($this->table['name'], "`id`='".$request['arg']['update']."'");
    sendNotify('Изменен блок: '.$item['caption']);
    HTTP::redirect($request['arg']['submitURL']);
  }
  //-----------------------------------------------------------------------------

  /**
   * ???
   * @return unknown_type
   */
  public function create()
  {
  global $page, $db;

    $sections = array(array(), array());
    $sections = $this->menuBranch();
    array_unshift($sections[0], 'ВСЕ РАЗДЕЛЫ');
    array_unshift($sections[1], 'all');
    $form = array(
      'name' => 'formCreate',
      'caption' => 'Добавить блок',
      'width' => '95%',
      'fields' => array (
        array ('type'=>'hidden','name'=>'action', 'value'=>'insert'),
        array ('type' => 'edit', 'name' => 'caption', 'label' => 'Заголовок', 'width' => '100%', 'maxlength' => '255', 'pattern'=>'/.+/', 'errormsg'=>'Заголовок не может быть пустым!'),
        array ('type' => 'listbox', 'name' => 'section', 'label' => 'Разделы', 'height'=> 5,'items'=>$sections[0], 'values'=>$sections[1]),
        array ('type' => 'edit', 'name' => 'priority', 'label' => 'Приоритет', 'width' => '20px', 'comment' => 'Большие значения - больший приоритет', 'value'=>0, 'pattern'=>'/\d+/', 'errormsg'=>'Приоритет задается только цифрами!'),
        array ('type' => 'edit', 'name' => 'block', 'label' => 'Блок', 'width' => '100px', 'maxlength' => 31),
        array ('type' => 'select', 'name' => 'target', 'label' => 'Область', 'items' => array('Отрисованная страница','Шаблон страницы'), 'values' => array('page','template')),
        array ('type' => 'html', 'name' => 'content', 'label' => 'Содержимое', 'height' => '300px'),
      ),
      'buttons' => array('ok', 'cancel'),
    );

    $result = $page->renderForm($form);
    return $result;
  }
  //-----------------------------------------------------------------------------

  /**
   * ???
   * @return unknown_type
   */
  public function edit()
  {
  global $page, $db, $request;

    $item = $db->selectItem($this->table['name'], "`id`='".$request['arg']['id']."'");
    $item['section'] = explode(':', $item['section']);
    $sections = array(array(), array());
    $sections = $this->menuBranch();
    array_unshift($sections[0], 'ВСЕ РАЗДЕЛЫ');
    array_unshift($sections[1], 'all');
    $form = array(
      'name' => 'formEdit',
      'caption' => 'Изменить блок',
      'width' => '95%',
      'fields' => array (
        array ('type' => 'hidden','name'=>'update', 'value'=>$item['id']),
        array ('type' => 'edit', 'name' => 'caption', 'label' => 'Заголовок', 'width' => '100%', 'maxlength' => '255', 'pattern'=>'/.+/', 'errormsg'=>'Заголовок не может быть пустым!'),
        array ('type' => 'listbox', 'name' => 'section', 'label' => 'Разделы', 'height'=> 5,'items'=>$sections[0], 'values'=>$sections[1]),
        array ('type' => 'edit', 'name' => 'priority', 'label' => 'Приоритет', 'width' => '20px', 'comment' => 'Большие значения - больший приоритет', 'default'=>0, 'pattern'=>'/\d+/', 'errormsg'=>'Приоритет задается только цифрами!'),
        array ('type' => 'edit', 'name' => 'block', 'label' => 'Блок', 'width' => '100px', 'maxlength' => 31),
        array ('type' => 'select', 'name' => 'target', 'label' => 'Область', 'items' => array('Отрисованная страница','Шаблон страницы'), 'values' => array('page','template')),
        array ('type' => 'html', 'name' => 'content', 'label' => 'Содержимое', 'height' => '300px'),
        array ('type' => 'checkbox', 'name' => 'active', 'label' => 'Активировать'),
      ),
      'buttons' => array('ok', 'apply', 'cancel'),
    );

    $result = $page->renderForm($form, $item);
    return $result;
  }
  //-----------------------------------------------------------------------------

  /**
   * ???
   * @return unknown_type
   */
  public function adminRender()
  {
  global $db, $page, $user, $request, $session;

    $result = '';
    if (isset($request['arg']['id'])) {
      $item = $db->selectItem($this->table['name'], "`".$this->table['key']."` = '".$request['arg']['id']."'");
      $page->title .= empty($item['caption'])?'':' - '.$item['caption'];
    }
    if (isset($request['arg']['update']) && isset($this->table['controls']['edit'])) {
      if (method_exists($this, 'update')) $result = $this->update(); else $session['errorMessage'] = sprintf(errMethodNotFound, 'update', get_class($this));
    } elseif (isset($request['arg']['toggle']) && isset($this->table['controls']['toggle'])) {
      if (method_exists($this, 'toggle')) $result = $this->toggle($request['arg']['toggle']); else $session['errorMessage'] = sprintf(errMethodNotFound, 'toggle', get_class($this));
    } elseif (isset($request['arg']['delete']) && isset($this->table['controls']['delete'])) {
      if (method_exists($this, 'delete')) $result = $this->delete($request['arg']['delete']); else $session['errorMessage'] = sprintf(errMethodNotFound, 'delete', get_class($this));
    } elseif (isset($request['arg']['id']) && isset($this->table['controls']['edit'])) {
      if (method_exists($this, 'edit')) $result = $this->edit(); else $session['errorMessage'] = sprintf(errMethodNotFound, 'edit', get_class($this));
    } elseif (isset($request['arg']['action'])) switch ($request['arg']['action']) {
      case 'create': $result = $this->create(); break;
      case 'insert':
        if (method_exists($this, 'insert')) $result = $this->insert();
        else $session['errorMessage'] = sprintf(errMethodNotFound, 'insert', get_class($this));
      break;
    } else {
      $result = $page->renderTable($this->table);
    }
    return $result;
  }
  //-----------------------------------------------------------------------------

  /**
   * ???
   * @param $source
   * @param $target
   * @return unknown_type
   */
  public function renderBlocks($source, $target)
  {
    global $db, $page, $request;

    preg_match_all('/\$\(Blocks:([^\)]+)\)/', $source, $blocks);
    foreach($blocks[1] as $block) {
      $sql = "(`active`=1) AND (`section` LIKE '%:".$page->id.":%' OR `section` = ':all:') AND (`block`='".$block."') AND (`target` = '".$target."')";
      $item = $db->select($this->name, $sql, '`priority`', true);
      if (count($item)) $source = str_replace('$(Blocks:'.$block.')', trim($item[0]['content']), $source);
    }
    return $source;
  }
  //-----------------------------------------------------------------------------

  /**
   * ???
   * @return unknown_type
   */
  public function adminOnMenuRender()
  {
    global $page;

    $page->addMenuItem(admExtensions, array ('access'  => EDITOR, 'link'  => $this->name, 'caption'  => $this->title, 'hint'  => $this->description));
  }
  //-----------------------------------------------------------------------------

  /**
   * ???
   * @param $text
   * @return unknown_type
   */
  public function clientOnContentRender($text)
  {
    global $page;
    $page->template = $this->renderBlocks($page->template, 'template');
    return $text;
  }
  //-----------------------------------------------------------------------------

  /**
   * ???
   * @param $text
   * @return unknown_type
   */
  public function clientOnPageRender($text)
  {
    $text = $this->renderBlocks($text, 'page');
    return $text;
  }
  //-----------------------------------------------------------------------------
}
