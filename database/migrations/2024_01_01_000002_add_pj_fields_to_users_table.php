<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPjFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('person_type', ['pf', 'pj'])->default('pf')->after('user_type');
            $table->string('cnpj', 18)->unique()->nullable()->after('cpf');
            $table->string('company_name')->nullable()->after('cnpj');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['person_type', 'cnpj', 'company_name']);
        });
    }
}
