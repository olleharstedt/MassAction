<?php

/**
 * Helper class for column data
 */
class Column
{
    /**
     * @var int
     */
    public $width = 100;

    /**
     * @var boolean
     */
    public $readonly = false;

    /**
     * @var string
     */
    public $header = 'Missing header';

    /**
     * Mandatory; database column name or attribute name
     * @var string
     */
    public $data;

    /**
     * @return Column
     */
    public function __construct($options)
    {
        if (!isset($options['data'])) {
            throw new InvalidArgumentException('Missing data column');
        }

        $this->width = isset($options['width']) ? $options['width'] : $this->width;
        $this->readonly = isset($options['readonly']) ? $options['readonly'] : $this->readonly;
        $this->header = isset($options['header']) ? $options['header'] : $this->header;
        $this->data = $options['data'];
    }

}
