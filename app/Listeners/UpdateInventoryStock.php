<?php

namespace App\Listeners;

use App\Events\SaleCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateInventoryStock implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SaleCreated $event): void
    {
        Log::info("--> [Worker] Procesando actualización de stock para producto ID: {$event->sale->producto_id}");

        // ======================================================
        // ✅ ¡ESTE ES EL CAMBIO CLAVE! ✅
        // Ahora el listener también usa config() en lugar de env()
        $inventarioApiUrl = config('services.inventario.base_uri');
        // ======================================================

        $sale = $event->sale;

        $response = Http::get("{$inventarioApiUrl}/inventario/{$sale->producto_id}");

        if ($response->successful()) {
            $inventario = $response->json();
            $nuevoStock = $inventario['disponibles'] - $sale->cantidad;

            Http::put("{$inventarioApiUrl}/inventario/{$sale->producto_id}", [
                'disponibles' => $nuevoStock
            ]);
        }
    }
}
