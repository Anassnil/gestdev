<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Backfill created_by and updated_by from the board owner when missing.
        $diagrams = DB::table('diagrams')->whereNull('created_by')->get();
        foreach ($diagrams as $d) {
            $board = DB::table('boards')->where('id', $d->board_id)->first();
            if ($board && $board->user_id) {
                DB::table('diagrams')->where('id', $d->id)->update([
                    'created_by' => $board->user_id,
                    'updated_by' => $d->updated_by ?? $board->user_id,
                ]);
            }
        }
    }

    public function down()
    {
        // no-op: do not unset historical data
    }
};
