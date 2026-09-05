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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->string('category')->default('General'); // IT Equipment, Furniture, Vehicle, Machinery, etc.
            $table->string('serial_number')->nullable();
            $table->date('purchase_date');
            $table->decimal('purchase_cost', 15, 2);
            $table->decimal('current_value', 15, 2)->nullable();
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status')->default('in_use'); // in_use, in_stock, maintenance, disposed
            $table->date('warranty_expiry_date')->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            // Disposal details
            $table->date('disposal_date')->nullable();
            $table->string('disposal_reason')->nullable(); // damaged, broken, obsolete, sold, lost
            $table->decimal('scrap_value', 15, 2)->default(0.00);
            $table->text('disposal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Add last_seen_at to users for online tracker
        if (!Schema::hasColumn('users', 'last_seen_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_seen_at')->nullable()->after('last_login_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');

        if (Schema::hasColumn('users', 'last_seen_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('last_seen_at');
            });
        }
    }
};
