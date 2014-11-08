<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppController', 'Controller');
App::uses('ConverterComponent', 'Controller/Component');
App::uses('StripScriptComponent', 'Controller/Component');

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package		app.Controller
 * @link		http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class ItController extends AppController {
	public $name = 'It';
	public $uses = array(
		'It'
	);
	public $components = array(
		'Converter' => array(
			'It' => array(
				'note' => array('rtrim', 'line', 'hankaku'),
			),
			'note2' => array('cutSpace', 'zenkaku'),
		),
		'StripScript',
	);

	public function form() {
		if ($this->request->is('get')) {
			$this->request->data[$this->It->alias] = array(
				'name'  => '  あｱ  ',
				'note'  => "あア１Ａ\n<img src=\"\" />\n<script>alert(1);</script>  ",
				'note2' => 'ｱ - 1 a A',
			);
		}
		if ($this->request->is('post')) {
			$this->It->set($this->request->data[$this->It->alias]);
			return $this->render('finish');
		}
	}
}
