<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MqttService;

class MqttSubscribe extends Command
{
    protected $signature = 'mqtt:subscribe';
    protected $description = 'Subscribe to MQTT topics for heartbeat and config';

    public function handle()
    {
        $this->info("🚀 MQTT subscription started..." . env('MQTT_HOST'));

        try {
            $mqtt = new MqttService();
            $mqtt->subscribeAndListen(); // Includes heartbeat + config handling
        } catch (\Throwable $e) {
            logger()->error("❌ MQTT Subscription Error: " . $e->getMessage());
            $this->error("❌ Failed to subscribe: " . $e->getMessage());
        }
    }
}
