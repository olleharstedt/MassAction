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
     * Either questionl10ns or groupl10ns or null
     * @var ?string
     */
    public $localized;

    /**
     * @param array $options
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
        if (isset($options['localized'])) {
            $this->localized = $options['localized'];
        }
        $this->data = $options['data'];
    }
}
