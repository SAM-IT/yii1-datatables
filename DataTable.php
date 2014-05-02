<?php
    /**
     *
     */
    Yii::import('zii.widgets.grid.CGridView');
    class DataTable extends CGridView
    {

		public $selectableRows = 0;
		public $filterPosition = self::FILTER_POS_HEADER;
        /**
         *
         * @var CGridColumn[]
         */
        public $columns = array();
        public $enablePagination = true;
        public $itemsCssClass = 'display';
        public $pageSize = 10;
		public $onInit = ['this.fnUpdateFilters();'];
		/*
		 * @var CActiveDataProvider
		 */
		public $dataProvider;

        protected $config = array(
            'info' => true,
            'lengthChange' => false,
			"createdRow" => "js:function() { this.fnAddMetaData.apply(this, arguments); }",
			
			//"sAjaxSource" => null,
			//'bJQueryUI' => true


        );
        /**
         * If set to true, the widget will render a full <table> that is used
         * by DataTables as its datasource, this is bad performance wise, but
         * will enable the widget to work if there is no javascript support.
         * @var boolean
         */
        public $gracefulDegradation = false;
		/**
		 * This will add several pieces of metadata to the table rows after creation.
		 * @var boolean
		 */
		public $addMetaData = true;

		protected function createDataArray()
		{
			$data = array();

			$paginator = $this->dataProvider->getPagination();
			$this->dataProvider->setPagination(false);
			foreach ($this->dataProvider->getData(true) as $i => $r)
            {
                $row = array();
                foreach ($this->columns as $column)
                {
                    ob_start();
					$column->renderDataCell($i);
					$row[] = $this->removeOuterTag(ob_get_clean());
                }
				if ($this->addMetaData && is_object($r) && method_exists($r, 'getKeyString'))
				{
					$row[] = array(
						'data-key' => $r->getKeyString(),
					);
				}
                $data[] = $row;
            }
			$this->dataProvider->setPagination($paginator);
			return $data;
		}
        
        public function init()
		{
			if(!isset($this->htmlOptions['class']))
				$this->htmlOptions['class']='datatable-view';

            parent::init();
			if ($this->selectableRows == 1)
			{
				$this->itemsCssClass .= ' singleSelect';
				$this->config['fnInitComplete'] = new CJavaScriptExpression("function() { $(this).find('tr input:checked').each(function() { $(this).closest('tr').addClass('selected'); }); }");
			}
			elseif ($this->selectableRows > 1)
			{
				$this->itemsCssClass .= ' multiSelect';
				$this->config['fnInitComplete'] = new CJavaScriptExpression("function() { $(this).find('tr input:checked').each(function() { $(this).closest('tr').addClass('selected'); }); }");
			}
            $this->config["paging"] = $this->enablePagination;// && $this->dataProvider->getTotalItemCount() > $this->pageSize;
//			$this->config["lengthChange"] = $this->pageSize;
            $this->config["oLanguage"]["sInfo"] = Yii::t('app', "Showing entries {start} to {end} out of {total}", array(
                '{start}' => '_START_',
                '{end}' => '_END_',
                '{total}' => '_TOTAL_'
            ));
            $this->config["oLanguage"]["sEmptyTable"] = Yii::t('app', "No data available in table");
            $this->config["oLanguage"]["sInfoEmpty"] = Yii::t('app', "Showing entries 0 to 0 out of 0");
            $this->config["oLanguage"]["sInfoFiltered"] = Yii::t('app', "- filtering from {max} record(s)", array(
                '{max}' => '_MAX_',
            ));

			$this->config["bSort"] = $this->enableSorting;
			$this->config["bFilter"] = !is_null($this->filter);
			$this->config["sDom"] = 'lrtip';

			if (isset($this->ajaxUrl))
			{
				$this->onInit[] = "settings.ajax = " . json_encode($this->ajaxUrl);
			}
			Yii::app()->getClientScript()->registerScript($this->getId() . 'data', "", CClientScript::POS_READY);
        }

        protected function initColumns()
		{
            parent::initColumns();
			foreach ($this->columns as $column)
            {
				$columnConfig = array(
                    'orderable' => $this->enableSorting && isset($column->sortable) && $column->sortable,
				);
				if ($column instanceof CDataColumn)
				{
					switch ($column->type)
					{
						case 'number':
							$columnConfig['type'] = 'numeric';
							break;
						case 'datetime':
							$columnConfig['type'] = 'date';
							break;
						default:
							$columnConfig['type'] = 'html';
					}
				}
				elseif ($column instanceof CLinkColumn)
				{
					$columnConfig['type'] = 'html'; 
				}

				// Set width if applicable.
				if (isset($column->htmlOptions['width']))
				{
					$columnConfig['width'] = $column->htmlOptions['width'];
				}

				// Set style if applicable.
				if (isset($column->htmlOptions['style']))
				{
					// Create custom class:
					$class = __CLASS__ . md5(microtime());
					$css = "td.$class {{$column->htmlOptions['style']}}";
					App()->getClientScript()->registerCss($class, $css );
					$column->htmlOptions['class'] = isset($column->htmlOptions['class']) ? $column->htmlOptions['class'] . ' ' . $class : $class;
				}
				// Set class if applicable.
				if (isset($column->htmlOptions['class']))
				{
					$columnConfig['className'] = $column->htmlOptions['class'];
				}
				$this->config["columns"][] = $columnConfig;
            }
        }

        public function registerClientScript()
		{
			$url = Yii::app()->getAssetManager()->publish(dirname(__FILE__) . '/assets', false, -1, YII_DEBUG);
            Yii::app()->getClientScript()->registerPackage('jQuery');
			if (defined('YII_DEBUG') && YII_DEBUG)
            {
                Yii::app()->getClientScript()->registerScriptFile($url . '/js/jquery.dataTables.js');
				Yii::app()->getClientScript()->registerCssFile($url . '/css/jquery.dataTables.css');
            }
            else
            {
                Yii::app()->getClientScript()->registerScriptFile($url . '/js/jquery.dataTables.min.js');
				Yii::app()->getClientScript()->registerCssFile($url . '/css/jquery.dataTables.min.css');
            }
			
			Yii::app()->getClientScript()->registerCssFile($url . '/css/overrides.css');
			Yii::app()->getClientScript()->registerScriptFile($url . '/js/widget.js');
        }


        /**
         * Renders the data as javascript array into the configuration array.
         */
        protected function renderData()
        {
            $this->config['data'] = $this->createDataArray();
			if (isset($this->config["ajax"]))
			{
//				$this->config["deferLoading"] = $this->dataProvider->getTotalItemCount();
//				$this->config["serverSide"] = true;
			}
			if (!empty($this->onInit))
			{
				$this->config['initComplete'] = new CJavaScriptExpression("function (settings, json) {\n" . implode("\n", $this->onInit) . "\n}");
			}
			$this->config['ordering'] = $this->enableSorting;

			Yii::app()->getClientScript()->registerScript($this->getId() . 'data', "$('#" . $this->getId() . "').data('dataTable', $('#" . $this->getId() . " > table').dataTable(" . CJavaScript::encode($this->config) . "));", CClientScript::POS_READY);
        }

		public function renderFilter()
		{
			if($this->filter!==null)
			{
				echo "<tr class=\"{$this->filterCssClass}\">\n";
				foreach($this->columns as $column)
				{
					echo "<th>";
					if (isset($column->filter) && $column->filter == 'select')
					{
						echo CHtml::dropDownList('filter', null, array());
					}
					elseif (!isset($column->filter) ||  $column->filter !== false)
					{
						echo CHtml::textField('filter');
					}
					echo "</th>";

				}
				echo "</tr>\n";
			}
		}
        /**
         * This function renders the item, either as HTML table or as javascript array.
         */
        public function renderItems()
        {
			$options = array(
				'class' => "dataTable {$this->itemsCssClass}"
			);

			if ($this->addMetaData && isset($this->dataProvider->modelClass))
			{
				$options['data-model'] = $this->dataProvider->modelClass;
			}
			echo CHtml::openTag('table', $options);
			$this->renderTableHeader();

			if (!$this->gracefulDegradation)
            {
                $this->renderData();
            }
			else
			{
				throw new Exception('Graceful degration not yet supported.');
			}
			echo "</table>";

        }

        public function renderPager() {
            //parent::renderPager();
        }
        public function renderSummary() {
            //parent::renderSummary();
        }
		
		public function renderTableHeader()
		{
			$sorting = $this->enableSorting;
			$this->enableSorting = false;
			parent::renderTableHeader();
			$this->enableSorting = $sorting;
		}
        public function run() {
			if (Yii::app()->getRequest()->getIsAjaxRequest())
			{
				$result = ['aaData' => $this->createDataArray()];
				$result['iTotalRecords'] = count($result['aaData']);
				$result['iTotalDisplayRecords'] = count($result['aaData']);
				header("Content-Type: application/json");
				echo json_encode($result);
			}
			else
			{
            	parent::run();
			}
            
        }

		protected function removeOuterTag($str)
		{
			$regex = '/<.*?>(.*)<\/.*?>/s';
			$matches = array();
			if (preg_match($regex, $str, $matches))
			{
				return $matches[1];
			}
			return $str;
		}



}
    
?>