<?php
use ZFCTool\Service\Migration\AbstractMigration;

class Migration_99999999_100000_00 extends AbstractMigration
{
    public function up()
    {
        // options table
        $this->createTable('items_s0');
        
        $this->createColumn(
            'items_s0',
            'name',
            AbstractMigration::TYPE_VARCHAR,
            255, null, true, true
        );
                            
        $this->createColumn(
            'items_s0',
            'value',
            AbstractMigration::TYPE_INT,
            null, null, true
        );
        
        // insert data about revision number ZERO
       
        $this->insert(
            'items_s0',
            array(
                'name'  => 'simpleName',
                'value' => 3234,
            )
        );
    }

    public function down()
    {
        $this->dropTable('items_s0');
    }
}

