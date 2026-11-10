<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable();
            $table->string('google_id')->nullable()->unique();
            $table->text('avatar_url')->nullable();

            $table->string('status')->default('active')->index();
            $table->softDeletes();
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'phone',
                'google_id',
                'avatar_url',
         
                'status',
                'deleted_at',
            ]);
        });
    }
};
