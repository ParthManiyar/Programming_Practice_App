<?php

use Illuminate\Database\Schema\Blueprint;
use Phinx\Migration\AbstractMigration;

class CreateProblemTable extends BaseMigration
{

    public function up()
    {
        $this->schema->create('problems', function (Blueprint $table) {
            $table->increments('id');

            $table->string('title')->unique();
            $table->string('problemcode')->unique();
            $table->string('author');
            $table->integer('submission');
            $table->timestamps();
        });
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */

    public function down()
    {
        $this->schema->dropIfExists('problems');

    }

}
