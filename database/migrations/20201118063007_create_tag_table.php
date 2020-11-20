<?php

use Illuminate\Database\Schema\Blueprint;
use Phinx\Migration\AbstractMigration;

class CreateTagTable extends BaseMigration
{

   /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->create('tags', function (Blueprint $table) {
            $table->increments('id');

            $table->string('name')->unique();

            $table->timestamps();
        });

        $this->schema->create('problem_tag', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('problem_id');
            $table->unsignedInteger('tag_id');

            $table->foreign('problem_id')
                ->references('id')->on('problems')
                ->onDelete('cascade');

            $table->foreign('tag_id')
                ->references('id')->on('tags')
                ->onDelete('cascade');

            $table->unique(['problem_id', 'tag_id']);

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
        $this->schema->dropIfExists('problem_tag');
        $this->schema->dropIfExists('tags');
    }
}
