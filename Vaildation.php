<?php

namespace Lhp\Form_validation;

class Vaildation{

	/**
	 * 验证规则
	 * @var array
	 */
	protected $rules = [];

	/**
	 * 错误消息
	 * @var array
	 */
	protected $error = [];

	/**
	 * 验证数据
	 * @var array
	 */
	public $vaildationData = [];

	/**
	 *
	 * @var string
	 */
	public $language = 'CN';
	
	/**
	 * 语言包
	 * @var array
	 */
	public $lang = array();

	/**
	 * 初始化验证数据
	 */
	public function __construct($data, $rules = []){
		
		$this->vaildationData = $data;
		foreach ($rules as $v){
				
			$this->rules[] = [
					'field' => $v[0],
					'label' => $v[1],
					'rules' => $v[2],
					'errors' => isset($v[3]) ? $v[3] : [],
			];
		}
		
		$file = dirname(__FILE__).'/local/language_CN.php';
		if( ! file_exists($file)){
			exit('没有找到语言包：'.$file);
		}
		
		require $file;
		$this->lang = $lang;
	}

	/**
	 * 验证规则
	 * @param 	string 	$field
	 * @param 	string 	$label
	 * @param 	array 	$rules
	 * @param 	array 	$errors
	 * @return	Vaildation
	 */
	public function setRules($field, $label = '', $rules = [], $errors = []){

		$rules[] = [
				'field' => $field,
				'label' => $label,
				'rules' => $rules,
				'errors' => $errors,
		];

		return $this;
	}

	/**
	 * 执行验证
	 */
	public function execute(){

		$status = true;
		foreach ($this->rules as $v){
			
			$field = [$v['field']];
			if((boolean)preg_match_all('/\[(.*?)\]/', $v['field'], $matches) === true){
				
				sscanf($v['field'], '%[^[][', $tS);
				$field = [$tS];
				if(isset($matches[1])){
					
					foreach ($matches[1] as $vv){
						$field[] = $vv;
					}
					
				}
			}
			
			$data = $this->_getValue($field, $this->vaildationData);
			$tA = explode('|', trim($v['rules'], '/'));
			$tB = true;
			
			foreach ($data as $val){
				foreach ($tA as $rules){
					$langKey = $rules;
					$param = null;
					if($tB){
						if((boolean)preg_match_all('/\[(.*?)\]/', $rules, $matches) === true){
							sscanf($rules, '%[^[][', $langKey);
							if( ! method_exists($this, '_V'.$langKey)){
								$this->_addError($v['field'], str_replace('{rule}', $rules, $this->lang['no_rule']));
								$tB = false;
								break 3;
							}
							$mothod = '_V'.$langKey;
							$param = $matches[1][0];
							$tB = $this->$mothod($val, $param);
						}else{
							if( ! method_exists($this, '_V'.$rules)){
								$this->_addError($v['field'], str_replace('{rule}', $rules, $this->lang['no_rule']));
								$tB = false;
								break 3;
							}
							$mothod = '_V'.$rules;
							$tB = $this->$mothod($val);
						}
						
						if( ! $tB){
							$error = str_replace('{field}', $v['label'], $this->lang[$langKey]);
							if(isset($v['errors']) && isset($v['errors'][$rules])){
								$error = $v['errors'][$rules];
							}
							if(null != $param){
								$error = str_replace('{param}', $param, $error);
							}
							$this->_addError($v['field'], $v['label'], $error);
							break 3;
						}
					}
				}
			}
		}
		
		return $this->error;
	}
	
	/**
	 * 添加错误消息
	 * @param string $field
	 * @param string $label
	 * @param string $message
	 */
	private function _addError($field, $label, $message){
		$this->error[$field] = $message;
	}
		
	/**
	 * 获取字段提交数据
	 * @param array $field
	 */
	private function _getValue($field, $vaildationData = []){
		
		static $data = [];
		if( ! $vaildationData) $vaildationData = $this->vaildationData;
		
		foreach ($vaildationData as $k => $v){
			
			if((! $field || $k == $field[0]) && ! is_array($v)){
				$data[] = trim($v);
				
			}elseif(is_array($v) && ( ! $field || $k == $field[0])){
				
				$tA = [];
				foreach ($field as $kk => $vv){
					if($kk != 0){
						$tA[] = $vv;
					}	
				}
				
				$this->_getValue($tA, $v);
			}
		}
		
		return $data;
	}

	/**
	 * 验证必须
	 * @param 	string 	$value
	 * @return	boolean
	 */
	private function _Vrequired($value){
		return ($v !== '' || $v);
	}
	
	/**
	 * 验证必须是整数
	 * @param 	string 	$value
	 * @return	boolean
	 */
	private function _Vinteger($value){
		return strlen($value) == 0 || (bool) preg_match('/^[\-+]?[0-9]+$/', $value);
	}
	
	/**
	 * 验证必须是大于0的整数
	 * @param 	string 	$value
	 * @return	boolean
	 */
	private function _VnaturalNoZero($value){
		return strlen($value) == 0 || (ctype_digit($value) && $value != 0);
	}
	
	/**
	 * 验证必须是邮箱格式
	 * @param 	string 	$value
	 * @return	boolean
	 */
	private function _Vemail($value){
		return strlen($value) == 0 || ((boolean)filter_var($value, FILTER_VALIDATE_EMAIL));
	}
	
	/**
	 * 验证字符串长度小于等于
	 * @param 	string 	$value
	 * @param 	string  $param
	 * @return	boolean
	 */
	private function _VmaxLength($value, $param){
		$length = mb_strlen($value);
		return $length == 0 || $length <= $param;
	}
	
	/**
	 * 验证数字必须大于等于
	 * @param 	string 	$value
	 * @param 	string $param
	 * @return	boolean
	 */
	private function _VminLength($value, $param){
		$length = mb_strlen($value);
		return $length == 0 || $length >= $param;
	}
	
	/**
	 * 验证数字必须大于等于
	 * @param 	string 	$value
	 * @param 	string $param
	 * @return	boolean
	 */
	private function _Vmin($value, $param){
		return strlen($value) == 0 || $value >= $param;
	}
	
	/**
	 * 验证数字必须小于等于
	 * @param 	string 	$value
	 * @param 	string $param
	 * @return	boolean
	 */
	private function _Vmax($value, $param){
		return strlen($value) == 0 || $value <= $param;
	}
	
	/**
	 * 验证值必须在制定的范围内
	 * @param string $value
	 * @param string $param
	 * @return	boolean
	 */
	private function _VinList($value, $param){
		$tA = explode(',', $param);
		return strlen($value) == 0 || in_array($value, $tA);
	}
	
	/**
	 * 验证手机号码
	 *
	 * @param	string	$value
	 * @return	boolean
	 */
	private function _Vmobile($value){
		return strlen($value) == 0 || (bool) preg_match('/^0?1[1|3|4|5|7|8][0-9]\d{8}$/', $value);
	}
	
	/**
	 * 验证是否是中文
	 *
	 * @param	string	$value
	 * @return	boolean
	 */
	private function _Vchinese($value){
		return strlen($value) == 0 || (bool) preg_match('/^([\x81-\xfe]|[\x40-\xfe])+$/', $value);
	}
	
	/**
	 * 验证是否是日期格式
	 *
	 * @param	string	$value
	 * @return	boolean
	 */
	private function _Vdate($value){
		if(strlen($value) == 0){
			return true;
		}
		if (preg_match ("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $value, $parts)){
			return (bool)checkdate($parts[2],$parts[3],$parts[1]);
		}
		return false;
	}
}
