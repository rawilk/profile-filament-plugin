<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('profile-filament.table_names.pending_user_email'), function (Blueprint $table) {
            $table->id();
            $table->morphs('user');
            $table->string('email')->index();
            $table->string('token');
            $table->dateTime('created_at')->nullable();
        });

        Schema::create(config('profile-filament.table_names.old_user_email'), function (Blueprint $table) {
            $table->id();
            $table->morphs('user');
            $table->string('email')->index();
            $table->string('token');
            $table->dateTime('created_at')->nullable();
        });
    }
};
