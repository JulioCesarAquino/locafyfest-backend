<?php

namespace App\Modules\Address\Services;

use App\Modules\Address\Models\Address;
use App\Models\User;
use App\Modules\Address\Queries\AddressQuery;

class AddressService
{
    protected $addressQuery;

    public function __construct(AddressQuery $addressQuery)
    {
        $this->addressQuery = $addressQuery;
    }

    /**
     * Criar endereço
     */
    public function create(array $data): Address
    {
        $address = Address::create($data);

        // Se é o primeiro endereço do usuário ou foi marcado como padrão
        if ($data['is_default'] ?? false || $this->isFirstAddress($address->user_id)) {
            $address->setAsDefault();
        }

        return $address;
    }

    /**
     * Atualizar endereço
     */
    public function update(Address $address, array $data): Address
    {
        $address->update($data);

        // Se foi marcado como padrão
        if (isset($data['is_default']) && $data['is_default']) {
            $address->setAsDefault();
        }

        return $address->fresh();
    }

    /**
     * Deletar endereço
     */
    public function delete(Address $address): bool
    {
        // Se é o endereço padrão, definir outro como padrão
        if ($address->is_default) {
            $newDefault = Address::where('user_id', $address->user_id)
                                ->where('id', '!=', $address->id)
                                ->first();

            if ($newDefault) {
                $newDefault->setAsDefault();
            }
        }

        return $address->delete();
    }

    /**
     * Definir como endereço padrão
     */
    public function setAsDefault(Address $address): Address
    {
        $address->setAsDefault();
        return $address;
    }

    /**
     * Buscar endereço por CEP (integração com API externa)
     */
    public function searchByCep(string $cep): ?array
    {
        // Remove formatação do CEP
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return null;
        }

        try {
            // Integração com ViaCEP
            $response = file_get_contents("https://viacep.com.br/ws/{$cep}/json/");
            $data = json_decode($response, true);

            if (isset($data['erro'])) {
                return null;
            }

            return [
                'zip_code' => $data['cep'],
                'street' => $data['logradouro'],
                'neighborhood' => $data['bairro'],
                'city' => $data['localidade'],
                'state' => $data['uf'],
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obter coordenadas por endereço (geocoding)
     */
    public function getCoordinates(Address $address): ?array
    {
        // Aqui seria integrada uma API de geocoding como Google Maps
        // Por enquanto retorna null
        return null;
    }

    /**
     * Calcular distância entre endereços
     */
    public function calculateDistance(Address $from, Address $to): ?float
    {
        if (!$from->hasCoordinates() || !$to->hasCoordinates()) {
            return null;
        }

        // Fórmula de Haversine para calcular distância
        $earthRadius = 6371; // Raio da Terra em km

        $latFrom = deg2rad($from->latitude);
        $lonFrom = deg2rad($from->longitude);
        $latTo = deg2rad($to->latitude);
        $lonTo = deg2rad($to->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Validar CEP
     */
    public function validateCep(string $cep): bool
    {
        $cep = preg_replace('/\D/', '', $cep);
        return strlen($cep) === 8;
    }

    /**
     * Formatar CEP
     */
    public function formatCep(string $cep): string
    {
        $cep = preg_replace('/\D/', '', $cep);
        return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $cep);
    }

    /**
     * Verificar se é o primeiro endereço do usuário
     */
    protected function isFirstAddress(int $userId): bool
    {
        return Address::where('user_id', $userId)->count() === 1;
    }

    /**
     * Obter endereços do usuário
     */
    public function getByUser(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return $this->addressQuery->getByUser($user);
    }

    /**
     * Obter endereço padrão do usuário
     */
    public function getDefaultByUser(User $user): ?Address
    {
        return $this->addressQuery->getDefaultByUser($user);
    }

    /**
     * Buscar endereços por cidade/estado
     */
    public function searchByLocation(string $city = null, string $state = null, int $perPage = 15)
    {
        return $this->addressQuery->searchByLocation($city, $state, $perPage);
    }
}

