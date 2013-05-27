<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Stub;

class Product
{
    /**
     * @var int
     */
    private $id;

    /**
     * @param int $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public static function getEntityName()
    {
        return 'Product';
    }
}
