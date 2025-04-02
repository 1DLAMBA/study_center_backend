<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEducationalDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('educational_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_number'); // Foreign key for personal_details
            $table->string('exam_type');
            $table->string('exam_number');
            $table->string('exam_month');
            $table->year('exam_year');
            $table->string('subject_1');
            $table->string('grade_1');
            $table->string('subject_2')->nullable();
            $table->string('grade_2')->nullable();
            $table->string('subject_3')->nullable();
            $table->string('grade_3')->nullable();
            $table->string('subject_4')->nullable();
            $table->string('grade_4')->nullable();
            $table->string('subject_5')->nullable();
            $table->string('grade_5')->nullable();
            $table->string('subject_6')->nullable();
            $table->string('grade_6')->nullable();
            $table->string('subject_7')->nullable();
            $table->string('grade_7')->nullable();
            $table->string('subject_8')->nullable();
            $table->string('grade_8')->nullable();
            $table->string('subject_9')->nullable();
            $table->string('grade_9')->nullable();
            $table->string('uploaded_ssce')->nullable(); // File upload field
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('application_number')
                ->references('id')
                ->on('personal_details')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('educational_details');
    }
}
