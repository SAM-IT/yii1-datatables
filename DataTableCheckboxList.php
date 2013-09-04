<?php
	require_once(__DIR__ . '/DataTable.php');
	class DataTableCheckboxList extends CInputWidget
	{

		public $checkBoxColumn;
		public $options;
		public $errorOptions;

		public $multiple = true;


		public function init() {
			parent::init();
			$this->checkBoxColumn = array_merge(array(
				'class' => 'CCheckBoxColumn',
				'checked' => function($model, $row, $source) { return in_array($model->user_id, $this->model->user_id); },
			), $this->checkBoxColumn);
			
			
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
		}
		public function run()
		{
			$this->widget('DataTable', $this->options);
			//print_r($this->options);
		}
	}
?>