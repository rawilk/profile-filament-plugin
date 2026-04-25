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
        $authenticatableClass = Config::getAuthenticatableModel();

        $authenticatableTableName = (new $authenticatableClass)->getTable();

        Schema::create(Config::getTableName('authenticator_app'), function (Blueprint $table) use ($authenticatableClass, $authenticatableTableName) {
            $table->id();

            $table->foreignIdFor($authenticatableClass, 'user_id')
                ->constrained(table: $authenticatableTableName, indexName: 'authenticator_apps_authenticatable_fk')
                ->cascadeOnDelete();

            $table->string('name')->nullable();
            $table->text('secret')->nullable();

            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }
};
