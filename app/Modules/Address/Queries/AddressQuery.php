<?php

namespace App\Modules\Address\Queries;

use App\Modules\Address\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AddressQuery
{
    /**
     * Obter endereços do usuário
     */
    public function getByUser(User $user): Collection
    {
        return Address::where('user_id', $user->id)
                     ->orderBy('is_default', 'desc')
                     ->orderBy('created_at', 'desc')
                     ->get();
    }

    /**
     * Obter endereço padrão do usuário
     */
    public function getDefaultByUser(User $user): ?Address
    {
        return Address::where('user_id', $user->id)
                     ->where('is_default', true)
                     ->first();
    }

    /**
     * Buscar endereços por tipo
     */
    public function getByType(string $type, int $perPage = 15): LengthAwarePaginator
    {
        return Address::byType($type)
                     ->with('user')
                     ->orderBy('created_at', 'desc')
                     ->paginate($perPage);
    }

    /**
     * Buscar endereços por cidade/estado
     */
    public function searchByLocation(string $city = null, string $state = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Address::with('user');

        if ($city) {
            $query->where('city', 'like', '%' . $city . '%');
        }

        if ($state) {
            $query->where('state', $state);
        }

        return $query->orderBy('city')
                    ->orderBy('neighborhood')
                    ->paginate($perPage);
    }

    /**
     * Obter endereços por CEP
     */
    public function getByCep(string $cep): Collection
    {
        $cep = preg_replace('/\D/', '', $cep);

        return Address::where('zip_code', $cep)
                     ->with('user')
                     ->get();
    }

    /**
     * Obter endereços com coordenadas
     */
    public function getWithCoordinates(): Collection
    {
        return Address::whereNotNull('latitude')
                     ->whereNotNull('longitude')
                     ->with('user')
                     ->get();
    }

    /**
     * Obter endereços sem coordenadas
     */
    public function getWithoutCoordinates(): Collection
    {
        return Address::where(function ($query) {
            $query->whereNull('latitude')
                  ->orWhereNull('longitude');
        })
        ->with('user')
        ->get();
    }

    /**
     * Obter endereços padrão
     */
    public function getDefaultAddresses(): Collection
    {
        return Address::default()
                     ->with('user')
                     ->orderBy('created_at', 'desc')
                     ->get();
    }

    /**
     * Obter estatísticas de endereços
     */
    public function getStats(): array
    {
        $total = Address::count();
        $withCoordinates = Address::whereNotNull('latitude')
                                 ->whereNotNull('longitude')
                                 ->count();

        $byType = Address::selectRaw('type, COUNT(*) as count')
                        ->groupBy('type')
                        ->pluck('count', 'type')
                        ->toArray();

        $byState = Address::selectRaw('state, COUNT(*) as count')
                         ->groupBy('state')
                         ->orderBy('count', 'desc')
                         ->limit(10)
                         ->pluck('count', 'state')
                         ->toArray();

        $topCities = Address::selectRaw('city, state, COUNT(*) as count')
                           ->groupBy('city', 'state')
                           ->orderBy('count', 'desc')
                           ->limit(10)
                           ->get()
                           ->map(function ($item) {
                               return [
                                   'city' => $item->city,
                                   'state' => $item->state,
                                   'count' => $item->count
                               ];
                           })
                           ->toArray();

        return [
            'total' => $total,
            'with_coordinates' => $withCoordinates,
            'without_coordinates' => $total - $withCoordinates,
            'by_type' => $byType,
            'by_state' => $byState,
            'top_cities' => $topCities,
        ];
    }

    /**
     * Buscar endereços próximos
     */
    public function getNearby(float $latitude, float $longitude, float $radiusKm = 10): Collection
    {
        // Cálculo aproximado usando coordenadas
        $latRange = $radiusKm / 111; // 1 grau de latitude ≈ 111 km
        $lonRange = $radiusKm / (111 * cos(deg2rad($latitude)));

        return Address::whereNotNull('latitude')
                     ->whereNotNull('longitude')
                     ->whereBetween('latitude', [$latitude - $latRange, $latitude + $latRange])
                     ->whereBetween('longitude', [$longitude - $lonRange, $longitude + $lonRange])
                     ->with('user')
                     ->get();
    }

    /**
     * Obter endereços mais utilizados para entrega
     */
    public function getMostUsedForDelivery(int $limit = 10): Collection
    {
        return Address::selectRaw('addresses.*, COUNT(delivery_orders.id) as delivery_count')
                     ->leftJoin('orders as delivery_orders', 'addresses.id', '=', 'delivery_orders.delivery_address_id')
                     ->groupBy('addresses.id')
                     ->orderBy('delivery_count', 'desc')
                     ->limit($limit)
                     ->with('user')
                     ->get();
    }

    /**
     * Obter endereços mais utilizados para retirada
     */
    public function getMostUsedForPickup(int $limit = 10): Collection
    {
        return Address::selectRaw('addresses.*, COUNT(pickup_orders.id) as pickup_count')
                     ->leftJoin('orders as pickup_orders', 'addresses.id', '=', 'pickup_orders.pickup_address_id')
                     ->groupBy('addresses.id')
                     ->orderBy('pickup_count', 'desc')
                     ->limit($limit)
                     ->with('user')
                     ->get();
    }

    /**
     * Buscar endereços por bairro
     */
    public function getByNeighborhood(string $neighborhood, int $perPage = 15): LengthAwarePaginator
    {
        return Address::where('neighborhood', 'like', '%' . $neighborhood . '%')
                     ->with('user')
                     ->orderBy('street')
                     ->paginate($perPage);
    }

    /**
     * Obter endereços recentes
     */
    public function getRecent(int $limit = 10): Collection
    {
        return Address::with('user')
                     ->orderBy('created_at', 'desc')
                     ->limit($limit)
                     ->get();
    }
}

