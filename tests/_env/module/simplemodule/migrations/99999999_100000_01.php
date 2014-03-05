<?php
use ZFCTool\Service\Migration\AbstractMigration;

class Migration_99999999_100000_01 extends AbstractMigration
{
    public function up()
    {
        // options table
        $this->createTable('items_s1');
        
        $this->createColumn(
            'items_s1',
            'name',
            AbstractMigration::TYPE_VARCHAR,
            255, null, true, true
        );
                            
        $this->createColumn(
            'items_s1',
            'value',
            AbstractMigration::TYPE_INT,
            null, null, true
        );
        
        // insert data about revision number ZERO
       
        $this->insert(
            'items_s1',
            array(
                'name'  => 'simpleName',
                'value' => 1976,
            )
        );
    }

    public function down()
    {
        $this->dropTable('items_s1');
    }
}

