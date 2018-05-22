<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterOauthRefreshTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE oauth_refresh_tokens ADD user_id BIGINT NOT NULL DEFAULT 0;");
        DB::statement("ALTER TABLE oauth_refresh_tokens ADD INDEX oauth_refresh_tokens_user_id_index(user_id);");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE oauth_refresh_tokens DROP INDEX oauth_refresh_tokens_user_id_index;");
        DB::statement("ALTER TABLE oauth_refresh_tokens DROP user_id;");
    }
}
