<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('manufacturer')->nullable();
            $table->string('article')->nullable();
            $table->string('model')->nullable();
            $table->string('coating')->nullable();
            $table->decimal('index', 10, 2)->nullable();
            $table->decimal('sph', 10, 2)->nullable();
            $table->decimal('cyl', 10, 2)->nullable();
            $table->integer('axis')->nullable();
            $table->string('family')->nullable();
            $table->string('color')->nullable();
            $table->string('option')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'manufacturer', 'article', 'model', 'coating', 'index', 'sph', 'cyl', 'axis', 'family', 'color', 'option'
            ]);
        });
    }
};
