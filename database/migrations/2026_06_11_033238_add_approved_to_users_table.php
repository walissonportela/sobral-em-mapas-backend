<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovedToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Adiciona o campo booleano, definindo o padrão como falso (não aprovado)
            // O modificador 'after' organiza a coluna visualmente no banco de dados
            $table->boolean('approved')->default(false)->after('profile_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove a coluna caso você precise fazer um rollback da migration
            $table->dropColumn('approved');
        });
    }
}