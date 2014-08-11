<?php
	namespace DataTable;
	use \CHtml, \Yii;

	class CheckBoxList extends \CInputWidget
	{
		public $checkBoxColumn;
		public $options;
		public $errorOptions;
		
		public $multiple = true;

		public function init() {
			parent::init();
			$checkboxClass = class_exists(\Befound\Widgets\CheckBoxColumn::CLASS) ? \Befound\Widgets\CheckBoxColumn::CLASS : \CCheckboxColumn::CLASS;
			$this->checkBoxColumn = array_merge(array(
				'class' => $checkboxClass,
				'headerTemplate' => '{item}',
				'checked' => function($model, $row, $source) { 
					if(is_array($this->model->{$this->attribute}))
					{
						return in_array($model->{$source->name}, $this->model->{$this->attribute});
					}
					else
					{
						return $model->{$source->name} == $this->model->{$this->attribute};
					}
				}
			), $this->checkBoxColumn);

			if (isset($this->checkBoxColumn['header']))
			{
				$this->checkBoxColumn['headerTemplate'] = $this->checkBoxColumn['header'] . '&nbsp;&nbsp;' . $this->checkBoxColumn['headerTemplate'];
			}
			if ($this->multiple)
			{
				$this->options['selectableRows'] = 2;
			}
			else
			{
				$this->options['selectableRows'] = 1;
			}

			CHtml::resolveNameID($this->model, $this->attribute, $this->htmlOptions);
			
			if(substr($this->htmlOptions['name'],-2)!=='[]')
			{
				$this->htmlOptions['name'] .= '[]';
			}

			$this->checkBoxColumn['checkBoxHtmlOptions']['name'] = $this->htmlOptions['name'];
			$this->options['columns'][] = $this->checkBoxColumn;
			if (!isset($this->options['id']))
			{
				$this->options['id'] = $this->resolveNameID()[1];
			}
		}
		public function run()
		{
			$widget = $this->beginWidget(DataTable::CLASS, $this->options);
			$widget->run();
			Yii::app()->clientScript->registerScript($widget->id . 'type', new \CJavaScriptExpression("$('#{$widget->id}')[0].type = 'DataTableCheckBoxList';"));
		}
	}
?>