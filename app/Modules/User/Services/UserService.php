<?php

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\User\Queries\UserQuery;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserService
{
    protected $userQuery;

    public function __construct(UserQuery $userQuery)
    {
        $this->userQuery = $userQuery;
    }

    /**
     * Criar um novo usuário
     */
    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        return User::create($data);
    }

    /**
     * Atualizar usuário
     */
    public function update(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
            unset($data['password']);
        }

        $user->update($data);
        return $user->fresh();
    }

    /**
     * Deletar usuário
     */
    public function delete(User $user): bool
    {
        // Soft delete - apenas desativa o usuário
        return $user->update(['is_active' => false]);
    }

    /**
     * Autenticar usuário
     */
    public function authenticate(string $email, string $password): ?User
    {
        $user = $this->userQuery->findByEmail($email);

        if ($user && Hash::check($password, $user->password) && $user->is_active) {
            $user->update(['last_login' => now()]);
            return $user;
        }

        return null;
    }

    /**
     * Gerar novo token de API
     */
    public function generateApiToken(User $user): string
    {
        $token = Str::random(80);
        $user->update(['api_token' => $token]);
        return $token;
    }

    /**
     * Verificar email
     */
    public function verifyEmail(User $user): User
    {
        $user->update([
            'email_verified' => true,
            'email_verified_at' => now()
        ]);

        return $user;
    }

    /**
     * Upload de foto de perfil
     */
    public function uploadProfilePicture(User $user, UploadedFile $file): User
    {
        // Deletar foto anterior se existir
        if ($user->profile_picture_path) {
            Storage::delete($user->profile_picture_path);
        }

        $path = $file->store('profile-pictures', 'public');

        $user->update(['profile_picture_path' => $path]);

        return $user;
    }

    /**
     * Remover foto de perfil
     */
    public function removeProfilePicture(User $user): User
    {
        if ($user->profile_picture_path) {
            Storage::delete($user->profile_picture_path);
            $user->update(['profile_picture_path' => null]);
        }

        return $user;
    }

    /**
     * Alterar senha
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            return false;
        }

        $user->update(['password' => Hash::make($newPassword)]);
        return true;
    }

    /**
     * Resetar senha
     */
    public function resetPassword(User $user, string $newPassword): User
    {
        $user->update(['password' => Hash::make($newPassword)]);
        return $user;
    }

    /**
     * Ativar/Desativar usuário
     */
    public function toggleStatus(User $user): User
    {
        $user->update(['is_active' => !$user->is_active]);
        return $user;
    }

    /**
     * Obter estatísticas do usuário
     */
    public function getUserStats(User $user): array
    {
        return [
            'total_orders' => $user->orders()->count(),
            'active_orders' => $user->orders()->active()->count(),
            'completed_orders' => $user->orders()->completed()->count(),
            'total_spent' => $user->orders()->where('payment_status', 'paid')->sum('total_amount'),
            'favorite_products' => $user->favorites()->count(),
            'reviews_count' => $user->reviews()->count(),
            'unread_notifications' => $user->unreadNotifications()->count(),
        ];
    }

    /**
     * Buscar usuários com filtros
     */
    public function search(array $filters = [], int $perPage = 15)
    {
        return $this->userQuery->search($filters, $perPage);
    }

    /**
     * Obter usuários por tipo
     */
    public function getByType(string $type, int $perPage = 15)
    {
        return $this->userQuery->getByType($type, $perPage);
    }

    /**
     * Obter usuários ativos
     */
    public function getActive(int $perPage = 15)
    {
        return $this->userQuery->getActive($perPage);
    }

    /**
     * Validar se email já existe
     */
    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        $query = User::where('email', $email);

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        return $query->exists();
    }

    /**
     * Validar se CPF já existe
     */
    public function cpfExists(string $cpf, ?int $excludeUserId = null): bool
    {
        $query = User::where('cpf', $cpf);

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        return $query->exists();
    }

    /**
     * Obter usuários recentes
     */
    public function getRecent(int $limit = 10)
    {
        return $this->userQuery->getRecent($limit);
    }

    /**
     * Obter clientes mais ativos
     */
    public function getTopClients(int $limit = 10)
    {
        return $this->userQuery->getTopClients($limit);
    }
}

