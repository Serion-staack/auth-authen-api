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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->index();
            $table->string('email')->unique();
            $table->string('verification_code')->nullable();
            $table->timestamp('verification_code_expired_at')->nullable();
            $table->string('notes')->nullable();
            $table->string('address')->nullable();
            $table->string('google_id')->nullable();
            $table->tinyInteger('approved_google_login')->default(2);
            $table->foreignId('role_id')->nullable()->index()->references('id')->on('roles');
            $table->string('phone_number')->nullable();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('login_code')->nullable();
            $table->timestamp('login_code_expires_at')->nullable();
            $table->integer('login_attempts')->default(0);
            $table->timestamp('login_blocked_until')->nullable();
            $table->boolean('alert_login')->default(false);
            $table->dateTime('last_login')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
