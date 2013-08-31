<?php
    /**
     *
     */
    Yii::import('zii.widgets.grid.CGridView');
    class DataTable extends CGridView
    {

        /**
         *
         * @var CGridColumn[]
         */
        public $columns = array();
        public $enablePagination = true;
        public $itemsCssClass = '';
        public $pageSize = 10;
		
		/*
		 * @var CActiveDataProvider
		 */
		public $dataProvider;

        protected $config = array(
            'bInfo' => true,
            'bLengthChange' => false,
			'aaSorting' => array(),
			//'bJQueryUI' => true


        );
        /**
         * If set to true, the widget will render a full <table> that is used
         * by DataTables as its datasource, this is bad performance wise, but
         * will enable the widget to work if there is no javascript support.
         * @var boolean
         */
        public $gracefulDegradation = false;


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
                $data[] = $row;
            }
			$this->dataProvider->setPagination($paginator);
			return $data;
		}
        protected function getHeader(CGridColumn $column)
        {
            ob_start();
            $column->renderHeaderCell();
            return strip_tags(ob_get_clean());
        }
        public function init()
		{
			if(!isset($this->htmlOptions['class']))
				$this->htmlOptions['class']='datatable-view';

            parent::init();


            $this->config["bPaginate"] = $this->enablePagination;// && $this->dataProvider->getTotalItemCount() > $this->pageSize;
			$this->config["iDisplayLength"] = $this->pageSize;
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
			$this->config["bFilter"] = $this->filter;
        }

        protected function initColumns()
		{
            parent::initColumns();
            foreach ($this->columns as $column)
            {
				$columnConfig = array(
                    'sTitle' => $this->getHeader($column),
                    'bSortable' => $this->enableSorting && $column instanceof CDataColumn && $column->sortable,
				);
				if ($column instanceof CDataColumn)
				{
					switch ($column->type)
					{
						case 'number':
							$columnConfig['sType'] = 'numeric';
							break;
						case 'datetime':
							$columnConfig['sType'] = 'date';
							break;
						default:
							$columnConfig['sType'] = 'html';
					}
				}

				$this->config["aoColumns"][] = $columnConfig;
            }
        }

        public function registerClientScript()
		{
            $url = App()->getAssetManager()->publish(dirname(__FILE__) . '/assets');
            App()->getClientScript()->registerPackage('jQuery');
            if (defined(YII_DEBUG))
            {
                App()->getClientScript()->registerScriptFile($url . '/js/jquery.dataTables.js');
            }
            else
            {
                App()->getClientScript()->registerScriptFile($url . '/js/jquery.dataTables.min.js');
            }
			App()->getClientScript()->registerScriptFile($url . '/js/datatables.reload.js');
            App()->getClientScript()->registerCssFile($url . '/css/jquery.dataTables.css');
			App()->getClientScript()->registerCssFile($url . '/css/overrides.css');
			App()->getClientScript()->registerScriptFile($url . '/js/widget.js');
           
            
        }


        /**
         * Renders the data as javascript array into the configuration array.
         */
        protected function renderData()
        {
            $this->config['aaData'] = $this->createDataArray();
            App()->getClientScript()->registerScript($this->getId(), "$('#" . $this->getId() . "').data('dataTable', $('#" . $this->getId() . " > table').dataTable(" . json_encode($this->config) . "));", CClientScript::POS_READY);
        }

        /**
         * This function renders the item, either has HTML table or as javascript array.
         */
        public function renderItems()
        {
            echo "<table class=\"{$this->itemsCssClass}\">\n";
			echo "</table>";
            if (!$this->gracefulDegradation)
            {
                $this->renderData();
            }

        }

        public function renderPager() {
            //parent::renderPager();
        }
        public function renderSummary() {
            //parent::renderSummary();
        }
        public function run() {
			if (Yii::app()->getRequest()->getIsAjaxRequest())
			{
				$result = array(
					'aaData' => $this->createDataArray()
				);
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
			$regex = '/<.*?>(.*)<\/.*?>/';
			$matches = array();
			if (preg_match($regex, $str, $matches))
			{
				return $matches[1];
			}
			return $str;
		}



}
    
?>