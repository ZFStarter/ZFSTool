<?php

use ZFCTool\Service\Migration\AbstractMigration;

class Migration_99999999_100000_04 extends AbstractMigration
{
    public function up()
    {
        // options table
        $this->createTable('items_s4');
        
        $this->createColumn(
            'items_s4',
            'name',
            AbstractMigration::TYPE_VARCHAR,
            255, null, true, true
        );
                            
        $this->createColumn(
            'items_s4',
            'value',
            AbstractMigration::TYPE_INT,
            null, null, true
        );
        
        // insert data about revision number ZERO
       
        $this->insert(
            'items_s4',
            array(
                'name'  => 'simpleName',
                'value' => 4634,
            )
        );
    }

    public function down()
    {
        $this->dropTable('items_s4');
    }
}

