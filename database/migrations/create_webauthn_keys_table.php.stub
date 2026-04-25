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

        Schema::create(Config::getTableName('webauthn_key'), function (Blueprint $table) use ($authenticatableClass, $authenticatableTableName) {
            $table->id();

            $table->foreignIdFor($authenticatableClass, 'user_id')
                ->constrained(table: $authenticatableTableName, indexName: 'webauthn_authenticatable_fk')
                ->cascadeOnDelete();

            $table->string('name')->nullable();
            $table->text('credential_id');
            $table->json('data');

            $table->string('attachment_type', 50)->nullable();
            $table->boolean('is_passkey')->default(false);

            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }
};
