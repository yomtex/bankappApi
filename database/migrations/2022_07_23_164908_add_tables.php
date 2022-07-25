<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTables extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('lastname');
            $table->string('userid')->unique();
            $table->string('username')->unique();
            $table->string('walletBalance');
            $table->string('userCountry');
            $table->string('userCurrency');
            $table->string('telNumber');
            $table->string('userAddress')->nullable();
            $table->integer('isVerified');//bvn or ssn 
            $table->integer('accountStatus');//i.e new user status = 1 restricted = 0,locked=2
            $table->integer('isPrivate');//this show receiver info 

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
            $table->dropColumn([
                "userid",
                "lastname",
                "username",
                "walletBalance",
                "userAddress",
                "userCountry",
                "telNumber",
                "isVerified"
            ]);
        });
    }
}
