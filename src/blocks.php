<?php
/**
 * Blocks
 *
 * ���������� ���������� �������
 *
 * @version 3.02
 *
 * @copyright 2005, ProCreat Systems, http://procreat.ru/
 * @copyright 2007, Eresus Group, http://eresus.ru/
 * @copyright 2010, ��� "��� �����", http://dvaslona.ru/
 * @license http://www.gnu.org/licenses/gpl.txt	GPL License 3
 * @author ������ ������������ <mihalych@vsepofigu.ru>
 *
 * ������ ��������� �������� ��������� ����������� ������������. ��
 * ������ �������������� �� �/��� �������������� � ������������ �
 * ��������� ������ 3 ���� �� ������ ������ � ��������� ����� �������
 * ������ ����������� ������������ �������� GNU, �������������� Free
 * Software Foundation.
 *
 * �� �������������� ��� ��������� � ������� �� ��, ��� ��� ����� ���
 * ��������, ������ �� ������������� �� ��� ������� ��������, � ���
 * ����� �������� ��������� ��������� ��� ������� � ����������� ���
 * ������������� � ���������� �����. ��� ��������� ����� ���������
 * ���������� ������������ �� ����������� ������������ ��������� GNU.
 *
 * @package Blocks
 *
 * $Id$
 */

/**
 * ����� �������
 *
 * @package Blocks
 */
class Blocks extends Plugin
{
	/**
	 * ��������� ������ ����
	 * @var string
	 */
	public $kernel = '2.12';

	/**
	 * �������� �������
	 * @var string
	 */
	public $title = '�����';

	/**
	 * ��� �������
	 * @var string
	 */
	public $type = 'client,admin';

	/**
	 * ������ �������
	 * @var string
	 */
	public $version = '3.02a';

	/**
	 * �������� �������
	 * @var string
	 */
	public $description = '������� ���������� ���������� �������';

	/**
	 * �������� ������� ������
	 * @var array
	 */
	public $table = array (
		'name' => 'blocks',
		'key'=> 'id',
		'sortMode' => 'id',
		'sortDesc' => false,
		'columns' => array(
			array('name' => 'caption', 'caption' => '��������'),
			array('name' => 'block', 'caption' => '����', 'align'=> 'right'),
			array('name' => 'description', 'caption' => '��������'),
			array('name' => 'priority', 'caption' =>
				'<span title="���������" style="cursor: default;">&nbsp;&nbsp;*</span>', 'align'=>'center'),
		),
		'controls' => array (
			'delete' => '',
			'edit' => '',
			'toggle' => '',
		),
		'tabs' => array(
			'width'=>'180px',
			'items'=>array(
				array('caption'=>'�������� ����', 'name'=>'action', 'value'=>'create')
			),
		)
	);

