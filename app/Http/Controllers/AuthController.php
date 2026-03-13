<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use App\Modules\User\Services\UserService;
use App\Modules\User\Requests\CreateUserRequest;
use App\Models\VerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCodeMail;

class AuthController extends Controller
{
    /**
     * Registra novo cliente e envia código de verificação de e-mail
     */
    public function register(CreateUserRequest $request, UserService $userService)
    {
        $data = array_merge($request->validated(), [
            'user_type' => 'client',
            'is_active' => true,
        ]);

        $user = $userService->create($data);

        $this->sendCode($user, 'email_verification');

        return response()->json([
            'success' => true,
            'message' => 'Conta criada. Verifique seu e-mail para o código de confirmação.',
            'email' => $user->email,
        ], 201);
    }

    /**
     * Verifica o código de e-mail após cadastro
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        $expired = VerificationCode::where('user_id', $user->id)
            ->where('type', 'email_verification')
            ->where('code', $request->code)
            ->whereNull('used_at')
            ->where('expires_at', '<=', now())
            ->exists();

        if ($expired) {
            return $this->respondWithError('Código expirado. Solicite um novo código.', 422);
        }

        $record = VerificationCode::where('user_id', $user->id)
            ->where('type', 'email_verification')
            ->where('code', $request->code)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record) {
            return $this->respondWithError('Código incorreto. Verifique e tente novamente.', 422);
        }

        $record->update(['used_at' => now()]);
        $user->update(['email_verified_at' => now()]);

        $token = auth('api')->login($user);

        return $this->respondWithToken($token);
    }

    /**
     * Reenvia o código de verificação de e-mail
     */
    public function resendVerification(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->firstOrFail();

        if ($user->email_verified_at) {
            return $this->respondWithError('E-mail já verificado.', 422);
        }

        $this->sendCode($user, 'email_verification');

        return response()->json(['success' => true, 'message' => 'Código reenviado.']);
    }

    /**
     * Envia código para redefinição de senha
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        // Sempre retorna sucesso (não revela se e-mail existe)
        if ($user) {
            $this->sendCode($user, 'password_reset');
        }

        return response()->json([
            'success' => true,
            'message' => 'Se o e-mail estiver cadastrado, você receberá um código em breve.',
        ]);
    }

    /**
     * Redefine a senha com código de verificação
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email',
            'code'                  => 'required|string|size:6',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        $expired = VerificationCode::where('user_id', $user->id)
            ->where('type', 'password_reset')
            ->where('code', $request->code)
            ->whereNull('used_at')
            ->where('expires_at', '<=', now())
            ->exists();

        if ($expired) {
            return $this->respondWithError('Código expirado. Solicite um novo código.', 422);
        }

        $record = VerificationCode::where('user_id', $user->id)
            ->where('type', 'password_reset')
            ->where('code', $request->code)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record) {
            return $this->respondWithError('Código incorreto. Verifique e tente novamente.', 422);
        }

        $record->update(['used_at' => now()]);
        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['success' => true, 'message' => 'Senha redefinida com sucesso.']);
    }

    /**
     * Realiza login e retorna token JWT
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        if (!$token = auth('api')->attempt($credentials)) {
            return $this->respondWithError('Credenciais inválidas.', 401);
        }

        $user = auth('api')->user();

        if (!$user->email_verified_at) {
            auth('api')->logout();
            return response()->json([
                'success' => false,
                'message' => 'E-mail não verificado. Verifique sua caixa de entrada.',
                'email_verified' => false,
                'email' => $user->email,
            ], 403);
        }

        $user->update(['last_login' => now()]);

        return $this->respondWithToken($token);
    }

    /**
     * Retorna os dados do usuário autenticado
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Invalida o token atual (logout)
     */
    public function logout()
    {
        try {
            auth('api')->logout();
            return response()->json(['message' => 'Logout realizado com sucesso']);
        } catch (JWTException $e) {
            return $this->respondWithError('Erro ao realizar logout', 500);
        }
    }

    /**
     * Gera novo token JWT com o refresh token
     */
    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh();
            return $this->respondWithToken($newToken);
        } catch (TokenExpiredException $e) {
            return $this->respondWithError('Token expirado. Faça login novamente.', 401);
        } catch (JWTException $e) {
            return $this->respondWithError('Não foi possível atualizar o token.', 500);
        }
    }

    // ──────────────── Helpers ────────────────

    private function sendCode(User $user, string $type): void
    {
        // Invalida códigos anteriores do mesmo tipo
        VerificationCode::where('user_id', $user->id)
            ->where('type', $type)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        VerificationCode::create([
            'user_id'    => $user->id,
            'code'       => $code,
            'type'       => $type,
            'expires_at' => now()->addMinutes(15),
        ]);

        Mail::to($user->email)->send(new VerificationCodeMail($code, $type, $user->name));
    }

    protected function respondWithToken(string $token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    protected function respondWithError(string $message, int $statusCode = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }
}
