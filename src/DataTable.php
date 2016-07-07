<?php
	namespace SamIT\Yii1\DataTables;
	use \Yii, \CJavaScriptExpression;

	class DataTable extends \CGridView
    {
        /**
         * @var bool Whether to include jquery-color. It is used for animating background colors of search fields.
         */
        public $useColor = true;
        protected $pluginFiles = [
            'datetime-moment' => [
                '/moment/min/moment-with-locales.min.js',
                '/drmonty-datatables-plugins/sorting/datetime-moment.js'
            ]
        ];

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
        public $columns = [];
        public $itemsCssClass = 'display';
        
        /**
         *
         * @var string[]
         */
		public $onInit = [];

		public $pageSizeOptions = [];

		/**
		 * @var \CActiveDataProvider $dataProvider
		 */
		public $dataProvider;

        protected $config = array(
            'info' => true,
            "createdRow" => "js:function() { this.fnAddMetaData.apply(this, arguments); }",
            'autoWidth' => false,
			'processing' => false,
			"serverSide" => false,
            "pagingType" => "full"
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
		 * If true adds the models' key to the metadata.
		 * If an array adds the fields mentioned in the array (and the key)
		 * to the metadata.
		 *
		 * @var mixed
		 */
		public $addMetaData = true;


        public $plugins = [
            'datetime-moment'
        ];
		protected function createDataArray()
		{
			\Yii::beginProfile('createDataArray');
			$data = [];

			Yii::beginProfile('getData');
            if (!$this->config['serverSide'] && $this->dataProvider instanceof \CActiveDataProvider) {
                $paginator = $this->dataProvider->getPagination();
                $this->dataProvider->setPagination(false);
                $this->dataProvider->criteria->order = '';
                $source = $this->dataProvider->getData(true);
                $this->dataProvider->setPagination($paginator);
            } else {
                $source = $this->dataProvider->getData();
            }

            Yii::endProfile('getData');
            Yii::beginProfile('renderCells');
            // Get column name map.
            $names = array_map([$this, 'getColumnName'], $this->columns);
			foreach ($source as $i => $r)
            {
                $row = [];
                foreach ($this->columns as $j => $column)
                {
					$name = $names[$j];
					$row[$name] = $column->getDataCellContent($i);
                }

				$metaRow = [];
				if ($this->addMetaData !== false)
				{
					Yii::beginProfile('addMetaData');
					if (is_callable([$r, 'getKeyString']))
					{
						$metaRow['data-key'] = call_user_func([$r, 'getKeyString']);
					};
					if (is_array($this->addMetaData))
					{
						foreach ($this->addMetaData as $field)
						{
							$metaRow["data-$field"] = \CHtml::value($r, $field);
						}
					}
                    Yii::endProfile('addMetaData');
				}
				if (isset($this->rowCssClassExpression)) {
					$metaRow['class'] = $this->evaluateExpression($this->rowCssClassExpression,array('row'=> $i,'data'=> $r));
				}
				$row['metaData'] = $metaRow;
				$data[] = $row;
            }
			\Yii::endProfile('renderCells');
			\Yii::endProfile('createDataArray');
			return $data;
		}

		protected function getColumnName(\CGridColumn $column)
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
			static $hashes = [];
			if (parent::getId(false) == null && $autoGenerate)
			{
				// Generate a unique id that does not depend on the number of widgets on the page
				// but on the column configuration.
				if (isset($this->dataProvider->id))
				{
					$hash = substr(md5(json_encode($this->columns)), 0, 5) . $this->dataProvider->id;
				}
				else
				{
					$hash = substr(md5(json_encode($this->columns)), 0, 5);
				}
				while (in_array($hash, $hashes))
				{
					$hash = substr(md5($hash), 0, 5);
				}
				$hashes[] = $hash;
				$this->setId('dt_' . $hash);
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
            if ($this->dataProvider->pagination !== false && $this->enablePagination)
			{
				$this->config["paging"] = true;
				$this->config["pageLength"] = $this->dataProvider->getPagination()->pageSize;
			}
			else
			{
				$this->config["paging"] = false;
			}
            $this->config["language"]["info"] = Yii::t('app', "Showing entries {start} to {end} out of {total}", array(
                '{start}' => '_START_',
                '{end}' => '_END_',
                '{total}' => '_TOTAL_'
            ));
            $this->config["language"]["emptyTable"] = $this->emptyText;
            $this->config["language"]["infoEmpty"] = Yii::t('datatable', "Showing entries 0 to 0 out of 0");
            $this->config["language"]["infoFiltered"] = Yii::t('datatable', "- filtering from {max} record(s)", array(
                '{max}' => '_MAX_',
            ));
			$this->config["language"]['paginate']['next'] =  Yii::t('datatable', 'Next');
			$this->config["language"]['paginate']['previous'] =  Yii::t('datatable', 'Previous');
			$this->config["language"]['filter']['none'] =  Yii::t('datatable', 'No filter');
			$this->config["ordering"] = $this->enableSorting;
			$this->config["searching"] = !is_null($this->filter);
			$this->config["dom"] = 'lrtip';

			if (!empty($this->pageSizeOptions))
			{
				$this->config['lengthMenu'] = true;
				if (key($this->pageSizeOptions) == 0)
				{
					$this->config['lengthMenu'] = $this->pageSizeOptions;
					$oneDimension = true;
				}
				else
				{
					$oneDimension = false;
				}

				$this->config['lengthMenu'][0] = [];
				$this->config['lengthMenu'][1] = [];
				foreach($this->pageSizeOptions as $key => $value)
				{
					if ($oneDimension)
					{
						$this->config['lengthMenu'][0][] = $value;
					}
					else
					{
						$this->config['lengthMenu'][0][] = $key;
					}
					if ($value == -1)
					{
						$this->config['lengthMenu'][1][] = Yii::t('datatable', 'All');
					}
					else
					{
						$this->config['lengthMenu'][1][] = $value;
					}
				}
			}
			else
			{
				$this->config['lengthChange'] = false;
			}
			if (isset($this->ajaxUrl))
			{
				$this->config['ajax']['url'] = $this->ajaxUrl;
//                $this->config['processing'] = true;
                $this->config['serverSide'] = true;
				$this->config['deferLoading'] = true;
			}
        }

        protected function initColumns()
		{
            parent::initColumns();
			foreach ($this->columns as $column)
            {
				$columnConfig = [
                    'orderable' => $this->enableSorting && isset($column->sortable) && $column->sortable,
				];

				$columnConfig['data'] = $this->getColumnName($column);
                $columnConfig['name'] = $this->getColumnName($column);
                /* @var \CDataColumn $column */
				if ($column instanceof \CDataColumn)
				{
					switch ($column->type)
					{
						case 'number':
							$columnConfig['type'] = 'num';
							break;
						case 'datetime':
//                            $columnConfig['type'] = 'moment';
//                            var_dump($this->getFormatter()); die();
                            break;
						case 'date':
//                            var_dump($this->getFormatter()->dateFormat);
//                            var_dump($this->getFormatter()->);
//                            die();
//							$columnConfig['type'] = 'moment';
							break;
						default:
							$columnConfig['type'] = 'html';
					}
				}
				elseif ($column instanceof \CLinkColumn)
				{
					$columnConfig['type'] = 'html';
				}
				elseif ($column instanceof \CCheckBoxColumn)
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
					$class = $this->getId() . md5(microtime());
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
            $url = \Yii::app()->params['bower-asset'] . '/datatables/media';
			/** @var \CClientScript $cs */
			$cs = Yii::app()->clientScript;
            $cs->registerPackage('jquery');
			if (defined('YII_DEBUG') && YII_DEBUG)
            {
				$cs->registerScriptFile($url . '/js/jquery.dataTables.js', $cs::POS_END);
				$cs->registerCssFile($url . '/css/jquery.dataTables.css');
            }
            else
            {
                $cs->registerScriptFile($url . '/js/jquery.dataTables.min.js', $cs::POS_END);
				$cs->registerCssFile($url . '/css/jquery.dataTables.min.css');
            }

            // Jquery-color.
            if ($this->useColor) {
                $cs->registerScriptFile(\Yii::app()->params['bower-asset'] . '/jquery-color/jquery.color.js', $cs::POS_END);
            }


            $assetUrl = Yii::app()->getAssetManager()->publish(dirname(__FILE__) . '/../assets', false, -1, YII_DEBUG);
            $cs->registerCssFile($assetUrl . '/overrides.css');
			$cs->registerScriptFile($assetUrl . '/widget.js', $cs::POS_END);
			if (isset(Yii::app()->Befound)) {
				$cs->registerScriptFile($assetUrl . '/befound.js', $cs::POS_END);
			}

            foreach($this->plugins as $plugin) {
                foreach($this->pluginFiles[$plugin] as $file) {
//                    die(\Yii::app()->params['bower-asset'] . $file);

                    $cs->registerScriptFile(\Yii::app()->params['bower-asset'] . $file, $cs::POS_END);
                }
            }

            // Register locale.
            $language = \Yii::app()->language;
            $cs->registerScript('datatables-locale', "moment.locale('$language');");
            // Register format.
            if ($this->formatter instanceof \CLocalizedFormatter) {
                $format = $this->formatter->locale->getDateFormat($this->formatter->dateFormat);
            } else {
                $format = $this->formatter->dateFormat;
            }

            $format = strtr($format, [
                'd' => 'D',
                'dd' => 'DD',
                'y' => 'YYYY'
            ]);
            $cs->registerScript('datatables-dateformat', "$.fn.dataTable.moment('$format');");
        }


        /**
         * Renders the data as javascript array into the configuration array.
         */
        protected function renderData()
        {
            // Copy all registered scripts.
            $cs = Yii::app()->clientScript;
            if (isset($cs->scripts[$cs::POS_READY])) {
                $scripts =  $cs->scripts[$cs::POS_READY];
                $cs->scripts[$cs::POS_READY] = [];
            }
            
            $config = $this->config;
            
            // Render data
            $config['data'] = $config['serverSide'] ? [] : $this->createDataArray();
            
            // Started empty, now we have scripts.
            if (!isset($scripts) && isset($cs->scripts[$cs::POS_READY])) {
                $tableScripts = $cs->scripts[$cs::POS_READY];
            // Started with scripts and we have new ones.
            } elseif (isset($cs->scripts[$cs::POS_READY])) {
                // Compare newly registered scripts to see which one are data related.
                $tableScripts = implode("\n", array_diff_key($cs->scripts[$cs::POS_READY], $scripts));
            } 
            
            if (isset($scripts)) {
                // Restore original scripts.
                $cs->scripts[$cs::POS_READY] = $scripts;
            }
            if (isset($tableScripts)) {
                $this->onInit[] = "(function ($) { $tableScripts })(settings.oInstance.api().$);";
            }
//            $script = "$('#{$this->getId()}').one('draw.dt', function(e, settings) { (function ($) { $tableScripts })(settings.oInstance.api().$); });";
//            $cs->registerScript($this->getId() . 'scripts', $script);
            
			
			if (isset($config["ajax"]))
			{
//				$this->config["deferLoading"] = $this->dataProvider->getTotalItemCount();
//				$this->config["serverSide"] = true;
			}
			if (!empty($this->onInit))
			{
				$config['initComplete'] = new CJavaScriptExpression("function (settings, json) {\n" . implode("\n", $this->onInit) . "\n}");
			}
			$config['ordering'] = $this->enableSorting;
            
            Yii::app()->getClientScript()->registerScript($this->getId() . 'data', "$('#" . $this->getId() . "').data('dataTable', $('#" . $this->getId() . " table').dataTable(" . \CJavaScript::encode($config) . "));", \CClientScript::POS_READY);
        }
//
		public function renderFilter()
		{
			if($this->filter!==null)
			{
				echo "<tr class=\"{$this->filterCssClass}\">\n";
				foreach($this->columns as $column)
				{
					echo "<th>";
                    if (property_exists($column, 'filter')) {
                        switch($column->filter) {
                            case 'select':
                            case 'select-month':
                                echo \CHtml::dropDownList('filter', null, [], ['id' => "filter_" . $column->id, 'class' => 'form-control']);
                                break;
                            case 'select-strict':
                                echo \CHtml::dropDownList('filter', null, [], ['id' => "filter_" . $column->id, 'class' => 'form-control strict']);
                                break;
                            case 'select2':
                                echo $this->widget(\Befound\Widgets\Select2::CLASS, [
                                    'htmlOptions' => ['id' => "filter_" . $column->id, 'class' => 'form-control'],
                                    'name' => $column->name,
                                    'items' => []
                                ], true);
                                break;
                            case false:
                                // Since switch uses loose comparison we do a strict one manually..
                                if ($column->filter === false) break;
                            default:
                                echo \CHtml::textField('filter', '', ['id' => "filter_" . $column->id, 'class' => 'form-control']);
                        }
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

			if (isset($this->dataProvider->modelClass))
			{
				$options['data-model'] = $this->dataProvider->modelClass;
			}
			$options['data-basemodel'] = $this->baseModel;
			$options['data-route'] = $this->route;
			if ($this->listen)
			{
				$options['data-listen'] = $this->listen;
			}
			echo \CHtml::openTag('table', $options);
			$this->renderTableHeader();

			if (!$this->gracefulDegradation) {
                $this->renderData();
            } else {
                $this->renderTableBody();
                \Yii::app()->clientScript->registerScript($this->getId() . 'init', "$('#" . $this->getId() . "').data('dataTable', $('#" . $this->getId() . " table').dataTable(" . \CJavaScript::encode($this->config) . "));", \CClientScript::POS_READY);
			}
			echo \CHtml::closeTag('table');

        }

        public function renderPager() {
            //parent::renderPager();
        }
        public function renderSummary() {
            //parent::renderSummary();
        }

		/**
		 * Renders a table body row.
		 * @param integer $row the row number (zero-based).
		 */
		public function renderTableRow($row)
		{
			$htmlOptions = array();
			$data = $this->dataProvider->data[$row];
			if($this->rowHtmlOptionsExpression!==null)
			{

				$options=$this->evaluateExpression($this->rowHtmlOptionsExpression,array('row'=>$row,'data'=>$data));
				if(is_array($options))
					$htmlOptions = $options;
			}

			if($this->rowCssClassExpression!==null)
			{
				$data=$this->dataProvider->data[$row];
				$class=$this->evaluateExpression($this->rowCssClassExpression,array('row'=>$row,'data'=>$data));
			}
			elseif(is_array($this->rowCssClass) && ($n=count($this->rowCssClass))>0)
				$class=$this->rowCssClass[$row%$n];

			if(!empty($class))
			{
				if(isset($htmlOptions['class']))
					$htmlOptions['class'].=' '.$class;
				else
					$htmlOptions['class']=$class;
			}

			if ($this->addMetaData !== false)
			{
				if (is_callable([$data, 'getKeyString']))
				{
					$htmlOptions['data-key'] = call_user_func([$data, 'getKeyString']);
				};
				if (is_array($this->addMetaData))
				{
					foreach ($this->addMetaData as $field)
					{
						if (is_object($data))
						{
							$htmlOptions["data-$field"] = $data->$field;
						}
						elseif (is_array($data))
						{
							$htmlOptions["data-$field"] = $data[$field];
						}
					}
				}
			}
			echo \CHtml::openTag('tr', $htmlOptions)."\n";
			foreach($this->columns as $column)
				$column->renderDataCell($row);
			echo "</tr>\n";
		}
		public function renderTableHeader()
		{
			$sorting = $this->enableSorting;
			$this->enableSorting = false;
			parent::renderTableHeader();
			$this->enableSorting = $sorting;
		}
        public function run()
        {
            if (Yii::app()->getRequest()->getIsAjaxRequest()) {
                $this->runAjax();
			} else {
                parent::run();
			}

        }

        protected function runAjax()
        {
            /** @var \CClientScript; */
            $cs = Yii::app()->clientscript;

            $result = ['data' => $this->createDataArray()];
            $result['recordsTotal'] = intval($this->dataProvider->getTotalItemCount());
            $result['recordsFiltered'] = $this->filteredCount();
            $result['draw'] = Yii::app()->request->getParam('draw');
            if (isset($cs->scripts[$cs::POS_READY])) {
                $result['scripts'] = implode(" ", $cs->scripts[$cs::POS_READY]);
            }
            
            // Add filter values.
            $model = $this->dataProvider->model;
            $baseCriteria = $model->getDbCriteria();
            $result['filterData'] = [];
            /** @var \CDataColumn $column */
            foreach($this->columns as $column) {
                if (isset($column->filter)) {
                    switch($column->filter) {
                        case "select":
                        case "select-strict":
                            $values = [];
                            // Get all values for this column.
                            $criteria = clone $this->dataProvider->countCriteria;
                            $criteria->distinct = true;
                            $criteria->select = $column->name;
                            foreach($model->cache(120)->findAll($criteria) as $instance) {
                                $values[$instance->{$column->name}] = [
                                    "key" => $instance->{$column->name},
                                    "value" => isset($column->value)
                                        ? $column->evaluateExpression($column->value,
                                            ['data' => $instance, 'row' => -1])
                                        : $instance->{$column->name}
                                ];
                            }
                            usort($values, function($a, $b) { return strcasecmp($a['value'], $b['value']); });
                            $result['filterData'][$column->name] = $values;
                            break;
                        case "select-month":
                            $values = [];
                            // Get all values for this column.
                            $criteria = clone $this->dataProvider->countCriteria;
                            $criteria->distinct = true;
                            $criteria->select = $column->name;
//                            $criteria->limit = -1;
//                              $command = App()->db->schema->commandBuilder->createFindCommand($model->tableName(), $criteria);
//                            $values = $command->queryColumn();
                            foreach($model->findAll($criteria) as $dateModel) {
                                /** @var \DateTime $date */
                                $date = $dateModel->{$column->name};
                                $key = $date->format("Y-m");
                                $values[$key] = [
                                    "key" => $key,
                                    "value" => \Yii::app()->dateFormatter->format("MMMM yyyy", $date)
                                ];
                            }
                            ksort($values);
                            $result['filterData'][$column->name] = array_values($values);
                            break;

                        default:
                    }

                }
            }

            $model->setDbCriteria($baseCriteria);

            
            header("Content-Type: application/json");
            echo json_encode($result, JSON_PRETTY_PRINT);
        }

		protected function removeOuterTag($str)
		{
			Yii::beginProfile('removeOuterTag');
			$regex = '/<.*?>(.*)<\/.*?>/s';
			$matches = [];

			if (strncmp($str, '<td>', 4) == 0) {
				$result = substr($str, 4, -5);
			} else {
				throw new \Exception("Unknown format: $str");
			}

//			elseif (preg_match($regex, $str, $matches)) {
//				$result = $matches[1];
//			} else {
//				$result = $str;
//			}
			Yii::endProfile('removeOuterTag');
			return $result;
		}


        /**
         * Calculates the total number of data items after filtering.
         * @return integer the total number of data items.
         */
        protected function filteredCount()
        {
            $baseCriteria = $this->dataProvider->model->getDbCriteria(false);
            if($baseCriteria!==null)
                $baseCriteria=clone $baseCriteria;
            $count = $this->dataProvider->model->count($this->dataProvider->criteria);
            $this->dataProvider->model->setDbCriteria($baseCriteria);
            return intval($count);
        }


}

?>