	/**
	 * �����������
	 *
	 * @return Blocks
	 */
	public function __construct()
	{
		parent::__construct();
		$this->listenEvents('adminOnMenuRender', 'clientOnContentRender', 'clientOnPageRender');
	}
	//-----------------------------------------------------------------------------

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
			PRIMARY KEY	(`id`),
			KEY `active` (`active`),
			KEY `section` (`section`),
			KEY `block` (`block`),
			KEY `target` (`target`)
		');
	}
	//-----------------------------------------------------------------------------

	/**
	 * ��������� ����� ������ � ���� ������������
	 *
	 * @return void
	 */
	public function adminOnMenuRender()
	{
		$GLOBALS['page']->addMenuItem(admExtensions, array(
			'access' => EDITOR,
			'link' => $this->name,
			'caption' => $this->title,
			'hint' => $this->description
		));
	}
	//-----------------------------------------------------------------------------

	/**
	 * ���������� �������� ���������� ����������
	 *
	 * @return string  HTML
	 */
	public function adminRender()
	{
		global $page;

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
			$result = $this->update();
		}
		elseif (arg('toggle'))
		{
			$result = $this->toggle(arg('toggle', 'int'));
		}
		elseif (arg('delete'))
		{
			$result = $this->delete(arg('delete', 'int'));
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
					$result = $this->insert();
				break;

				default:
					$result = $page->renderTable($this->table);
				break;
			}
		}
		return $result;
	}
	//-----------------------------------------------------------------------------

	/**
	 * ����������� ����� � ������
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function clientOnContentRender($text)
	{
		$page = $GLOBALS['page'];
		$page->template = $this->renderBlocks($page->template, 'template');
		return $text;
	}
	//-----------------------------------------------------------------------------

	/**
	 * ����������� ����� � ������������ ��������
	 *
	 * @param string $text	���������� ��������
	 *
	 * @return string
	 */
	public function clientOnPageRender($text)
	{
		$text = $this->renderBlocks($text, 'page');
		return $text;
	}
	//-----------------------------------------------------------------------------

	/**
	 * ������ ������ �������� ��� �������� ���������� � ��������� �����
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
	//-----------------------------------------------------------------------------

	/**
	 * ��������� ���� � ��
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
	//-----------------------------------------------------------------------------

	/**
	 * �������� ���� � ��
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
	//-----------------------------------------------------------------------------

	/**
	 * ���������� ������ ���������� �����
	 *
	 * @return string	HTML
	 */
	private function create()
	{
		$sections = array(array(), array());
		$sections = $this->menuBranch();
		array_unshift($sections[0], '��� �������');
		array_unshift($sections[1], 'all');
		$form = array(
			'name' => 'formCreate',
			'caption' => '�������� ����',
			'width' => '95%',
			'fields' => array (
				array ('type'=>'hidden','name'=>'action', 'value'=>'insert'),
				array ('type' => 'edit', 'name' => 'caption', 'label' => '���������', 'width' => '100%',
					'maxlength' => '255', 'pattern'=>'/\S+/', 'errormsg'=>'��������� �� ����� ���� ������!'),
				array ('type' => 'edit', 'name' => 'description', 'label' => '��������', 'width' => '100%',
					'maxlength' => '255'),
				array ('type' => 'listbox', 'name' => 'section', 'label' => '�������', 'height'=> 5,
					'items'=>$sections[0], 'values'=>$sections[1]),
				array ('type' => 'edit', 'name' => 'priority', 'label' => '���������', 'width' => '20px',
					'comment' => '������� �������� - ������� ���������', 'value'=>0,
					'pattern'=>'/^\d+$/', 'errormsg'=>'��������� �������� ������ �������!'),
				array ('type' => 'edit', 'name' => 'block', 'label' => '����', 'width' => '100px',
					'maxlength' => 31, 'pattern'=>'/^\S+$/',
					'errormsg'=>'��� ����� �� ����� ���� ������ ��� ��������� �������!'),
				array ('type' => 'select', 'name' => 'target', 'label' => '�������',
					'items' => array('������������ ��������','������ ��������'),
					'values' => array('page','template')),
				array ('type' => 'html', 'name' => 'content', 'label' => '����������', 'height' => '300px'),
			),
			'buttons' => array('ok', 'cancel'),
		);

		$result = $GLOBALS['page']->renderForm($form);
		return $result;
	}
	//-----------------------------------------------------------------------------

	/**
	 * ���������� ������ ��������� �����
	 *
	 * @return string  HTML
	 */
	private function edit()
	{
		$item = $this->dbItem('', arg('id', 'int'));
		$item['section'] = explode('|', $item['section']);
		$sections = array(array(), array());
		$sections = $this->menuBranch();
		array_unshift($sections[0], '��� �������');
		array_unshift($sections[1], 'all');
		$form = array(
			'name' => 'formEdit',
			'caption' => '�������� ����',
			'width' => '95%',
			'fields' => array (
				array ('type' => 'hidden','name'=>'update', 'value'=>$item['id']),
				array ('type' => 'edit', 'name' => 'caption', 'label' => '���������', 'width' => '100%',
					'maxlength' => '255', 'pattern'=>'/\S+/',
					'errormsg'=>'��������� �� ����� ���� ������!'),
				array ('type' => 'edit', 'name' => 'description', 'label' => '��������', 'width' => '100%',
					'maxlength' => '255'),
				array ('type' => 'listbox', 'name' => 'section', 'label' => '�������', 'height'=> 5,
					'items'=>$sections[0], 'values'=>$sections[1]),
				array ('type' => 'edit', 'name' => 'priority', 'label' => '���������', 'width' => '20px',
					'comment' => '������� �������� - ������� ���������', 'default'=>0,
					'pattern'=>'/^\d+$/', 'errormsg'=>'��������� �������� ������ �������!'),
				array ('type' => 'edit', 'name' => 'block', 'label' => '����', 'width' => '100px',
					'maxlength' => 31, 'pattern'=>'/^\S+$/',
					'errormsg'=>'��� ����� �� ����� ���� ������ ��� ��������� �������!'),
				array ('type' => 'select', 'name' => 'target', 'label' => '�������',
					'items' => array('������������ ��������','������ ��������'),
					'values' => array('page','template')),
				array ('type' => 'html', 'name' => 'content', 'label' => '����������', 'height' => '300px'),
				array ('type' => 'checkbox', 'name' => 'active', 'label' => '������������'),
			),
			'buttons' => array('ok', 'apply', 'cancel'),
		);

		$result = $GLOBALS['page']->renderForm($form, $item);
		return $result;
	}
	//-----------------------------------------------------------------------------

	/**
	 * ����������� ���������� �����
	 *
	 * @param int $id  ������������� �����
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

		HTTP::redirect(str_replace('&amp;', '&', $GLOBALS['page']->url()));
	}
	//-----------------------------------------------------------------------------

	/**
	 * ��������
	 *
	 * ���������� �� TListContentPlugin
	 *
	 * @param int $id
	 */
	private function delete($id)
	{
		$this->dbDelete('', $id);
		HTTP::goback();
	}
	//-----------------------------------------------------------------------------

	/**
	 * ����������� ����� � �����
	 *
	 * @param string $source	�������� �����
	 * @param string $target	"page" ��� "template"
	 *
	 * @return string	������������ �����
	 */
	private function renderBlocks($source, $target)
	{
		global $page;

		// ��� ���������� ����� ��������� ������� � �����
		$blockName = null;

		$q = DB::getHandler()->createSelectQuery();
		$e = $q->expr;
		$q->select('*')
			->from($this->__table(''))
			->where(
				$e->lAnd(
					$e->eq('active', $q->bindValue(true)),
					$e->lOr(
						$e->like('section', $q->bindValue('%|all|%')),
						$e->like('section', $q->bindValue('%|' . $page->id . '|%'))
					),
					$e->eq('block', $q->bindParam($blockName)),
					$e->eq('target', $q->bindValue($target))
				)
			)
			->orderBy('priority', ezcQuerySelect::DESC);

		preg_match_all('/\$\(Blocks:([^\)]+)\)/', $source, $blocks);
		foreach ($blocks[1] as $block)
		{
			$blockName = $block;
			try
			{
				$item = DB::fetch($q);
			}
			catch (DBQueryException $e)
			{
				Core::logException($e);
				$item = null;
			}

			if ($item)
			{
				$source = str_replace('$(Blocks:'.$block.')', trim($item['content']), $source);
			}
		}
		return $source;
	}
	//-----------------------------------------------------------------------------
}
