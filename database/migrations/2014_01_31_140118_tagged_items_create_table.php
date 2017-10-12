<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class TaggedItemsCreateTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('tagged_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('thread_id');
            $table->integer('tag_id');

            $table->timestamps();
        });
    }
}
