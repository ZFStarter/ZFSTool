<?php

use ZFCTool\Service\Migration\AbstractMigration;

class Migration_99999999_100000_03 extends AbstractMigration
{
    public function up()
    {
        // options table
        $this->createTable('items_s3');
        
        $this->createColumn(
            'items_s3',
            'name',
            AbstractMigration::TYPE_VARCHAR,
            255, null, true, true
        );
                            
        $this->createColumn(
            'items_s3',
            'value',
            AbstractMigration::TYPE_INT,
            null, null, true
        );
        
        // insert data about revision number ZERO
       
        $this->insert(
            'items_s3',
            array(
                'name'  => 'simpleName',
                'value' => 345,
            )
        );
    }

    public function down()
    {
        $this->dropTable('items_s3');
    }
}

