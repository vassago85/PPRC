<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_runs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status', 32)->default('draft');
            $table->boolean('preview_visible')->default(false);
            $table->timestampTz('orders_open_at')->nullable();
            $table->timestampTz('orders_close_at')->nullable();
            $table->text('announcement')->nullable();
            $table->timestampTz('waitlist_last_notified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shop_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_run_id')->constrained('shop_runs')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents');
            $table->string('currency', 8)->default('ZAR');
            $table->string('image_path')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('max_per_order')->nullable();
            $table->timestamps();

            $table->unique(['shop_run_id', 'slug']);
        });

        Schema::create('shop_waitlist_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('confirm_token', 64)->unique();
            $table->string('unsubscribe_token', 64)->unique();
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('unsubscribed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shop_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_run_id')->constrained('shop_runs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default('draft');
            $table->string('ship_to_name')->nullable();
            $table->string('ship_phone', 64)->nullable();
            $table->string('ship_line1')->nullable();
            $table->string('ship_line2')->nullable();
            $table->string('ship_city')->nullable();
            $table->string('ship_province', 120)->nullable();
            $table->string('ship_postal_code', 32)->nullable();
            $table->string('ship_country', 120)->nullable();
            $table->unsignedInteger('subtotal_cents')->default(0);
            $table->unsignedInteger('shipping_cents')->default(0);
            $table->unsignedInteger('total_cents')->default(0);
            $table->string('currency', 8)->default('ZAR');
            $table->string('payment_provider', 32)->nullable();
            $table->string('paystack_reference')->nullable();
            $table->string('eft_reference')->nullable()->unique();
            $table->string('proof_path')->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['shop_run_id', 'user_id']);
        });

        Schema::create('shop_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_order_id')->constrained('shop_orders')->cascadeOnDelete();
            $table->foreignId('shop_product_id')->constrained('shop_products')->restrictOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('line_total_cents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_order_lines');
        Schema::dropIfExists('shop_orders');
        Schema::dropIfExists('shop_waitlist_subscribers');
        Schema::dropIfExists('shop_products');
        Schema::dropIfExists('shop_runs');
    }
};
