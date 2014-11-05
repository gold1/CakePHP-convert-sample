<?php

App::uses('Component', 'Controller');

/**
 * リクエストの変換処理
 */
class ConverterComponent extends Component {
	// コントローラー参照
	protected $controller = null;

	/**
	 * 初期化
	 * @param Controller $controller コンローラ参照
	 */
	public function initialize(Controller $controller) {
		parent::initialize($controller);
		$this->controller =& $controller;
	}

	/**
	 * 初期化
	 * @param Controller $controller コンローラ参照
	 */
	public function startup(Controller $controller) {
		parent::startup($controller);

		if (isset($this->controller->request->data) &&
			is_array($this->controller->request->data)) {
			$this->controller->request->data = $this->convert($this->controller->request->data);
		}
	}

	/**
	 * 値を変換する
	 * リクエストされたデータに対してデフォルトで trim と改行削除を行う。
	 * trim 以外の変換情報を設定したい場合はモデルかコントローラに記述する。
	 * モデルに書けばすべてのコントローラで適用されるため、基本的には
	 * モデルに記述すること。モデルに属さない変数の場合はコントローラに記述する。
	 * モデルよりもコントローラの情報が優先される。
	 * 
	 * @param mixed $request リクエスト情報
	 * @param string $field フィールド情報
	 * @return mixed 変換した値
	 */
	protected function convert($request, $field = '') {
		if (is_array($request)) {
			foreach ($request as $key => $value) {
				if ($field != '') {
					$new_field = $field . '.' . $key;
				} else {
					$new_field = $key;
				}
				$request[$key] = $this->convert($value, $new_field);
			}
		} elseif (is_string($request) ||
			is_numeric($request)) {
			return $this->getConvertValue((string)$request, $field);
		}
		// object, null, boolean はそのまま返す
		return $request;
	}

	/**
	 * 変換後の値を取得する
	 * @param string $request 文字列
	 * @param string $field フィールド情報
	 * @return string 変換後の文字列
	 */
	protected function getConvertValue($request, $field) {
		$convert_type = $this->getConvertTypeString($field);
		$model_name = $this->getModelName($field);

		// 空文字のときは変換なし
		if ($convert_type === '') {
			return $request;
		} elseif (!is_array($convert_type)) {
			throw new Exception('パラメータが不正です。');
		}
		foreach ($convert_type as $type) {
			$request = $this->convertByType($request, $type, $model_name);
		}
		return $request;
	}

	/**
	 * 変換情報を取得する
	 * @param string $field フィールド情報
	 * @return array|string 見つかった変換情報
	 */
	public function getConvertTypeString($field) {
		// デフォルトの値（trim, 改行削除）
		$result = $default_result = array('trim', 'line');
		if ($field === '') {
			return $result;
		}

		$explode_field = explode('.', $field);
		$column = array_pop($explode_field);
		$model_name = $this->getModelName($field);

		// クラス名が見つからない &&
		// uses 変数にモデル名が含まれている場合
		if (!class_exists($model_name) &&
			(in_array($model_name, $this->controller->uses) ||
			array_key_exists($model_name, $this->controller->uses))) {
			App::uses($model_name, 'Model');
		}
		// モデルクラスが読み込まれている &&
		// 変数が見つかる場合
		if (class_exists($model_name) &&
			$model_name::getConvertList($column) !== false) {
			$result = $model_name::getConvertList($column);
		}

		if (isset($this->settings) &&
			is_array($this->settings)) {
			if (strlen($model_name) > 0 &&
				array_key_exists($model_name, $this->settings) &&
				array_key_exists($column, $this->settings[$model_name])) {
				// モデルがある
				// 値がもし配列の場合は中身が文字列である
				$this->checkSubArrayString($this->settings[$model_name][$column]);
				$result = $this->settings[$model_name][$column];
			} elseif (array_key_exists($column, $this->settings)) {
				// 変数のみ
				// 値がもし配列の場合は中身が文字列である
				$this->checkSubArrayString($this->settings[$column]);
				$result = $this->settings[$column];
			}
		}
		if ($result === true) {
			// 該当する変数が true の場合はデフォルトの値を設定する
			$result = $default_result;
		} elseif ($result === false ||
			is_null($result)) {
			// 該当する変数が false, null の場合は変換なしに設定する
			$result = '';
		}
		return $result;
	}

	/**
	 * モデル名を取得する
	 * @param string $field フィールド情報
	 * @return string モデル名
	 */
	protected function getModelName($field) {
		$explode_field = explode('.', $field);
		// Model.field or
		// Model.0.field
		if (count($explode_field) > 1 &&
			!preg_match("/^[0-9]$/", $explode_field[0], $m)) {
			$model_name = $explode_field[0];
		} else {
			$model_name = '';
		}
		return $model_name;
	}

	/**
	 * 種類に応じてコンバートする
	 * @param string $str 文字列
	 * @param string $type 変換種別
	 * @param string $model_name モデル名
	 * @return string 変換した値
	 */
	protected function convertByType($str, $type, $model_name) {
		switch ($type) {
		case 'trim':
			$str = trim($str);
			break;
		case 'rtrim':
			$str = rtrim($str);
			break;
		case 'line':
			$str = str_replace(array("\r", "\n"), "", $str);
			break;
		case 'hiragana':
			$str = mb_convert_kana($str, 'HVc');
			break;
		case 'katakana':
			$str = mb_convert_kana($str, 'KVC');
			break;
		case 'hankakuKatakana':
			$str = mb_convert_kana($str, 'hk');
			break;
		case 'katakanaToZenkaku':
			$str = mb_convert_kana($str, 'KV');
			break;
		case 'katakanaToHankaku':
			$str = mb_convert_kana($str, 'k');
			break;
		case 'hankaku':
			$str = mb_convert_kana($str, 'hkas');
			break;
		case 'zenkaku':
			$str = mb_convert_kana($str, 'KVAS');
			break;
		case 'ucfirst':
			$str = ucfirst(strtolower($str));
			break;
		case 'strtoupper':
			$str = strtoupper($str);
			break;
		case 'strtolower':
			$str = strtolower($str);
			break;
		default:
			if ($model_name == '') {
				throw new Exception('パラメータが不正です。');
			}
			App::uses($model_name, 'Model');
			// モデルクラスが読み込まれている &&
			// 関数が見つかる場合
			if (class_exists($model_name) &&
				method_exists($model_name, $type)) {
				$model = ClassRegistry::init($model_name);
				$str = $model->$type($str);
			} else {
				throw new Exception('パラメータが不正です。');
			}
			break;
		}
		return $str;
	}

	/**
	 * 値がもし配列の場合は中身が文字列・数値であるか確認する
	 * @param mixed $value 値
	 */
	protected function checkSubArrayString($value) {
		// 値が配列以外の場合は、調べる必要がない
		if (!is_array($value)) {
			return;
		}
		foreach ($value as $sub_value) {
			if (!is_numeric($sub_value) &&
				!is_string($sub_value)) {
				throw new Exception('パラメータが不正です。');
			}
		}
	}
}
