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
        Schema::table('users', function (Blueprint $table) {
            $table->string('libresign_username')->nullable()->after('cpf');
            $table->string('libresign_signer_account')->nullable()->after('libresign_username');
            $table->text('libresign_password')->nullable()->after('libresign_signer_account');
        });

        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->string('libresign_uuid')->nullable()->after('signed_file_path');
            $table->string('libresign_sign_request_uuid')->nullable()->after('libresign_uuid');
            $table->string('assinatura_status')->nullable()->after('libresign_sign_request_uuid');
            $table->string('pdf_assinado_url')->nullable()->after('assinatura_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['libresign_username', 'libresign_signer_account', 'libresign_password']);
        });

        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->dropColumn(['libresign_uuid', 'libresign_sign_request_uuid', 'assinatura_status', 'pdf_assinado_url']);
        });
    }
};
