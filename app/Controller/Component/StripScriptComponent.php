<?php

App::uses('Component', 'Controller');
App::uses('Sanitize', 'Utility');

/**
 * サニタイズ処理
 */
class StripScriptComponent extends Component {
	// コントローラー参照
	protected $controller = null;

	/**
	 * コントローラの beforeFilter() の前に実行される
	 * @param Controller controller コンローラ参照
	 */
	public function initialize(Controller $controller) {
		parent::initialize($controller);
		$this->controller =& $controller;
	}

	/**
	 * コントローラの beforeFilter() の後に実行される
	 * @param Controller controller コンローラ参照
	 */
	public function startup(Controller $controller) {
		parent::startup($controller);

		if (isset($this->controller->request->data) &&
			is_array($this->controller->request->data)) {
			$this->controller->request->data = $this->sanitize($this->controller->request->data);
		}
	}

	/**
	 * サニタイズ
	 * デフォルトでリクエストされたデータにサニタイズをする。
	 * モデルやコントローラにカラムに除外設定を記述できる。
	 * 変換情報は基本的にモデルに記述すること。
	 * モデルよりもコントローラの情報が優先される。
	 * @param mixed $request リクエスト情報
	 * @param string $field フィールド情報
	 * @return mixed 変換した値
	 */
	protected function sanitize($request, $field = '') {
		if (is_array($request)) {
			foreach ($request as $key => $value) {
				if ($field != '') {
					$new_field = $field . '.' . $key;
				} else {
					$new_field = $key;
				}
				$request[$key] = $this->sanitize($value, $new_field);
			}
		} elseif (is_string($request) ||
			is_numeric($request)) {
			return $this->getSanitizeValue($request, $field);
		}
		// object, null, boolean はそのまま返す
		return $request;
	}

	/**
	 * サニタイズ後の値を取得する
	 * @param string $request 文字列
	 * @param string $field フィールド情報
	 * @return string 変換後の文字列
	 */
	protected function getSanitizeValue($request, $field) {
		$sanitize_type = $this->getSanitizeTypeString($field);
		if ($sanitize_type === '') {
			return $request;
		} else {
			// <script>, <img>, <link>, <style> を削除する
			return Sanitize::stripScripts($request);
		}
	}

	/**
	 * サニタイズ設定情報を取得する
	 * @param string $field フィールド情報
	 * @return boolean|string  string: 空文字は変換なし
	 */
	protected function getSanitizeTypeString($field) {
		// デフォルトの値
		$result = true;
		// フィールド情報が設定されてない場合
		if ($field === '') {
			return $result;
		}

		// モデルの設定情報を調べる
		$explode_field = explode('.', $field);
		// Model.field or
		// Model.0.field
		if (count($explode_field) > 1 &&
			!preg_match("/^[0-9]$/", $explode_field[0], $m)) {
			$model_name = $explode_field[0];
			$column = array_pop($explode_field);
		} else {
			$model_name = '';
			$column = array_pop($explode_field);
		}
		// クラス名が見つからない &&
		// uses 変数にモデル名が含まれている場合
		if (!class_exists($model_name) &&
			(in_array($model_name, $this->controller->uses) ||
			array_key_exists($model_name, $this->controller->uses))) {
			// モデルを読み込む
			App::uses($model_name, 'Model');
		}
		// モデルクラスが読み込まれている &&
		// 変数が見つかる場合
		if (class_exists($model_name) &&
			$model_name::getStripScript($column) !== false) {
			$result = $model_name::getStripScript($column);
		}

		// Controller の設定情報を調べる
		if (isset($this->settings) &&
			is_array($this->settings)) {
			// 設定値を取得
			if (strlen($model_name) > 0 &&
				array_key_exists($model_name, $this->settings) &&
				array_key_exists($column, $this->settings[$model_name])) {
				// モデルがある
				$result = $this->settings[$model_name][$column];
			} elseif (array_key_exists($column, $this->settings)) {
				// 変数のみ
				$result = $this->settings[$column];
			}
		}
		if ($result === false ||
			is_null($result)) {
			// 該当する変数が false の場合は変換なしに設定する
			$result = '';
		}
		return $result;
	}
}
