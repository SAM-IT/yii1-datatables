<?php


namespace SamIT\Yii1\DataTables;


class DataTableSort extends \CSort
{
    private $_directions;
    /**
     * @inheritdoc
     * @return array|mixed|null
     */
    public function getDirections()
    {
        if(!isset($this->_directions))
        {
            $this->_directions = [];
            if(isset($_GET['order']) && is_array($_GET['order']))
            {
                foreach($_GET['order'] as $i => $details) {
                    if (false !== $attribute = $this->resolveAttribute($_GET['columns'][$details['column']]['name'])) {
                        $this->_directions[$_GET['columns'][$details['column']]['name']] = $details['dir'] === 'desc';
                        if(!$this->multiSort)
                            return $this->_directions;
                        }
                }
            }

            if(empty($this->_directions) && is_array($this->defaultOrder))
                $this->_directions = $this->defaultOrder;
        }
        return $this->_directions;
    }


}