<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Services\UserService;
use App\Modules\User\Requests\CreateUserRequest;
use App\Modules\User\Requests\UpdateUserRequest;
use App\Modules\User\Requests\ChangePasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Listar usuários
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'name',
                'email',
                'user_type',
                'is_active',
                'email_verified',
                'created_from',
                'created_to',
                'last_login_from',
                'last_login_to',
                'sort_by',
                'sort_order'
            ]);

            $perPage = $request->get('per_page', 15);
            $users = $this->userService->search($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Usuários listados com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar usuários: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibir usuário específico
     */
    public function show(User $user): JsonResponse
    {
        try {
            $user->load(['addresses', 'orders.items.product']);
            $stats = $this->userService->getUserStats($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'stats' => $stats
                ],
                'message' => 'Usuário encontrado'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar usuário: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Criar usuário
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Usuário criado com sucesso'
        ], 201);
    }

    /**
     * Atualizar usuário
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            $updatedUser = $this->userService->update($user, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $updatedUser,
                'message' => 'Usuário atualizado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar usuário: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deletar usuário
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            $this->userService->delete($user);

            return response()->json([
                'success' => true,
                'message' => 'Usuário desativado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar usuário: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload de foto de perfil
     */
    public function uploadProfilePicture(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            $updatedUser = $this->userService->uploadProfilePicture($user, $request->file('profile_picture'));

            return response()->json([
                'success' => true,
                'data' => $updatedUser,
                'message' => 'Foto de perfil atualizada com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer upload da foto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remover foto de perfil
     */
    public function removeProfilePicture(User $user): JsonResponse
    {
        try {
            $updatedUser = $this->userService->removeProfilePicture($user);

            return response()->json([
                'success' => true,
                'data' => $updatedUser,
                'message' => 'Foto de perfil removida com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover foto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Alterar senha
     */
    public function changePassword(ChangePasswordRequest $request, User $user): JsonResponse
    {
        try {
            $success = $this->userService->changePassword(
                $user,
                $request->current_password,
                $request->new_password
            );

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Senha atual incorreta'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Senha alterada com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar senha: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar email
     */
    public function verifyEmail(User $user): JsonResponse
    {
        try {
            $updatedUser = $this->userService->verifyEmail($user);

            return response()->json([
                'success' => true,
                'data' => $updatedUser,
                'message' => 'Email verificado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ativar/Desativar usuário
     */
    public function toggleStatus(User $user): JsonResponse
    {
        try {
            $updatedUser = $this->userService->toggleStatus($user);

            $status = $updatedUser->is_active ? 'ativado' : 'desativado';

            return response()->json([
                'success' => true,
                'data' => $updatedUser,
                'message' => "Usuário {$status} com sucesso"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gerar novo token de API
     */
    public function generateApiToken(User $user): JsonResponse
    {
        try {
            $token = $this->userService->generateApiToken($user);

            return response()->json([
                'success' => true,
                'data' => ['api_token' => $token],
                'message' => 'Token de API gerado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter clientes
     */
    public function getClients(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $clients = $this->userService->getByType('client', $perPage);

            return response()->json([
                'success' => true,
                'data' => $clients,
                'message' => 'Clientes listados com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar clientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter usuários ativos
     */
    public function getActive(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $users = $this->userService->getActive($perPage);

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Usuários ativos listados com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar usuários ativos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter usuários recentes
     */
    public function getRecent(): JsonResponse
    {
        try {
            $users = $this->userService->getRecent(10);

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Usuários recentes listados com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar usuários recentes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter clientes mais ativos
     */
    public function getTopClients(): JsonResponse
    {
        try {
            $clients = $this->userService->getTopClients(10);

            return response()->json([
                'success' => true,
                'data' => $clients,
                'message' => 'Top clientes listados com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar top clientes: ' . $e->getMessage()
            ], 500);
        }
    }
}
