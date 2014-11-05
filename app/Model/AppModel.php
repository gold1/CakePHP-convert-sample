<?php
/**
 * Application model for CakePHP.
 *
 * This file is application-wide model file. You can put all
 * application-wide model-related methods here.
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
 * @package       app.Model
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Model', 'Model');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AppModel extends Model {
	// 変換情報
	protected static $convertList = array();
	// サニタイズ情報
	protected static $stripScript = array();

	/**
	 * 変換情報を取得する
	 * @param string $column カラム名
	 * @return array|false  成功したら配列で返す。失敗したら false を返す。
	 */
	public static function getConvertList($column = ''){
		if ($column != '') {
			if (array_key_exists($column, static::$convertList)) {
				return static::$convertList[$column];
			} else {
				return false;
			}
		}
		return static::$convertList;
	}

	/**
	 * サニタイズ情報を取得する
	 * @param string $column カラム名
	 * @return array|false  成功したら配列で返す。失敗したら false を返す。
	 */
	public static function getStripScript($column = '') {
		if ($column != '') {
			if (array_key_exists($column, static::$stripScript)) {
				return static::$stripScript[$column];
			} else {
				return false;
			}
		}
		return static::$stripScript;
	}
}
