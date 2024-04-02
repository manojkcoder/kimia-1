<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void{
        Schema::table('products',function(Blueprint $table){
            $table->enum('product_type',["regular","variant"])->default("regular")->after("slug");
            $table->unsignedFloat('price',8,2)->nullable()->change();
        });
    }
    public function down(): void{
        Schema::table('products',function(Blueprint $table){
            $table->dropColumn('product_type');
            $table->unsignedFloat('price',8,2)->change();
        });
    }
};