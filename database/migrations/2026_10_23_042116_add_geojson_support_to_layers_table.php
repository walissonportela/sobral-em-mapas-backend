
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGeojsonSupportToLayersTable extends Migration
{
    public function up()
    {
        Schema::table('layers', function (Blueprint $table) {
            $table->string('source_format', 20)
                ->default('WMS');

            $table->string('geojson_path', 500)
                ->nullable();
        });
    }

    public function down()
    {
        Schema::table('layers', function (Blueprint $table) {
            $table->dropColumn([
                'source_format',
                'geojson_path',
            ]);
        });
    }
}
