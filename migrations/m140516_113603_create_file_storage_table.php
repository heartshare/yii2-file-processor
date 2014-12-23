<?php

use yii\db\Schema;

class m140516_113603_create_file_storage_table extends \yii\db\Migration
{
    public function up()
    {
        $this->createTable('{{%fp_file_storage}}', [
            'id'        => Schema::TYPE_PK,
            'timestamp' => Schema::TYPE_TIMESTAMP   . ' NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "Время загрузки"',
            'filename'  => Schema::TYPE_STRING      . ' NOT NULL COMMENT "Имя файла"',
            'original'  => Schema::TYPE_STRING      . ' NOT NULL COMMENT "Оригинальное имя файла"',
            'mime'      => Schema::TYPE_STRING      . ' NOT NULL DEFAULT "" COMMENT "Тип файла"',
            'size'      => Schema::TYPE_INTEGER     . ' NOT NULL COMMENT "Размер файла"',
            'width'     => Schema::TYPE_INTEGER     . ' DEFAULT NULL COMMENT "Ширина"',
            'height'    => Schema::TYPE_INTEGER     . ' DEFAULT NULL COMMENT "Высота"',
        ]);

        $this->createTable('{{%fp_file_sequence}}', [
            'id'        => Schema::TYPE_PK,
            'type'      => Schema::TYPE_STRING      . ' DEFAULT NULL COMMENT "Тип"',
            'type_id'   => Schema::TYPE_INTEGER     . ' DEFAULT NULL COMMENT "ID Типа"',
            'file_id'   => Schema::TYPE_INTEGER     . ' DEFAULT NULL COMMENT "ID Файла"',
            'hash'      => Schema::TYPE_STRING      . ' DEFAULT NULL COMMENT "HASH"',
            'ord'       => Schema::TYPE_INTEGER     . ' NOT NULL DEFAULT "0" COMMENT "Порядок отображения"',
        ]);

        $this->addForeignKey('fk_sequence_file', '{{%fp_file_sequence}}', 'file_id', '{{%fp_file_storage}}', 'id', 'CASCADE');
    }

    public function down()
    {
        $this->dropTable('{{%fp_file_storage}}');
        $this->dropTable('{{%fp_file_sequence}}');
    }
}
