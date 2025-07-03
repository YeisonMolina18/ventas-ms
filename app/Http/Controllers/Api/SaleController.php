<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;

class SaleController extends Controller
{
    private string $inventarioApiUrl;

    public function __construct()
    {
        $this->inventarioApiUrl = env('INVENTARIO_API_URL');
    }

    public function index(Request $request): JsonResponse
    {
        $salesQuery = Sale::query();

        if ($request->has('producto_id')) {
            $salesQuery->where('producto_id', $request->input('producto_id'));
        }

        return response()->json(SaleResource::collection($salesQuery->get()));
    }

   // En ventas-ms/app/Http/Controllers/Api/SaleController.php

public function store(Request $request): JsonResponse
{
    $data = $request->validate([
        'producto_id' => 'required|integer',
        'cantidad' => 'required|integer|min:1',
        'cliente' => 'required|string|max:255',
    ]);

    $response = Http::get("{$this->inventarioApiUrl}/inventario/{$data['producto_id']}");
    if ($response->failed()) {
        return response()->json(['message' => 'Producto no encontrado.'], 404);
    }

    // --- AJUSTE DEFINITIVO AQUÍ ---
    // Leemos la respuesta directamente, sin ['data']
    $inventario = $response->json();

    if ($inventario['disponibles'] < $data['cantidad']) {
        return response()->json(['message' => 'Stock insuficiente.'], 422);
    }

    $sale = Sale::create($data + ['fecha' => now()]);

    Http::put("{$this->inventarioApiUrl}/inventario/{$data['producto_id']}", [
        'disponibles' => $inventario['disponibles'] - $data['cantidad'],
    ]);

    return response()->json(new SaleResource($sale), 201);
}

    public function show(Sale $sale): JsonResponse
    {
        return response()->json(new SaleResource($sale));
    }

    public function update(Request $request, Sale $sale): JsonResponse
{
    $data = $request->validate(['cantidad' => 'required|integer|min:1']);
    $cantidadNueva = $data['cantidad'];
    $diferencia = $sale->cantidad - $cantidadNueva;

    $response = Http::get("{$this->inventarioApiUrl}/inventario/{$sale->producto_id}");
    if ($response->failed()) {
        return response()->json(['message' => 'Producto no encontrado.'], 404);
    }

    // --- AJUSTE AQUÍ TAMBIÉN ---
    $inventario = $response->json();

    if ($inventario['disponibles'] + $diferencia < 0) {
        return response()->json(['message' => 'Stock insuficiente para la nueva cantidad.'], 422);
    }

    $sale->update(['cantidad' => $cantidadNueva]);

    Http::put("{$this->inventarioApiUrl}/inventario/{$sale->producto_id}", [
        'disponibles' => $inventario['disponibles'] + $diferencia
    ]);

    return response()->json(new SaleResource($sale));
}


    public function destroy(Sale $sale): JsonResponse
{
    $response = Http::get("{$this->inventarioApiUrl}/inventario/{$sale->producto_id}");

    if ($response->successful()) {
        // --- AJUSTE AQUÍ TAMBIÉN ---
        $inventario = $response->json();
        Http::put("{$this->inventarioApiUrl}/inventario/{$sale->producto_id}", [
            'disponibles' => $inventario['disponibles'] + $sale->cantidad,
        ]);
    }

    $sale->delete();

    return response()->json(null, 204);
}
}
