<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableUsers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
            ],
            'firstname' => [
                'type'       => 'VARCHAR',
                'constraint' => '180',
            ],
            'lastname' => [
                'type'       => 'VARCHAR',
                'constraint' => '180',
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => '250',
                'unique' => true,
            ],
            'mobile' => [
                'type'       => 'VARCHAR',
                'constraint' => '15',
            ],
            'image' => [
                'type'       => 'TEXT'
            ],
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => '250',
            ],
            'created_at' => [
                'type'       => 'DATETIME',
            ],
            'updated_at' => [
                'type'       => 'DATETIME',
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users');
    }
}
