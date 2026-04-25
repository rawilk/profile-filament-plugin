<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Rawilk\ProfileFilament\Support\Config;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(Config::getTableName('pending_user_email'), function (Blueprint $table) {
            $table->id();

            $table->morphs('user');
            $table->string('email')->index();
            $table->string('token');

            $table->timestamp('created_at')->nullable();
        });
    }
};
