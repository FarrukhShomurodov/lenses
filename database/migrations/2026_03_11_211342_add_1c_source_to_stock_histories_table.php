<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE stock_histories DROP CONSTRAINT IF EXISTS stock_histories_source_check");
        DB::statement("ALTER TABLE stock_histories ADD CONSTRAINT stock_histories_source_check CHECK (source::text = ANY (ARRAY['order'::text, 'manual'::text, '1c'::text]))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE stock_histories DROP CONSTRAINT IF EXISTS stock_histories_source_check");
        DB::statement("ALTER TABLE stock_histories ADD CONSTRAINT stock_histories_source_check CHECK (source::text = ANY (ARRAY['order'::text, 'manual'::text]))");
    }
};
