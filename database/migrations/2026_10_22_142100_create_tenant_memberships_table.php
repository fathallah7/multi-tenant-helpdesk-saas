<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('role', 24);
            $table->string('status', 24);
            $table->timestampsTz();

            $table->unique(['tenant_id', 'user_id']);
            $table->unique(['tenant_id', 'id']);
            $table->index('user_id');
        });

        DB::statement("ALTER TABLE tenant_memberships ADD CONSTRAINT tenant_memberships_role_check CHECK (role IN ('owner', 'admin', 'agent'))");
        DB::statement("ALTER TABLE tenant_memberships ADD CONSTRAINT tenant_memberships_status_check CHECK (status IN ('active', 'suspended'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_memberships');
    }
};
