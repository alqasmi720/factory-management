<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::create('document_sequences',function(Blueprint $table){$table->id();$table->string('series');$table->unsignedSmallInteger('year');$table->unsignedBigInteger('last_value')->default(0);$table->timestamps();$table->unique(['series','year']);}); }
    public function down(): void { Schema::dropIfExists('document_sequences'); }
};
