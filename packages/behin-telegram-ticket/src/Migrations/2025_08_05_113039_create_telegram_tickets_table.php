<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('telegram_tickets', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->enum('status', ['open', 'answered', 'closed'])->default('open');
            $table->timestamps();
        });

        Schema::create('telegram_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('telegram_tickets')->cascadeOnDelete();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('sender_type', 50);
            $table->text('message');
            $table->unsignedBigInteger('reply_to_message_id')->nullable();
            $table->unsignedBigInteger('platform_message_id')->nullable();
            $table->timestamps();
        });

        Schema::table('telegram_ticket_messages', function (Blueprint $table) {
            $table->foreign('reply_to_message_id')
                ->references('id')
                ->on('telegram_ticket_messages')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('telegram_ticket_messages', function (Blueprint $table) {
            $table->dropForeign(['reply_to_message_id']);
        });

        Schema::dropIfExists('telegram_ticket_messages');
        Schema::dropIfExists('telegram_tickets');
    }
};
