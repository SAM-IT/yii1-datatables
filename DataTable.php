<?php
    /**
     *
     */
    Yii::import('zii.widgets.grid.CGridView');
    class DataTable extends CGridView
    {

		/**
		 * If set, datatable will refresh on select event.
		 * @var string Name of the model the data in this table depends on.
		 */
		public $baseModel;
		/**
		 * Set to true to listen to create / delete / update events for the model.
		 * @var boolean
		 */
		public $listen = true;
		public $route;
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
		public $onInit = [];
		/*
		 * @var CActiveDataProvider
		 */
		public $dataProvider;

        protected $config = array(
            'info' => true,
            'lengthChange' => false,
			"createdRow" => "js:function() { this.fnAddMetaData.apply(this, arguments); }",
			'processing' => false
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
                $row = [];
                foreach ($this->columns as $column)
                {
					$name = $this->getColumnName($column);
                    ob_start();
					$column->renderDataCell($i);
					
					if (property_exists($column, 'type') && $column->type == 'number')
					{
						$row[$name] = (int) $this->removeOuterTag(ob_get_clean());
					}
					else
					{
						$row[$name] = $this->removeOuterTag(ob_get_clean());
					}
                }
				if ($this->addMetaData && is_object($r) && method_exists($r, 'getKeyString'))
				{
					$row['metaData'] = array(
						'data-key' => $r->getKeyString(),
					);
				}
                $data[] = $row;
            }
			$this->dataProvider->setPagination($paginator);
			return $data;
		}

		protected function getColumnName($column)
		{
			if (isset($column->name))
			{
				return strtr($column->name, ['.' => '_']);
			}
			else
			{
				return $column->id;
			}
		}

		public function getId($autoGenerate = true) {
			static $ids = [];
			if (parent::getId(false) == null && $autoGenerate)
			{
				// Generate a unique id that does not depend on the number of widgets on the page
				// but on the column configuration.
				$id = 'dt_' . substr(md5(json_encode($this->columns)), 0, 5);
				if (!in_array($id, $ids))
				{
					$this->setId($id);
					$ids[] = $id;
				}
				else
				{
					throw new Exception("Duplicate ID");
				}

			}
			return parent::getId($autoGenerate);
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
			$this->config["pageLength"] = $this->pageSize;
            $this->config["language"]["info"] = Yii::t('app', "Showing entries {start} to {end} out of {total}", array(
                '{start}' => '_START_',
                '{end}' => '_END_',
                '{total}' => '_TOTAL_'
            ));
            $this->config["language"]["emptyTable"] = Yii::t('app', "No data available in table");
            $this->config["language"]["infoEmpty"] = Yii::t('app', "Showing entries 0 to 0 out of 0");
            $this->config["language"]["infoFiltered"] = Yii::t('app', "- filtering from {max} record(s)", array(
                '{max}' => '_MAX_',
            ));

			$this->config["ordering"] = $this->enableSorting;
			$this->config["searching"] = !is_null($this->filter);
			$this->config["dom"] = 'lrtip';

			if (isset($this->ajaxUrl))
			{
				$this->config['ajax']['url'] = $this->ajaxUrl;
			}
        }

        protected function initColumns()
		{
            parent::initColumns();
			foreach ($this->columns as $column)
            {
				$columnConfig = array(
                    'orderable' => $this->enableSorting && isset($column->sortable) && $column->sortable,
				);

				$columnConfig['data'] = $this->getColumnName($column);
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
				
				// Set filter.
				if (isset($column->filter))
				{
					$columnConfig['sFilter'] = $column->filter;
				}
				$this->config["columns"][] = $columnConfig;
            }
        }

        public function registerClientScript()
		{
			$url = Yii::app()->getAssetManager()->publish(dirname(__FILE__) . '/assets', false, -1, YII_DEBUG);
            Yii::app()->getClientScript()->registerPackage('jquery');
			if (defined('YII_DEBUG') && YII_DEBUG)
            {
                Yii::app()->getClientScript()->registerScriptFile($url . '/js/jquery.dataTables.js', CClientScript::POS_END);
				Yii::app()->getClientScript()->registerCssFile($url . '/css/jquery.dataTables.css');
            }
            else
            {
                Yii::app()->getClientScript()->registerScriptFile($url . '/js/jquery.dataTables.min.js', CClientScript::POS_END);
				Yii::app()->getClientScript()->registerCssFile($url . '/css/jquery.dataTables.min.css');
            }
			
			Yii::app()->getClientScript()->registerCssFile($url . '/css/overrides.css');
			Yii::app()->getClientScript()->registerScriptFile($url . '/js/widget.js', CClientScript::POS_END);
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
						echo CHtml::dropDownList('filter', null, [], ['id' => "filter_" . $column->id]);
					}
					elseif (property_exists($column, 'filter') &&  $column->filter !== false)
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

			$options['data-model'] = $this->dataProvider->modelClass;
			$options['data-basemodel'] = $this->baseModel;
			$options['data-route'] = $this->route;
			if ($this->listen)
			{
				$options['data-listen'] = $this->listen;
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
				$result = ['data' => $this->createDataArray()];
				$result['iTotalRecords'] = count($result['data']);
				$result['iTotalDisplayRecords'] = count($result['data']);
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